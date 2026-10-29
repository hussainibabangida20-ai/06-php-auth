<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/csrf.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = isset($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== ''
        && strtolower($_SERVER['HTTPS']) !== 'off'
        && $_SERVER['HTTPS'] !== '0';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('SWDSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
