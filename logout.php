<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

if (!csrf_verify()) {
    http_response_code(400);
    exit('The form expired or could not be verified. Please try again.');
}

auth_reset_session('You have been signed out.');
redirect('login.php');
