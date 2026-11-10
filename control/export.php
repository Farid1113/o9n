<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';require_admin();
$type=(string)($_GET['type']??'orders');if(!in_array($type,['orders','customers'],true)){http_response_code(404);exit;}
$rows=$type==='orders'?all('SELECT order_number,customer_name,customer_phone,city,address,subtotal,discount_amount,shipping_fee,total,status,created_at FROM orders ORDER BY id DESC'):all('SELECT customer_name,customer_phone,MAX(city) city,COUNT(*) orders, SUM(CASE WHEN status<>"cancelled" THEN total ELSE 0 END) spent,MAX(created_at) last_order FROM orders GROUP BY customer_phone,customer_name ORDER BY last_order DESC');
header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="nova-'.$type.'-'.date('Y-m-d').'.csv"');header('Cache-Control: no-store');
$f=fopen('php://output','w');fwrite($f,"\xEF\xBB\xBF");
if($rows)fputcsv($f,array_keys($rows[0]));
foreach($rows as $r){$values=[];foreach($r as $v){$v=(string)$v;if(preg_match('/^[\s]*[=+@\-]/',$v))$v="'".$v;$values[]=$v;}fputcsv($f,$values);}fclose($f);
