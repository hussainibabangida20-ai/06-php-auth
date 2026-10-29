<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/db.php';

$email = $argv[1] ?? '';
if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 160) {
    fwrite(STDERR, "Usage: php promote-admin.php user@example.com\n");
    exit(2);
}

$findUser = db()->prepare('SELECT id, role FROM users WHERE email = :email LIMIT 1');
$findUser->execute(['email' => $email]);
$user = $findUser->fetch();
if ($user === false) {
    fwrite(STDERR, "No user found with that email address.\n");
    exit(1);
}

if ($user['role'] === 'admin') {
    fwrite(STDOUT, "That user is already an admin.\n");
    exit(0);
}

$promote = db()->prepare('UPDATE users SET role = :role WHERE id = :id');
$promote->execute(['role' => 'admin', 'id' => (int) $user['id']]);
fwrite(STDOUT, "Admin access granted to {$email}.\n");
