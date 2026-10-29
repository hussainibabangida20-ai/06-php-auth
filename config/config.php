<?php
declare(strict_types=1);

define('DB_HOST', getenv('SWD_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('SWD_DB_PORT') ?: '3306');
define('DB_NAME', getenv('SWD_DB_NAME') ?: 'swd_auth');
define('DB_USER', getenv('SWD_DB_USER') ?: 'root');
define('DB_PASS', getenv('SWD_DB_PASS') ?: '');
