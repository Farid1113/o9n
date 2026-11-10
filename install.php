<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
$err='';$done=false;$check=false;
try {
 $server=new PDO('mysql:host='.$config['host'].';port='.$config['port'].';charset=utf8mb4',$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $dbname=$config['database'];
 if(!preg_match('/^[A-Za-z0-9_]+$/',$dbname))throw new RuntimeException('Invalid database name in config.');
 $server->exec('CREATE DATABASE IF NOT EXISTS `'.$dbname.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
 $pdo=db();
 $check=(bool)$pdo->query("SHOW TABLES LIKE 'admins'")->fetchColumn();
 if($check){$done=(int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn()>0;}
} catch(Throwable $e) { $err='Database setup failed. Check MySQL running and config/database.php: '.$e->getMessage(); }
if(($_SERVER['REQUEST_METHOD']??'')==='POST'&&!$done&&!$err){
 try {
  if(!hash_equals(csrf(),(string)($_POST['csrf']??'')))throw new RuntimeException('Security token expired.');
  if($config['install_key'] && !hash_equals($config['install_key'],(string)($_POST['install_key']??'')))throw new RuntimeException('Invalid installation key.');
  $user=trim((string)($_POST['username']??''));$pass=(string)($_POST['password']??'');
  if(!preg_match('/^[a-zA-Z0-9_]{4,50}$/',$user))throw new RuntimeException('Username: 4-50 letters/numbers.');
  if(strlen($pass)<12)throw new RuntimeException('Choose a password of at least 12 characters.');
  $sql=file_get_contents(__DIR__.'/schema.sql');
  foreach(explode(';',$sql) as $statement){$statement=trim($statement);if($statement)db()->exec($statement);}
  db()->beginTransaction();
  $count=(int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
  if($count)throw new RuntimeException('Already installed.');
  exec_sql('INSERT INTO admins(username,password_hash) VALUES (?,?)',[$user,password_hash($pass,PASSWORD_DEFAULT)]);
  $seed=[
   ['phone-accessories','إكسسوارات الهواتف','Accessoires téléphone','Phone accessories','smartphone',1],
   ['car-accessories','إكسسوارات السيارات','Accessoires auto','Car accessories','car',2],
   ['men-fashion','ملابس رجالية','Mode homme','Men’s fashion','shirt',3],
   ['women-fashion','ملابس نسائية','Mode femme','Women’s fashion','sparkles',4],
   ['kids-fashion','ملابس الأطفال','Mode enfant','Kids fashion','baby',5],
   ['home-kitchen','أواني المطبخ والمنزل','Maison et cuisine','Home & kitchen','cooking-pot',6]
  ];
  foreach($seed as $c)exec_sql('INSERT INTO categories(slug,name_ar,name_fr,name_en,icon,sort_order) VALUES (?,?,?,?,?,?)',$c);
  $samples=[
   [1,'Coque premium MagSafe','غطاء هاتف مغناطيسي فاخر','Premium magnetic phone case',129,169,36,'case'],
   [1,'Écouteurs sans fil','سماعات لاسلكية','Wireless earbuds',249,329,24,'earbuds'],
   [2,'Support téléphone voiture','حامل هاتف للسيارة','Car phone holder',89,129,42,'holder'],
   [2,'Kit nettoyage voiture','طقم تنظيف السيارة','Car cleaning kit',159,199,30,'car-kit'],
   [3,'Hoodie Essential','هودي رجالي عصري','Men essential hoodie',259,349,28,'hoodie'],
   [3,'Sneakers Street','حذاء رياضي رجالي','Men street sneakers',349,459,17,'sneakers'],
   [4,'Sac minimal chic','حقيبة نسائية أنيقة','Minimal chic handbag',299,399,19,'bag'],
   [4,'Ensemble casual femme','طقم نسائي عصري','Women casual set',319,399,22,'outfit'],
   [5,'Set bébé confort','طقم أطفال مريح','Kids comfy set',189,239,31,'baby'],
   [5,'Mini sac enfant','حقيبة أطفال صغيرة','Kids mini backpack',139,179,27,'backpack'],
   [6,'Set poêles antiadhésives','طقم مقالي غير لاصقة','Non-stick cookware set',399,499,20,'pans'],
   [6,'Organiseur de cuisine','منظم أدوات المطبخ','Kitchen organizer',169,219,25,'organizer']
  ];
  foreach($samples as $i=>$s){
    exec_sql('INSERT INTO products(category_id,slug,name_ar,name_fr,name_en,description_ar,description_fr,description_en,price,compare_at_price,stock,image,featured) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',[$s[0],$s[7].'-'.$i,$s[2],$s[1],$s[3],$s[2].'، جودة ممتازة وتوصيل لجميع المدن.',$s[1].' · Qualité sélectionnée, livraison partout au Maroc.',$s[3].' · Curated quality, Morocco-wide delivery.',$s[4],$s[5],$s[6],'assets/img/'.$s[7].'.svg',$i<8?1:0]);
    $prodId=(int)$pdo->lastInsertId();
    if(in_array($s[0],[3,4,5],true)){
     foreach(['S','M','L','XL'] as $size){
      foreach(['Noir','Beige'] as $color){
       exec_sql('INSERT INTO product_variants(product_id,size,color,sku,price_adjustment,stock) VALUES (?,?,?,?,?,?)',[$prodId,$size,$color,'NV-'.$prodId.'-'.$size.'-'.($color==='Noir'?'BK':'BG'),0,5]);
      }
     }
    }
  }
  $defaults=[
   'store_name'=>'NOVA MARKET','tagline_ar'=>'كل ما تحتاج في مكان واحد','tagline_fr'=>'Toutes vos envies, une seule adresse','tagline_en'=>'Everything you love. One store.',
   'announcement_ar'=>'توصيل مجاني للطلبات فوق 399 درهم','announcement_fr'=>'Livraison offerte dès 399 MAD','announcement_en'=>'FREE DELIVERY OVER 399 MAD',
   'support_whatsapp'=>'','support_email'=>'','instagram'=>'','facebook'=>'','shipping_fee'=>'35', 'free_shipping_above'=>'399',
   'telegram_token'=>'','telegram_chat_id'=>'','telegram_orders'=>'1','telegram_visits'=>'0','whatsapp_orders'=>'0',
   'wa_cloud_token'=>'','wa_phone_id'=>'','wa_notify_number'=>'','wa_template'=>'nova_new_order','wa_template_lang'=>'en_US',
   'accent'=>'#c9fa6d','hero_title'=>'','hero_subtitle'=>'','maintenance'=>'0', 'min_order'=>'0'
  ];
  foreach($defaults as $key=>$val)exec_sql('INSERT INTO settings(name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE name=name',[$key,(string)$val]);
  db()->commit();
  $done=true;
 }catch(Throwable $e){if(isset($pdo) && $pdo->inTransaction())$pdo->rollBack();$err=$e->getMessage();}
}
?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>NOVA Setup</title><style>*{box-sizing:border-box}body{background:#0b1b28;color:#edf6fa;font:16px system-ui;margin:0;display:grid;min-height:100vh;place-items:center}main{width:min(530px,92vw);padding:42px;background:#142839;border:1px solid #325061;border-radius:24px}h1{font-size:32px;margin:0 0 14px}small{color:#98adba}input{width:100%;padding:16px;margin:10px 0 18px;border:1px solid #3e5c69;border-radius:12px;background:#0e2030;color:white;font-size:17px}button{background:#c9fa6d;color:#102332;border:0;padding:16px 23px;border-radius:12px;font-weight:800;width:100%;cursor:pointer}.error{color:#ffb8b8}a{color:#c9fa6d}</style></head><body><main><h1>✦ NOVA MARKET</h1><p>Initial setup · PHP + MySQL</p><?php if($err): ?><p class="error"><?=e($err)?></p><?php endif; ?><?php if($done):?><h2>✅ Store installed</h2><p>Installation is locked because an admin account exists.</p><p><a href="control/index.php">Open control center</a> · <a href="index.php">View store</a></p><p><strong>Delete install.php from your server now.</strong></p><?php else: ?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><label>Admin username</label><input name="username" value="farid123" required autocomplete="username"><label>Admin password</label><input name="password" type="password" placeholder="Enter your chosen password" minlength="12" required autocomplete="new-password"><?php if($config['install_key']):?><label>Installation key</label><input name="install_key" type="password" required><?php endif; ?><button>Install store + sample catalogue</button></form><small>Run locally before publishing. The password is stored as a one-way hash, never as readable text.</small><?php endif; ?></main></body></html>
