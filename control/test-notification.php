<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';
require_admin();only_post();
if(($_POST['action']??'')!=='telegram')json_error('Invalid action.');
if(!telegram_send('✳ NOVA MARKET — Test notification successful!'))json_error('Telegram failed. Verify Bot token and Chat ID (and open your bot chat).');
json_ok(['message'=>'Telegram test delivered.']);
