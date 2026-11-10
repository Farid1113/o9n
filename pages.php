<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
$l=lang();$doc=(string)($_GET['doc']??'privacy');
if(!in_array($doc,['privacy','shipping','returns','terms'],true)){$doc='privacy';}
$terms=[
 'privacy'=>[
  'ar'=>['الخصوصية','كنجمعو غير المعلومات اللي كتدخل أثناء الطلب (الاسم، الهاتف، المدينة، العنوان إلا دخلتيه) باش نأكدوا الطلب ونوصلوه. كنستعملو معرف تقني مجهول لتقدير الزوار وما كنبيعوش معلومات الزبناء. التواصل كيمكن يدوز عبر Telegram أو WhatsApp Business حسب الإعدادات ديال المتجر. باش تطلب التصحيح أو الحذف تواصل مع إدارة المتجر.'],
  'fr'=>['Confidentialité','Nous traitons les informations communiquées à la commande (nom, téléphone, ville, adresse facultative) pour confirmer et livrer les achats. Un identifiant technique pseudonyme sert au comptage des visites. Les notifications de commande peuvent être transmises aux services Telegram ou WhatsApp Business si configurés. Contactez le commerçant pour exercer vos demandes d’accès, de correction ou de suppression.'],
  'en'=>['Privacy','We process information provided at checkout (name, phone, city and optional address) to confirm and deliver orders. A pseudonymous technical identifier is used to estimate unique visitors. Order notifications may be relayed via configured Telegram or WhatsApp Business services. Contact the store to request access, correction or deletion of your data.']
 ],
 'shipping'=>[
  'ar'=>['التوصيل والدفع','جميع الطلبات حالياً بالدفع عند الاستلام (COD). ثمن التوصيل والمجموع النهائي كيبانو ليك قبل تأكيد الطلب. غادي نتاصلو بك باش نأكدوا المعلومات وعنوان الاستلام والمدة التقريبية.'],
  'fr'=>['Livraison et paiement','Les commandes sont réglées en espèces à la livraison (COD). Les frais de livraison et le total sont présentés avant validation. Nous vous contacterons pour confirmer les coordonnées, le lieu de livraison et les délais estimés.'],
  'en'=>['Delivery and payment','All current orders are cash on delivery (COD). Shipping costs and the total are shown before confirmation. The store will contact you to confirm delivery details and estimated timing.']
 ],
 'returns'=>[
  'ar'=>['الإرجاع والتبديل','قبل طلب الإرجاع أو التبديل تواصل مع دعم المتجر وقدم رقم الطلب وصور المنتج إلا كانت مشكلة. الشروط، الآجال وتكاليف الإرجاع خاص التاجر يحددهم ويوضحهم حسب القانون المعمول به قبل إطلاق المتجر.'],
  'fr'=>['Retours et échanges','Contactez le service client avec votre numéro de commande et, si nécessaire, des photos du produit. Le commerçant doit préciser les conditions, délais et frais de retour applicables conformément à la législation avant la mise en ligne définitive.'],
  'en'=>['Returns and exchanges','Contact store support with your order number and, where relevant, product photos. The store owner must finalize applicable return rules, deadlines and fees in accordance with local law before launch.']
 ],
 'terms'=>[
  'ar'=>['شروط البيع','المتجر كيعرض أثمنة المنتجات بالدرهم المغربي. الطلبات كتتسجل داخل الموقع وكتحتاج تأكيد هاتفي. التوفر والثمن النهائي كيتم التحقق منهم وقت إرسال الطلب. خاص صاحب المتجر يزيد الهوية القانونية وشروط البيع النهائية قبل النشر.'],
  'fr'=>['Conditions de vente','Les prix sont indiqués en dirhams marocains. Les commandes sont enregistrées sur le site, puis confirmées par téléphone. La disponibilité et le prix final sont validés par le serveur au moment de la commande. Le commerçant doit ajouter ses mentions légales et conditions définitives avant la publication.'],
  'en'=>['Terms of sale','Prices are expressed in Moroccan dirhams. Orders are submitted on-site and require telephone confirmation. Availability and final prices are checked by the server at checkout. The store owner must add legal business details and final terms before public launch.']
 ]
];
[$heading,$body]=$terms[$doc][$l];
?><!doctype html><html lang="<?=e($l)?>" dir="<?=$l==='ar'?'rtl':'ltr'?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($heading)?> | <?=e(setting('store_name','NOVA MARKET'))?></title><link rel="stylesheet" href="<?=e(url('assets/css/store.css'))?>"></head><body><div class="topline"><div class="container top-inner">✦ <?=e(setting('store_name','NOVA MARKET'))?></div></div><header class="site-header"><div class="container header-row"><a href="<?=e(url('index.php?lang='.$l))?>" class="brand"><span class="brand-mark">✳</span>NOVA<span class="brand-light">MARKET</span></a><a style="margin-inline-start:auto;font-weight:800" href="<?=e(url('index.php?lang='.$l))?>">← <?=e(t('back'))?></a></div></header><main class="container" style="min-height:60vh;max-width:920px;padding:70px 0"><div class="eyebrow">NOVA MARKET · INFORMATION</div><h1 style="font-size:clamp(32px,5vw,54px);margin:13px 0 20px"><?=e($heading)?></h1><p style="font-size:18px;line-height:2;color:#536877"><?=e($body)?></p><hr style="border:0;border-top:1px solid #eee;margin:45px 0"><p><?=e(t('contact'))?>: <?=e(setting('support_email','Contact store support'))?></p></main><footer><div class="container footer-bottom"><span>© <?=date('Y')?> NOVA MARKET</span><span><?php foreach(['privacy','shipping','returns','terms'] as $k):?><a style="margin:0 9px" href="<?=e(url('pages.php?doc='.$k.'&lang='.$l))?>"><?=e($terms[$k][$l][0])?></a><?php endforeach;?></span></div></footer></body></html>
