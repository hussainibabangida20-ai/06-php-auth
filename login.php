<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

require_guest();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_verify()) {
        http_response_code(400);
        exit('The form expired or could not be verified. Please try again.');
    }

    $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $user = false;

    if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 160) {
        $statement = db()->prepare(
            'SELECT id, name, email, role, password_hash FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();
    }

    if ($user === false || !password_verify($password, (string) $user['password_hash'])) {
        $_SESSION['_old'] = ['email' => $email];
        flash('error', 'Invalid email or password');
        redirect('login.php');
    }

    if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
        $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);
        $statement = db()->prepare(
            'UPDATE users SET password_hash = :password_hash, last_login_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute([
            'password_hash' => $newPasswordHash,
            'id' => (int) $user['id'],
        ]);
    } else {
        $statement = db()->prepare(
            'UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute(['id' => (int) $user['id']]);
    }

    if (!session_regenerate_id(true)) {
        throw new RuntimeException('Unable to renew the session after login.');
    }

    $intendedUrl = safe_intended_url(
        is_string($_SESSION['_intended_url'] ?? null) ? $_SESSION['_intended_url'] : null
    );
    $_SESSION = [
        'user_id' => (int) $user['id'],
        'role' => (string) $user['role'],
        '_last_activity' => time(),
    ];

    redirect($intendedUrl ?? 'dashboard.php');
}

$error = flash('error');
$notice = flash('notice');
$success = flash('success');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<main class="auth-page">
    <section class="auth-card" aria-labelledby="login-title">
        <div class="auth-heading">
            <p class="eyebrow">Welcome back</p>
            <h1 id="login-title">Sign in</h1>
            <p>Enter your email and password to continue.</p>
        </div>

        <?php if (is_string($error) && $error !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if (is_string($notice) && $notice !== ''): ?>
            <div class="alert alert-success" role="status"><?= e($notice) ?></div>
        <?php endif; ?>
        <?php if (is_string($success) && $success !== ''): ?>
            <div class="alert alert-success" role="status"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <?= csrf_field() ?>
            <label for="email">Email address</label>
            <input
                id="email"
                name="email"
                type="email"
                autocomplete="email"
                maxlength="160"
                value="<?= old('email') ?>"
                required
            >

            <label for="password">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Sign in</button>
        </form>

        <p class="auth-footer">Don't have an account? <a href="register.php">Create one</a></p>
    </section>
</main>
</body>
</html>
