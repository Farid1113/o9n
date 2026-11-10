<?php
/** NOVA MARKET configuration. Edit only this file or set environment variables. */
return [
    'host' => getenv('NOVA_DB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('NOVA_DB_PORT') ?: 3306),
    'database' => getenv('NOVA_DB_NAME') ?: 'nova_market',
    'username' => getenv('NOVA_DB_USER') ?: 'root',
    'password' => getenv('NOVA_DB_PASS') ?: '',
    'base_url' => rtrim(getenv('NOVA_BASE_URL') ?: '', '/'),
    'install_key' => getenv('NOVA_INSTALL_KEY') ?: '', // optional: lock install.php with an external secret
];
