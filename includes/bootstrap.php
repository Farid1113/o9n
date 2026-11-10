<?php
declare(strict_types=1);
const ROOT = __DIR__ . '/..';
$config = require ROOT . '/config/database.php';
$isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (!$isSecure && (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')) $isSecure = true;
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('NOVA_MARKET_SID');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$isSecure,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
$GLOBALS['csp_nonce'] = base64_encode(random_bytes(16));
header('Content-Security-Policy: default-src \'self\'; img-src \'self\' https: data:; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com data:; script-src \'self\' \'nonce-'. $GLOBALS['csp_nonce'] .'\'; connect-src \'self\'; object-src \'none\'; frame-ancestors \'none\'; base-uri \'self\'; form-action \'self\'');
if ($isSecure) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
require_once ROOT . '/includes/functions.php';
require_once ROOT . '/includes/notifications.php';
