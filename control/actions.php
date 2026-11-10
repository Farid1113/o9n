<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';
require_admin();only_post();
$action=(string)($_POST['action']??'');
$target=['product_save'=>'products','product_delete'=>'products','category_save'=>'categories','category_delete'=>'categories','coupon_save'=>'coupons','coupon_delete'=>'coupons','order_status'=>'orders','settings_save'=>($_POST['section']??'settings'),'password_change'=>'security'][$action]??'dashboard';
if(!in_array($target,['products','categories','coupons','orders','settings','notifications','security'],true))$target='dashboard';
function redirect_admin(string $tab,string $msg,bool $ok=true):never {$_SESSION['flash']=['type'=>$ok?'success':'error','message'=>$msg];header('Location: '.url('control/index.php?tab='.$tab));exit;}
function slugify(string $raw,string $default):string{$s=strtolower(trim($raw));$s=preg_replace('/[^a-z0-9-]+/','-',$s);$s=trim($s,'-');return $s?:$default;}
function number_post(string $key,float $default=0):float { $v=(string)($_POST[$key]??$default);if(!is_numeric($v))throw new RuntimeException('Invalid number for '.$key);$f=round((float)$v,2);if(!is_finite($f)||$f<0||$f>10000000)throw new RuntimeException('Out-of-range amount: '.$key);return $f;}
try{
 switch($action){
 case 'product_save':
  $id=(int)($_POST['id']??0);$data=[];
  foreach(['ar','fr','en'] as $l){$data['name_'.$l]=input_str('name_'.$l,250);$data['description_'.$l]=input_str('description_'.$l,5000);if(!$data['name_'.$l])throw new RuntimeException('Product names in all 3 languages required.');}
  $pprice=number_post('price');if($pprice<=0)throw new RuntimeException('Selling price must be >0.');$oldprice=number_post('compare_at_price');
  $stock=filter_var($_POST['stock']??'0',FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);if($stock===false)throw new RuntimeException('Invalid stock.');
  $cat=(int)($_POST['category_id']??0);$catid=$cat>0?$cat:null;
  if($catid&&!one('SELECT id FROM categories WHERE id=?',[$catid]))throw new RuntimeException('Invalid category.');
  $previous=$id?one('SELECT * FROM products WHERE id=?',[$id]):null;if($id&&!$previous)throw new RuntimeException('Product not found.');
  $slug=slugify(input_str('slug',180),'nova-'.bin2hex(random_bytes(5)));
  $image=save_uploaded_image('image',$previous['image']??null);
  $values=[$catid,$slug,input_str('sku',90),$data['name_ar'],$data['name_fr'],$data['name_en'],$data['description_ar'],$data['description_fr'],$data['description_en'],$pprice,$oldprice,(int)$stock,$image,isset($_POST['featured'])?1:0,isset($_POST['active'])?1:0];
  $pdo=db();$pdo->beginTransaction();
  if($previous){exec_sql('UPDATE products SET category_id=?,slug=?,sku=?,name_ar=?,name_fr=?,name_en=?,description_ar=?,description_fr=?,description_en=?,price=?,compare_at_price=?,stock=?,image=?,featured=?,active=? WHERE id=?',[...$values,$id]);}
  else{exec_sql('INSERT INTO products(category_id,slug,sku,name_ar,name_fr,name_en,description_ar,description_fr,description_en,price,compare_at_price,stock,image,featured,active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',$values);$id=(int)$pdo->lastInsertId();}
  $sizes=$_POST['variant_size']??[];$colors=$_POST['variant_color']??[];$skus=$_POST['variant_sku']??[];$adjusts=$_POST['variant_adjust']??[];$stocks=$_POST['variant_stock']??[];
  if(!is_array($sizes)||count($sizes)>100)throw new RuntimeException('Maximum 100 variations per product.');
  $newVariants=[];
  foreach($sizes as $i=>$size){$size=trim(mb_safe_sub((string)$size,80));$color=trim(mb_safe_sub((string)($colors[$i]??''),80));$vsku=trim(mb_safe_sub((string)($skus[$i]??''),90));if($size===''&&$color==='')continue;
   $delta=(float)($adjusts[$i]??0);$vs=filter_var($stocks[$i]??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
   if(!is_finite($delta)||$vs===false||$pprice+$delta<=0)throw new RuntimeException('Invalid variant price or stock.');
   $newVariants[]=[$id,$size,$color,$vsku,round($delta,2),(int)$vs];
  }
  exec_sql('DELETE FROM product_variants WHERE product_id=?',[$id]);
  foreach($newVariants as $v)exec_sql('INSERT INTO product_variants(product_id,size,color,sku,price_adjustment,stock) VALUES (?,?,?,?,?,?)',$v);
  $delete=$_POST['delete_gallery']??[];if(is_array($delete))foreach($delete as $gid){exec_sql('DELETE FROM product_images WHERE product_id=? AND id=?',[$id,(int)$gid]);}
  if(isset($_FILES['gallery']['name'])&&is_array($_FILES['gallery']['name'])){
   if(count($_FILES['gallery']['name'])>10)throw new RuntimeException('Maximum 10 photos per batch.');
   foreach($_FILES['gallery']['name'] as $i=>$filename){if(!$filename)continue;
    $_FILES['_temp_gallery']=['name'=>$filename,'type'=>$_FILES['gallery']['type'][$i],'tmp_name'=>$_FILES['gallery']['tmp_name'][$i],'error'=>$_FILES['gallery']['error'][$i],'size'=>$_FILES['gallery']['size'][$i]];
    $path=save_uploaded_image('_temp_gallery');exec_sql('INSERT INTO product_images(product_id,image) VALUES (?,?)',[$id,$path]);
   }
  }
  $pdo->commit();audit('product_save','Product #'.$id.' · '.$data['name_fr']);redirect_admin('products','Product saved successfully.');
 case 'product_delete':
  $id=(int)($_POST['id']??0);exec_sql('DELETE FROM products WHERE id=?',[$id]);audit('product_delete','Product #'.$id);redirect_admin('products','Product deleted. Historical order items remain.');
 case 'category_save':
  $id=(int)($_POST['id']??0);$ar=input_str('name_ar',160);$fr=input_str('name_fr',160);$en=input_str('name_en',160);if(!$ar||!$fr||!$en)throw new RuntimeException('3 languages required.');
  $slug=slugify(input_str('slug',140),'cat-'.bin2hex(random_bytes(4)));$sort=(int)($_POST['sort_order']??0);$active=isset($_POST['active'])?1:0;
  if($id)exec_sql('UPDATE categories SET slug=?,name_ar=?,name_fr=?,name_en=?,sort_order=?,active=? WHERE id=?',[$slug,$ar,$fr,$en,$sort,$active,$id]);
  else exec_sql('INSERT INTO categories(slug,name_ar,name_fr,name_en,sort_order,active) VALUES (?,?,?,?,?,?)',[$slug,$ar,$fr,$en,$sort,$active]);
  audit('category_save',$fr);redirect_admin('categories','Category saved.');
 case 'category_delete':
  $id=(int)($_POST['id']??0);exec_sql('DELETE FROM categories WHERE id=?',[$id]);audit('category_delete','Category #'.$id);redirect_admin('categories','Category deleted. Its products remain uncategorized.');
 case 'coupon_save':
  $id=(int)($_POST['id']??0);$code=strtoupper(input_str('code',50));if(!preg_match('/^[A-Z0-9_-]{3,50}$/',$code))throw new RuntimeException('Invalid coupon code.');
  $typ=(string)($_POST['discount_type']??'percent');if(!in_array($typ,['percent','fixed'],true))throw new RuntimeException('Invalid discount type.');
  $amt=number_post('amount');if($amt<=0||($typ==='percent'&&$amt>100))throw new RuntimeException('Invalid discount amount.');$min=number_post('min_subtotal');
  $max=(int)($_POST['max_uses']??0);if($max<0)throw new RuntimeException('Invalid usage limit.');$raw=input_str('expires_at',40);$exp=null;
  if($raw){$d=DateTime::createFromFormat('Y-m-d\TH:i',$raw);if(!$d)throw new RuntimeException('Invalid expiry date.');$exp=$d->format('Y-m-d H:i:s');}
  $args=[$code,$typ,$amt,$min,$max,$exp,isset($_POST['active'])?1:0];
  if($id)exec_sql('UPDATE coupons SET code=?,discount_type=?,amount=?,min_subtotal=?,max_uses=?,expires_at=?,active=? WHERE id=?',[...$args,$id]);
  else exec_sql('INSERT INTO coupons(code,discount_type,amount,min_subtotal,max_uses,expires_at,active) VALUES (?,?,?,?,?,?,?)',$args);
  audit('coupon_save',$code);redirect_admin('coupons','Coupon saved.');
 case 'coupon_delete':
  $id=(int)($_POST['id']??0);exec_sql('DELETE FROM coupons WHERE id=?',[$id]);audit('coupon_delete','Coupon #'.$id);redirect_admin('coupons','Coupon deleted.');
 case 'order_status':
  $id=(int)($_POST['order_id']??0);$next=(string)($_POST['status']??'');if(!in_array($next,['new','confirmed','preparing','shipped','delivered','cancelled'],true))throw new RuntimeException('Invalid order status.');
  db()->beginTransaction();$o=one('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id]);if(!$o)throw new RuntimeException('Order not found.');
  if($o['status']==='cancelled'&&$next!=='cancelled')throw new RuntimeException('Cancelled orders cannot be reopened.');
  if($o['status']==='delivered'&&$next==='cancelled')throw new RuntimeException('Delivered orders cannot be cancelled.');
  if($o['status']!==$next&&$next==='cancelled'){
   $items=all('SELECT * FROM order_items WHERE order_id=?',[$id]);
   foreach($items as $line){
     if($line['variant_id'])exec_sql('UPDATE product_variants SET stock=stock+? WHERE id=?',[$line['qty'],$line['variant_id']]);
     elseif(!$line['variant_label']&&$line['product_id'])exec_sql('UPDATE products SET stock=stock+? WHERE id=?',[$line['qty'],$line['product_id']]);
   }
   if($o['coupon_code'])exec_sql('UPDATE coupons SET used_count=GREATEST(0,used_count-1) WHERE code=?',[$o['coupon_code']]);
  }
  exec_sql('UPDATE orders SET status=? WHERE id=?',[$next,$id]);db()->commit();audit('order_status',$o['order_number'].' → '.$next);redirect_admin('orders','Order status updated.');
 case 'settings_save':
  $section=(string)($_POST['section']??'settings');
  $keys=$section==='notifications'?['telegram_token','telegram_chat_id','telegram_orders','telegram_visits','wa_cloud_token','wa_phone_id','wa_notify_number','wa_template','wa_template_lang','whatsapp_orders']:['store_name','announcement_ar','announcement_fr','announcement_en','accent','shipping_fee','free_shipping_above','min_order','support_whatsapp','support_email','instagram','facebook','maintenance'];
  foreach($keys as $key){
   if(in_array($key,['telegram_orders','telegram_visits','whatsapp_orders','maintenance'],true))$val=isset($_POST[$key])?'1':'0';
   elseif(in_array($key,['telegram_token','wa_cloud_token'],true)){$val=trim((string)($_POST[$key]??''));if($val==='')continue;}
   elseif(in_array($key,['shipping_fee','free_shipping_above','min_order'],true))$val=(string)number_post($key);
   elseif($key==='accent'){$val=input_str($key,7);if(!preg_match('/^#[0-9a-fA-F]{6}$/',$val))throw new RuntimeException('Invalid HEX color.');}
   else $val=input_str($key,350);
   exec_sql('INSERT INTO settings(name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',[$key,$val]);
  }
  audit('settings_save',$section);redirect_admin($section,'Configuration saved.');
 case 'password_change':
  $a=one('SELECT password_hash FROM admins WHERE id=?',[$_SESSION['admin_id']]);$old=(string)($_POST['current_password']??'');$pass=(string)($_POST['new_password']??'');
  if(!$a||!password_verify($old,$a['password_hash']))throw new RuntimeException('Current password is incorrect.');
  if(strlen($pass)<12||strlen($pass)>200||$pass!==(string)($_POST['confirm_password']??''))throw new RuntimeException('Check the new password and confirmation.');
  exec_sql('UPDATE admins SET password_hash=? WHERE id=?',[password_hash($pass,PASSWORD_DEFAULT),$_SESSION['admin_id']]);session_regenerate_id(true);audit('password_change','Password updated');redirect_admin('security','Password successfully updated.');
 default:throw new RuntimeException('Unknown operation.');
 }
}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();error_log('Admin error: '.$e->getMessage());
 $message=$e instanceof RuntimeException?$e->getMessage():'Operation failed. Check duplicate slug or invalid input.';
 redirect_admin($target,$message,false);
}
