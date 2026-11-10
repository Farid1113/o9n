<?php
declare(strict_types=1);
function telegram_send(string $msg): bool {
    $bot=setting('telegram_token');$chat=setting('telegram_chat_id');if(!$bot||!$chat||!function_exists('curl_init'))return false;
    if(!preg_match('/^\d+:[A-Za-z0-9_-]{20,}$/',$bot))return false;
    $ch=curl_init('https://api.telegram.org/bot'.$bot.'/sendMessage');
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query(['chat_id'=>$chat,'text'=>$msg]),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>5]);
    $resp=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
    if($code!==200)error_log('NOVA Telegram HTTP '.$code.' '.substr((string)$resp,0,120));
    return $code===200;
}
function whatsapp_cloud_send(string $name,string $phone,string $order,string $city): bool {
    // Business-initiated WhatsApp alerts require an approved Meta message template.
    $token=setting('wa_cloud_token');$id=setting('wa_phone_id');$to=preg_replace('/\D+/','',setting('wa_notify_number'));$template=setting('wa_template','nova_new_order');
    if(!$token||!$id||!$to||!function_exists('curl_init'))return false;
    $payload=['messaging_product'=>'whatsapp','to'=>$to,'type'=>'template','template'=>['name'=>$template,'language'=>['code'=>setting('wa_template_lang','en_US')],'components'=>[['type'=>'body','parameters'=>array_map(static fn($v)=>['type'=>'text','text'=>$v],[$name,$phone,$order,$city])]]]];
    $ch=curl_init('https://graph.facebook.com/v22.0/'.rawurlencode($id).'/messages');
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Content-Type: application/json'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>5]);
    $resp=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
    if($code<200||$code>=300)error_log('NOVA WhatsApp HTTP '.$code.' '.substr((string)$resp,0,200));
    return $code>=200&&$code<300;
}
function notify_new_order(array $o): void {
    $msg="🛍 NOVA MARKET - NEW ORDER\nOrder: {$o['order_number']}\nName: {$o['customer_name']}\nPhone: {$o['customer_phone']}\nCity: {$o['city']}\nTotal: ".price($o['total'])."\nPayment: CASH ON DELIVERY";
    if(setting('telegram_orders','1')==='1')telegram_send($msg);
    if(setting('whatsapp_orders','0')==='1')whatsapp_cloud_send($o['customer_name'],$o['customer_phone'],$o['order_number'],$o['city']);
}
function track_visit(): void {
    if(!empty($_SESSION['visit_logged']))return;
    $_SESSION['visit_logged']=1;
    try {
      $fingerprint=hash('sha256',($_SERVER['REMOTE_ADDR']??'unknown').':'.($_SERVER['HTTP_USER_AGENT']??''));
      $existing=one('SELECT id, last_seen FROM visitors WHERE fingerprint=?',[$fingerprint]);
      if($existing){exec_sql('UPDATE visitors SET last_seen=NOW(),views=views+1 WHERE id=?',[$existing['id']]);return;}
      exec_sql('INSERT INTO visitors (fingerprint) VALUES (?)',[$fingerprint]);
      if(setting('telegram_visits','0')==='1')telegram_send('👀 New NOVA MARKET visitor · '.date('Y-m-d H:i').' · page: store');
    }catch(Throwable $e){error_log('Visitor counter failed: '.$e->getMessage());}
}
