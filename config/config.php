<?php
declare(strict_types=1);

define('APP_ENV', getenv('SWD_ENV') ?: 'production');
define('APP_DEBUG', APP_ENV === 'development' && getenv('SWD_DEBUG') === '1');
define(
    'APP_BASE_URL',
    getenv('SWD_BASE_URL')
        ?: (APP_ENV === 'development' ? 'http://localhost/swd/06-php-auth' : '')
);
define('DB_HOST', getenv('SWD_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('SWD_DB_PORT') ?: '3306');
define('DB_NAME', getenv('SWD_DB_NAME') ?: 'swd_auth');
define('DB_USER', getenv('SWD_DB_USER') ?: '');
define('DB_PASS', getenv('SWD_DB_PASS') ?: '');

if (APP_ENV !== 'development') {
    $baseUrl = parse_url(APP_BASE_URL);
    if (!is_array($baseUrl)
        || ($baseUrl['scheme'] ?? null) !== 'https'
        || !isset($baseUrl['host'])
        || isset($baseUrl['user'])
        || isset($baseUrl['pass'])
        || isset($baseUrl['query'])
        || isset($baseUrl['fragment'])
    ) {
        throw new RuntimeException('Production requires a valid HTTPS SWD_BASE_URL.');
    }

    if (DB_USER === '' || DB_USER === 'root' || DB_PASS === '') {
        throw new RuntimeException('Production requires a non-root MySQL account with a password.');
    }
}
