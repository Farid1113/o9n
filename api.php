<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
only_post();
$action=(string)($_POST['action']??'');
if($action==='track'){
 $num=trim((string)($_POST['order_number']??''));$phone=valid_phone((string)($_POST['phone']??''));
 if(!$phone||!preg_match('/^NV-[A-Z0-9-]{6,27}$/',$num))json_error('Invalid order number or phone.');
 $row=one('SELECT order_number,status,created_at,total FROM orders WHERE order_number=? AND REPLACE(REPLACE(customer_phone," ",""),"-","")=?',[$num,$phone]);
 if(!$row)json_error('No matching order found.',404);
 json_ok(['order'=>$row]);
}
if($action!=='order')json_error('Unknown action.',404);
$last=(int)($_SESSION['last_order_at']??0);if(time()-$last<20)json_error('Please wait a moment before another order.',429);
if(time()-(int)($_SESSION['order_attempt_window']??0)>3600){$_SESSION['order_attempt_window']=time();$_SESSION['order_attempts']=0;}
$attempts=(int)($_SESSION['order_attempts']??0);if($attempts>=12)json_error('Too many order attempts. Please try later.',429);
$_SESSION['order_attempts']=$attempts+1;
// Shared rate limit across browser sessions; keep only a one-way IP hash.
$ipHash=hash('sha256',($_SERVER['REMOTE_ADDR']??'local').':nova-order-v1');
$recent=one('SELECT COUNT(*) n FROM order_attempts WHERE ip_hash=? AND attempted_at>DATE_SUB(NOW(), INTERVAL 1 HOUR)',[$ipHash]);
if((int)$recent['n']>=30)json_error('Too many orders from this connection. Please try again later.',429);
exec_sql('INSERT INTO order_attempts(ip_hash) VALUES (?)',[$ipHash]);
$customer=trim(mb_safe_sub((string)($_POST['customer_name']??''),150));
$phone=valid_phone((string)($_POST['customer_phone']??''));
$city=trim(mb_safe_sub((string)($_POST['city']??''),120));
$address=trim(mb_safe_sub((string)($_POST['address']??''),300));
$notes=trim(mb_safe_sub((string)($_POST['notes']??''),1000));
if(strlen($customer)<3||!$phone||strlen($city)<2)json_error('Please enter your name, valid phone number and city.');
$raw=(string)($_POST['items']??'');$items=json_decode($raw,true);
if(!is_array($items)||!count($items)||count($items)>30)json_error('Invalid cart.');
$normalized=[];
foreach($items as $it){
 if(!is_array($it)||!isset($it['id'],$it['qty']))json_error('Invalid cart row.');
 $pid=filter_var($it['id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
 $vid=isset($it['variant'])&&$it['variant']!==null&&$it['variant']!==''?filter_var($it['variant'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]):null;
 $qty=filter_var($it['qty'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>20]]);
 if(!$pid||!$qty||($vid===false))json_error('Invalid quantity / variant.');
 $key=$pid.':'.($vid??'0');
 if(isset($normalized[$key])){$normalized[$key]['qty']+=$qty;if($normalized[$key]['qty']>20)json_error('Quantity too high.');}
 else $normalized[$key]=['id'=>$pid,'variant'=>$vid,'qty'=>$qty];
}
ksort($normalized);
$promo=strtoupper(trim(mb_safe_sub((string)($_POST['coupon']??''),50)));
if($promo!==''&&!preg_match('/^[A-Z0-9_-]{3,50}$/',$promo))json_error('Invalid coupon.');
try{
 $pdo=db();$pdo->beginTransaction();$subtotal=0;$lines=[];
 foreach($normalized as $it){
  $p=one('SELECT id,name_ar,name_fr,name_en,price,stock,sku,active FROM products WHERE id=? FOR UPDATE',[$it['id']]);
  if(!$p||!$p['active'])throw new RuntimeException('An item is no longer available.');
  $variantCount=one('SELECT COUNT(*) n FROM product_variants WHERE product_id=?',[$p['id']]);
  $v=null;
  if((int)$variantCount['n']>0){
   if(!$it['variant'])throw new RuntimeException('Please choose a size / color.');
   $v=one('SELECT * FROM product_variants WHERE id=? AND product_id=? FOR UPDATE',[$it['variant'],$p['id']]);
   if(!$v)throw new RuntimeException('Selected variant not found.');
  }elseif($it['variant'])throw new RuntimeException('Invalid variation.');
  $stock=(int)($v?$v['stock']:$p['stock']);
  if($stock<$it['qty'])throw new RuntimeException('Insufficient stock for '.$p['name_fr'].'.');
  $unit=round((float)$p['price']+(float)($v['price_adjustment']??0),2);
  if($unit<=0)throw new RuntimeException('Product is not currently available.');
  $subtotal+=($unit*$it['qty']);
  $lines[]=['pid'=>$p['id'],'vid'=>$v['id']??null,'qty'=>$it['qty'],'unit'=>$unit,'total'=>round($unit*$it['qty'],2),'sku'=>$v['sku']??$p['sku'],'name'=>$p['name_fr'],'label'=>$v?trim($v['size'].' / '.$v['color'],' /'):null];
 }
 $subtotal=round($subtotal,2);$minimum=(float)setting('min_order','0');if($subtotal<$minimum)throw new RuntimeException('Minimum order is '.price($minimum).'.');
 $discount=0;$couponRow=null;
 if($promo){
  $couponRow=one('SELECT * FROM coupons WHERE code=? FOR UPDATE',[$promo]);
  if(!$couponRow||!$couponRow['active']||($couponRow['expires_at']&&strtotime($couponRow['expires_at'])<time())||($couponRow['max_uses']>0&&$couponRow['used_count']>=$couponRow['max_uses'])||$subtotal<(float)$couponRow['min_subtotal'])throw new RuntimeException('Coupon invalid, expired or minimum not met.');
  $discount=round(min($subtotal,$couponRow['discount_type']==='percent'?$subtotal*min(100,(float)$couponRow['amount'])/100:(float)$couponRow['amount']),2);
 }
 $shipping=(float)setting('shipping_fee','35');$freeAt=(float)setting('free_shipping_above','399');
 if($freeAt>0&&$subtotal-$discount>=$freeAt)$shipping=0;
 $shipping=max(0,round($shipping,2));$total=round($subtotal-$discount+$shipping,2);
 $orderNumber='NV-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(6)));
 exec_sql('INSERT INTO orders(order_number,customer_name,customer_phone,city,address,notes,subtotal,discount_amount,shipping_fee,total,coupon_code,payment_method) VALUES (?,?,?,?,?,?,?,?,?,?,?,"cod")',[$orderNumber,$customer,$phone,$city,$address,$notes,$subtotal,$discount,$shipping,$total,$promo?:null]);
 $orderId=(int)$pdo->lastInsertId();
 foreach($lines as $line){
  exec_sql('INSERT INTO order_items(order_id,product_id,variant_id,sku,product_name,variant_label,qty,unit_price,line_total) VALUES (?,?,?,?,?,?,?,?,?)',[$orderId,$line['pid'],$line['vid'],$line['sku'],$line['name'],$line['label'],$line['qty'],$line['unit'],$line['total']]);
  if($line['vid'])exec_sql('UPDATE product_variants SET stock=stock-? WHERE id=?',[$line['qty'],$line['vid']]);
  else exec_sql('UPDATE products SET stock=stock-? WHERE id=?',[$line['qty'],$line['pid']]);
 }
 if($couponRow)exec_sql('UPDATE coupons SET used_count=used_count+1 WHERE id=?',[$couponRow['id']]);
 $pdo->commit();$_SESSION['last_order_at']=time();
 $o=['order_number'=>$orderNumber,'customer_name'=>$customer,'customer_phone'=>$phone,'city'=>$city,'total'=>$total];
 // Notifications are best-effort. A remote service failure never loses a completed order.
 notify_new_order($o);
 json_ok(['order_number'=>$orderNumber,'total'=>$total,'shipping'=>$shipping,'discount'=>$discount,'message'=>'Order received']);
}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();error_log('Order error: '.$e->getMessage());json_error($e instanceof RuntimeException?$e->getMessage():'Could not place order. Please try again.',400);}
