<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';
if(!admin_user())json_error('Unauthorized',401);
header('Cache-Control: no-store');
$after=max(0,(int)($_GET['after']??0));$rows=all('SELECT id,order_number,customer_name,city,total FROM orders WHERE id>? ORDER BY id ASC LIMIT 20',[$after]);
json_ok(['items'=>$rows,'new_count'=>(int)(one('SELECT COUNT(*) n FROM orders WHERE status="new"')['n']??0),'last_id'=>(int)(one('SELECT MAX(id) n FROM orders')['n']??0)]);
