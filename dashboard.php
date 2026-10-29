<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$user = current_user();
if ($user === null) {
    require_login();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    http_response_code(405);
    header('Allow: GET');
    exit('Method not allowed.');
}

$success = flash('success');
$lastLogin = $user['last_login_at'] === null
    ? 'Not available'
    : (new DateTimeImmutable((string) $user['last_login_at']))->format('M j, Y \a\t g:i A');
$createdAt = (new DateTimeImmutable((string) $user['created_at']))->format('M j, Y');
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
<?php require __DIR__ . '/src/views/navbar.php'; ?>
<main class="auth-page">
    <section class="auth-card" aria-labelledby="dashboard-title">
        <?php if (is_string($success) && $success !== ''): ?>
            <div class="alert alert-success" role="status"><?= e($success) ?></div>
        <?php endif; ?>
        <div class="auth-heading">
            <p class="eyebrow">Account</p>
            <h1 id="dashboard-title">Welcome, <?= e((string) $user['name']) ?></h1>
            <p>You are signed in.</p>
        </div>
        <dl class="account-details">
            <div>
                <dt>Email address</dt>
                <dd><?= e((string) $user['email']) ?></dd>
            </div>
            <div>
                <dt>Role</dt>
                <dd><?= e((string) $user['role']) ?></dd>
            </div>
            <div>
                <dt>Member since</dt>
                <dd><?= e($createdAt) ?></dd>
            </div>
            <div>
                <dt>Last login</dt>
                <dd><?= e($lastLogin) ?></dd>
            </div>
        </dl>
        <form method="post" action="logout.php">
            <?= csrf_field() ?>
            <button type="submit">Sign out</button>
        </form>
    </section>
</main>
</body>
</html>
