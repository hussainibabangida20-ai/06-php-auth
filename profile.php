<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

require_login();
$user = current_user();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_verify()) {
        http_response_code(400);
        exit('The form expired or could not be verified. Please try again.');
    }

    $action = $_POST['action'] ?? null;
    if ($action === 'profile') {
        $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
        $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
        $errors = [];

        if ($name === '') {
            $errors[] = 'Please enter your name.';
        } else {
            $nameLength = preg_match_all('/./us', $name);
            if ($nameLength === false) {
                $errors[] = 'Please enter a name using valid text.';
            } elseif ($nameLength > 100) {
                $errors[] = 'Your name must be 100 characters or fewer.';
            }
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 160) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            $statement = db()->prepare(
                'SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1'
            );
            $statement->execute(['email' => $email, 'id' => $user['id']]);
            if ($statement->fetch() !== false) {
                $errors[] = 'An account with that email address already exists.';
            }
        }

        if ($errors !== []) {
            $_SESSION['_old'] = ['name' => $name, 'email' => $email];
            flash('profile_errors', $errors);
            redirect('profile.php');
        }

        try {
            $statement = db()->prepare(
                'UPDATE users SET name = :name, email = :email WHERE id = :id'
            );
            $statement->execute([
                'name' => $name,
                'email' => $email,
                'id' => $user['id'],
            ]);
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) !== 1062) {
                throw $exception;
            }

            $_SESSION['_old'] = ['name' => $name, 'email' => $email];
            flash('profile_errors', ['An account with that email address already exists.']);
            redirect('profile.php');
        }

        flash('success', 'Your profile has been updated.');
        redirect('profile.php');
    }

    if ($action === 'password') {
        $currentPassword = is_string($_POST['current_password'] ?? null)
            ? $_POST['current_password']
            : '';
        $newPassword = is_string($_POST['new_password'] ?? null)
            ? $_POST['new_password']
            : '';
        $confirmPassword = is_string($_POST['confirm_password'] ?? null)
            ? $_POST['confirm_password']
            : '';

        $statement = db()->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $user['id']]);
        $passwordHash = $statement->fetchColumn();
        $errors = [];

        if (!is_string($passwordHash) || !password_verify($currentPassword, $passwordHash)) {
            $errors[] = 'Your current password is incorrect.';
        }

        $passwordLength = preg_match_all('/./us', $newPassword);
        if ($passwordLength === false || $passwordLength < 10) {
            $errors[] = 'Your new password must be at least 10 characters long.';
        }
        if (preg_match('/\p{L}/u', $newPassword) !== 1) {
            $errors[] = 'Your new password must contain at least one letter.';
        }
        if (preg_match('/\p{N}/u', $newPassword) !== 1) {
            $errors[] = 'Your new password must contain at least one number.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'The new passwords do not match.';
        }

        if ($errors !== []) {
            flash('password_errors', $errors);
            redirect('profile.php');
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $statement = db()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $statement->execute([
            'password_hash' => $newPasswordHash,
            'id' => $user['id'],
        ]);

        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Unable to renew the session after changing the password.');
        }

        unset($_SESSION['_csrf_token']);
        flash('success', 'Your password has been changed.');
        redirect('profile.php');
    }

    http_response_code(400);
    exit('The requested profile action is invalid.');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Method not allowed.');
}

$profileErrors = flash('profile_errors');
$passwordErrors = flash('password_errors');
$success = flash('success');
if (!is_array($profileErrors)) {
    $profileErrors = [];
}
if (!is_array($passwordErrors)) {
    $passwordErrors = [];
}
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your profile</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<?php require __DIR__ . '/src/views/navbar.php'; ?>
<main class="auth-page">
    <section class="auth-card profile-card" aria-labelledby="profile-title">
        <div class="auth-heading">
            <p class="eyebrow">Account settings</p>
            <h1 id="profile-title">Your profile</h1>
            <p>Update your account details or change your password.</p>
        </div>

        <?php if (is_string($success) && $success !== ''): ?>
            <div class="alert alert-success" role="status"><?= e($success) ?></div>
        <?php endif; ?>

        <h2>Profile details</h2>
        <?php if ($profileErrors !== []): ?>
            <div class="alert alert-error" role="alert">
                <ul>
                    <?php foreach ($profileErrors as $error): ?>
                        <li><?= e((string) $error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="profile.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">
            <label for="name">Name</label>
            <input
                id="name"
                name="name"
                type="text"
                autocomplete="name"
                maxlength="100"
                value="<?= old('name', (string) $user['name']) ?>"
                required
            >
            <label for="email">Email address</label>
            <input
                id="email"
                name="email"
                type="email"
                autocomplete="email"
                maxlength="160"
                value="<?= old('email', (string) $user['email']) ?>"
                required
            >
            <button type="submit">Save profile</button>
        </form>

        <hr>
        <h2>Change password</h2>
        <?php if ($passwordErrors !== []): ?>
            <div class="alert alert-error" role="alert">
                <ul>
                    <?php foreach ($passwordErrors as $error): ?>
                        <li><?= e((string) $error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="profile.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <label for="current-password">Current password</label>
            <input
                id="current-password"
                name="current_password"
                type="password"
                autocomplete="current-password"
                required
            >
            <label for="new-password">New password</label>
            <input
                id="new-password"
                name="new_password"
                type="password"
                autocomplete="new-password"
                minlength="10"
                required
            >
            <p class="field-hint">At least 10 characters, including a letter and a number.</p>
            <label for="confirm-password">Confirm new password</label>
            <input
                id="confirm-password"
                name="confirm_password"
                type="password"
                autocomplete="new-password"
                minlength="10"
                required
            >
            <button type="submit">Change password</button>
        </form>
    </section>
</main>
</body>
</html>
