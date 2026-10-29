<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        http_response_code(400);
        exit('The form expired or could not be verified. Please go back and try again.');
    }

    $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
    $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmPassword = is_string($_POST['confirm_password'] ?? null)
        ? $_POST['confirm_password']
        : '';
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
        $statement = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);

        if ($statement->fetch() !== false) {
            $errors[] = 'An account with that email address already exists.';
        }
    }

    $passwordLength = preg_match_all('/./us', $password);
    if ($passwordLength === false || $passwordLength < 10) {
        $errors[] = 'Your password must be at least 10 characters long.';
    }
    if (preg_match('/\p{L}/u', $password) !== 1) {
        $errors[] = 'Your password must contain at least one letter.';
    }
    if (preg_match('/\p{N}/u', $password) !== 1) {
        $errors[] = 'Your password must contain at least one number.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'The passwords do not match.';
    }

    if ($errors !== []) {
        $_SESSION['_old'] = ['name' => $name, 'email' => $email];
        flash('errors', $errors);
        redirect('register.php');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $statement = db()->prepare(
        'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)'
    );

    try {
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);
    } catch (PDOException $exception) {
        if (($exception->errorInfo[1] ?? null) !== 1062) {
            throw $exception;
        }

        $_SESSION['_old'] = ['name' => $name, 'email' => $email];
        flash('errors', ['An account with that email address already exists.']);
        redirect('register.php');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) db()->lastInsertId();
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    unset($_SESSION['_old'], $_SESSION['_csrf_token']);

    flash('success', 'Your account has been created successfully.');
    redirect('dashboard.php');
}

$errors = flash('errors');
if (!is_array($errors)) {
    $errors = [];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<main class="auth-page">
    <section class="auth-card" aria-labelledby="register-title">
        <div class="auth-heading">
            <p class="eyebrow">Get started</p>
            <h1 id="register-title">Create your account</h1>
            <p>Enter your details to get started.</p>
        </div>

        <?php if ($errors !== []): ?>
            <div class="alert alert-error" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e((string) $error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="register.php">
            <?= csrf_field() ?>
            <label for="name">Name</label>
            <input
                id="name"
                name="name"
                type="text"
                autocomplete="name"
                maxlength="100"
                value="<?= old('name') ?>"
                required
            >

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
                autocomplete="new-password"
                minlength="10"
                required
            >
            <p class="field-hint">At least 10 characters, including a letter and a number.</p>

            <label for="confirm-password">Confirm password</label>
            <input
                id="confirm-password"
                name="confirm_password"
                type="password"
                autocomplete="new-password"
                minlength="10"
                required
            >

            <button type="submit">Create account</button>
        </form>
    </section>
</main>
</body>
</html>
