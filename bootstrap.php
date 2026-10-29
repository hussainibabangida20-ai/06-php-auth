<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/csrf.php';
require_once __DIR__ . '/src/auth.php';

ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('display_startup_errors', APP_DEBUG ? '1' : '0');

header_remove('X-Powered-By');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'");
header('Cache-Control: no-store, private');

$https = (isset($_SERVER['HTTPS'])
    && $_SERVER['HTTPS'] !== ''
    && strtolower($_SERVER['HTTPS']) !== 'off'
    && $_SERVER['HTTPS'] !== '0')
    || getenv('SWD_HTTPS') === '1';

if (APP_ENV !== 'development' && !$https) {
    header('Location: ' . APP_BASE_URL, true, 308);
    exit;
}

if ($https) {
    header('Strict-Transport-Security: max-age=31536000');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('SWDSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https || APP_ENV !== 'development',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

auth_enforce_idle_timeout();
