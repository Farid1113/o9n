<?php
declare(strict_types=1);
function db(): PDO {
    static $pdo = null;
    global $config;
    if ($pdo !== null) return $pdo;
    try {
        $pdo = new PDO('mysql:host='.$config['host'].';port='.$config['port'].';dbname='.$config['database'].';charset=utf8mb4', $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log('NOVA DB connection failed: '.$e->getMessage());
        http_response_code(503);
        exit('<!doctype html><html><meta charset="utf-8"><title>NOVA setup</title><body style="font:17px system-ui;padding:60px;max-width:700px;margin:auto"><h1>NOVA MARKET</h1><p>Database unavailable. Check <code>config/database.php</code>, then open <a href="install.php">install.php</a> to install.</p></body></html>');
    }
}
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path=''): string {
    global $config;
    if ($config['base_url']) return $config['base_url'].'/'.ltrim($path,'/');
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME']??'/index.php'));
    $dir = str_replace('\\','/',dirname($script));
    if (basename($dir)==='control') $dir=dirname($dir);
    $prefix = $dir==='/'||$dir==='.' ? '' : rtrim($dir,'/');
    return $prefix.'/'.ltrim($path,'/');
}
function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_check(): void { if (!is_string($_POST['csrf'] ?? null) || !hash_equals(csrf(), $_POST['csrf'])) json_error('Security token expired. Refresh the page.', 419); }
function json_ok(array $data=[]): never { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>true]+$data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit; }
function json_error(string $message, int $code=400): never { http_response_code($code);header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>false,'error'=>$message],JSON_UNESCAPED_UNICODE);exit; }
function one(string $sql,array $args=[]): ?array { $s=db()->prepare($sql);$s->execute($args);$r=$s->fetch();return $r?:null; }
function all(string $sql,array $args=[]): array { $s=db()->prepare($sql);$s->execute($args);return $s->fetchAll(); }
function exec_sql(string $sql,array $args=[]): int { $s=db()->prepare($sql);$s->execute($args);return $s->rowCount(); }
function setting(string $name,string $default=''): string { static $cache=[];if(!array_key_exists($name,$cache)){try{$r=one('SELECT value FROM settings WHERE name=?',[$name]);$cache[$name]=$r['value']??$default;}catch(Throwable $e){$cache[$name]=$default;}}return $cache[$name]; }
function t(string $key): string {
    $lang = lang();
    $a = [
       'ar'=>['home'=>'الرئيسية','categories'=>'التصنيفات','offers'=>'العروض','search'=>'شنو كتقلب عليه؟','cart'=>'السلة','all'=>'جميع المنتجات','shop'=>'تسوق الآن','hero1'=>'تسوق كل ما تحتاج','hero2'=>'في مكان واحد.','hero3'=>'إكسسوارات، أزياء، منزل، وأكثر. اختيارات مميزة بأسعار مغرية.','featured'=>'مختارة ليك','new'=>'وصل حديثاً','add'=>'أضف للسلة','buy'=>'اشري دابا','details'=>'التفاصيل','discount'=>'تخفيض','shipping'=>'التوصيل','cod'=>'الدفع عند الاستلام','secure'=>'طلب آمن','returns'=>'خدمة الزبناء','subtotal'=>'المجموع','checkout'=>'تأكيد الطلب','name'=>'الاسم الكامل','phone'=>'رقم الهاتف','city'=>'المدينة','address'=>'العنوان (اختياري)','notes'=>'ملاحظات (اختياري)','place_order'=>'تأكيد الطلب الآن','free'=>'مجاني','empty'=>'السلة فارغة','size'=>'المقاس','color'=>'اللون','quantity'=>'الكمية','apply'=>'تطبيق','coupon'=>'كود التخفيض','contact'=>'تواصل معنا','about'=>'متجر متنوع يوفر لكم أفضل الاختيارات اليومية.','success'=>'توصلنا بطلبك! غادي نتاصلو بك فالرقم اللي دخلتي باش نأكدوه.','thanks'=>'شكراً على الثقة ديالك','back'=>'الرجوع للمتجر','stock'=>'متوفر','out'=>'نفد المخزون','filter'=>'تصفية','sort'=>'ترتيب','price'=>'الثمن','newest'=>'الأحدث','cheap'=>'الأرخص','expensive'=>'الأغلى','sale'=>'التخفيضات','phoneacc'=>'إكسسوارات الهاتف','caracc'=>'إكسسوارات السيارات','men'=>'ملابس الرجال','women'=>'ملابس النساء','kids'=>'ملابس الأطفال','kitchen'=>'المطبخ والدار','track'=>'تتبع طلبي','track_title'=>'تتبع حالة الطلب','track_btn'=>'بحث','order_number'=>'رقم الطلب','wishlist'=>'المفضلة'],
       'fr'=>['home'=>'Accueil','categories'=>'Catégories','offers'=>'Promotions','search'=>'Que recherchez-vous ?','cart'=>'Panier','all'=>'Tous les produits','shop'=>'Découvrir','hero1'=>'Toutes vos envies.','hero2'=>'Une seule adresse.','hero3'=>'Tech, mode, auto et maison. Une sélection unique, à prix doux.','featured'=>'Nos coups de cœur','new'=>'Nouveautés','add'=>'Ajouter au panier','buy'=>'Acheter maintenant','details'=>'Détails','discount'=>'Réduction','shipping'=>'Livraison','cod'=>'Paiement à la livraison','secure'=>'Commande sécurisée','returns'=>'Service client','subtotal'=>'Sous-total','checkout'=>'Commander','name'=>'Nom complet','phone'=>'Téléphone','city'=>'Ville','address'=>'Adresse (facultatif)','notes'=>'Notes (facultatif)','place_order'=>'Valider ma commande','free'=>'Gratuit','empty'=>'Votre panier est vide','size'=>'Taille','color'=>'Couleur','quantity'=>'Quantité','apply'=>'Appliquer','coupon'=>'Code promo','contact'=>'Contactez-nous','about'=>'Des essentiels soigneusement sélectionnés pour votre quotidien.','success'=>'Commande reçue ! Nous vous appellerons pour confirmer sur le numéro fourni.','thanks'=>'Merci pour votre confiance','back'=>'Retour à la boutique','stock'=>'En stock','out'=>'Rupture','filter'=>'Filtres','sort'=>'Trier','price'=>'Prix','newest'=>'Récents','cheap'=>'Prix croissant','expensive'=>'Prix décroissant','sale'=>'Promotions','phoneacc'=>'Accessoires téléphone','caracc'=>'Accessoires auto','men'=>'Mode homme','women'=>'Mode femme','kids'=>'Mode enfant','kitchen'=>'Maison et cuisine','track'=>'Suivi commande','track_title'=>'Suivi de commande','track_btn'=>'Rechercher','order_number'=>'N° commande','wishlist'=>'Favoris'],
       'en'=>['home'=>'Home','categories'=>'Categories','offers'=>'Offers','search'=>'What are you looking for?','cart'=>'Cart','all'=>'All products','shop'=>'Shop now','hero1'=>'Everything you love.','hero2'=>'One extraordinary store.','hero3'=>'Tech, fashion, auto and home — thoughtful picks, wonderful prices.','featured'=>'Handpicked for you','new'=>'New arrivals','add'=>'Add to cart','buy'=>'Buy now','details'=>'Details','discount'=>'Save','shipping'=>'Delivery','cod'=>'Cash on delivery','secure'=>'Secure ordering','returns'=>'Customer care','subtotal'=>'Subtotal','checkout'=>'Checkout','name'=>'Full name','phone'=>'Phone number','city'=>'City','address'=>'Address (optional)','notes'=>'Notes (optional)','place_order'=>'Place order','free'=>'Free','empty'=>'Your cart is empty','size'=>'Size','color'=>'Color','quantity'=>'Quantity','apply'=>'Apply','coupon'=>'Coupon code','contact'=>'Contact us','about'=>'Curated everyday essentials at prices worth celebrating.','success'=>'Order received! We’ll contact you at your phone number to confirm it.','thanks'=>'Thank you for shopping','back'=>'Back to store','stock'=>'In stock','out'=>'Out of stock','filter'=>'Filter','sort'=>'Sort','price'=>'Price','newest'=>'Newest','cheap'=>'Price low to high','expensive'=>'Price high to low','sale'=>'Sale','phoneacc'=>'Phone accessories','caracc'=>'Car accessories','men'=>'Men’s fashion','women'=>'Women’s fashion','kids'=>'Kids fashion','kitchen'=>'Home & kitchen','track'=>'Track order','track_title'=>'Track your order','track_btn'=>'Search','order_number'=>'Order number','wishlist'=>'Wishlist']
    ];
    return $a[$lang][$key] ?? $a['fr'][$key] ?? $key;
}
function lang(): string { $v=$_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';if(!in_array($v,['ar','fr','en'],true))$v='ar';$_SESSION['lang']=$v;return $v; }
function localized(array $a,string $col='name'): string { $l=lang();return $a[$col.'_'.$l] ?: ($a[$col.'_fr'] ?: $a[$col.'_en'] ?? ''); }
function price(mixed $n): string {return number_format((float)$n,2,'.',' ') . ' MAD';}
function discount(array $p): int { $was=(float)$p['compare_at_price'];$now=(float)$p['price'];return $was>$now&&$now>0 ? (int)round(100*(1-$now/$was)) : 0; }
function image_url(?string $v, string $fallback='assets/img/placeholder.svg'): string {
    if (!$v) return url($fallback);
    if (preg_match('#^uploads/products/[A-Za-z0-9._-]+$#',$v)) return url($v);
    if (preg_match('#^assets/img/[A-Za-z0-9._-]+$#',$v)) return url($v);
    return url($fallback);
}
function admin_user(): ?array {return !empty($_SESSION['admin_id']) ? one('SELECT id,username FROM admins WHERE id=? AND active=1',[(int)$_SESSION['admin_id']]) : null;}
function require_admin(): array { $admin=admin_user();if(!$admin){header('Location: '.url('control/index.php'));exit;}return $admin; }
function audit(string $action,string $detail=''): void {if(empty($_SESSION['admin_id']))return;exec_sql('INSERT INTO audit_logs (admin_id,action,detail) VALUES (?,?,?)',[(int)$_SESSION['admin_id'],$action,substr($detail,0,400)]);}
function valid_phone(string $input): ?string { $digits=preg_replace('/[^0-9+]/','',$input);if(!preg_match('/^\+?[0-9]{9,15}$/',$digits))return null;return $digits; }
function input_str(string $key,int $max=250): string {return trim(mb_safe_sub((string)($_POST[$key]??''),$max));}
function mb_safe_sub(string $v,int $n): string{return function_exists('mb_substr')?mb_substr($v,0,$n):substr($v,0,$n);}
function only_post(): void {if(($_SERVER['REQUEST_METHOD']??'') !== 'POST') json_error('Method not allowed',405);csrf_check();}
function save_uploaded_image(string $field,?string $existing=null): ?string {
    if (empty($_FILES[$field]['name'])) return $existing;
    $f=$_FILES[$field];if($f['error']!==UPLOAD_ERR_OK||$f['size']>4*1024*1024)throw new RuntimeException('Image must be under 4MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$mime]??null;
    if(!$ext || !getimagesize($f['tmp_name']))throw new RuntimeException('Only JPEG, PNG, WEBP, GIF images allowed.');
    $name=bin2hex(random_bytes(16)).'.'.$ext;$dest=ROOT.'/uploads/products/'.$name;
    if(!move_uploaded_file($f['tmp_name'],$dest))throw new RuntimeException('Cannot save image. Check uploads folder permissions.');
    return 'uploads/products/'.$name;
}
function status_label(string $s): string {return ['new'=>'New','confirmed'=>'Confirmed','preparing'=>'Preparing','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'][$s]??$s;}
