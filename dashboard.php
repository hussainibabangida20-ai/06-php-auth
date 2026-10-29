<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    redirect('register.php');
}

$success = flash('success');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<main class="auth-page">
    <section class="auth-card" aria-labelledby="dashboard-title">
        <?php if (is_string($success) && $success !== ''): ?>
            <div class="alert alert-success" role="status"><?= e($success) ?></div>
        <?php endif; ?>
        <div class="auth-heading">
            <p class="eyebrow">Account</p>
            <h1 id="dashboard-title">Welcome, <?= e((string) ($_SESSION['user_name'] ?? '')) ?></h1>
            <p>You are signed in.</p>
        </div>
    </section>
</main>
</body>
</html>
