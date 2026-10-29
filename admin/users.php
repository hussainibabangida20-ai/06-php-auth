<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

require_role('admin');
$admin = current_user();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_verify()) {
        http_response_code(400);
        exit('The form expired or could not be verified. Please try again.');
    }

    $targetId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    $newRole = $_POST['role'] ?? null;
    if (!is_int($targetId) || $targetId < 1 || !in_array($newRole, ['user', 'admin'], true)) {
        flash('error', 'The requested role change is invalid.');
        redirect('users.php');
    }

    if ($targetId === $admin['id'] && $newRole !== 'admin') {
        flash('error', 'You cannot remove your own admin role.');
        redirect('users.php');
    }

    $findUser = db()->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
    $findUser->execute(['id' => $targetId]);
    if ($findUser->fetch() === false) {
        flash('error', 'That user could not be found.');
        redirect('users.php');
    }

    $updateRole = db()->prepare('UPDATE users SET role = :role WHERE id = :id');
    $updateRole->execute(['role' => $newRole, 'id' => $targetId]);
    flash('success', 'User role updated.');
    redirect('users.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Method not allowed.');
}

$error = flash('error');
$success = flash('success');
$users = db()->query(
    'SELECT id, name, email, role, created_at, last_login_at FROM users ORDER BY id'
)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage users</title>
    <link rel="stylesheet" href="../assets/auth.css">
</head>
<body>
<?php require dirname(__DIR__) . '/src/views/navbar.php'; ?>
<main class="auth-page">
    <section class="auth-card admin-card" aria-labelledby="users-title">
        <div class="auth-heading">
            <p class="eyebrow">Administration</p>
            <h1 id="users-title">Manage users</h1>
            <p>Review accounts and manage administrator access.</p>
        </div>

        <?php if (is_string($error) && $error !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if (is_string($success) && $success !== ''): ?>
            <div class="alert alert-success" role="status"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Last login</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <?php
                    $createdAt = (new DateTimeImmutable((string) $user['created_at']))->format('M j, Y');
                    $lastLoginAt = $user['last_login_at'] === null
                        ? 'Never'
                        : (new DateTimeImmutable((string) $user['last_login_at']))->format('M j, Y g:i A');
                    $isSelf = (int) $user['id'] === $admin['id'];
                    $nextRole = $user['role'] === 'admin' ? 'user' : 'admin';
                    ?>
                    <tr>
                        <td><?= e((string) $user['name']) ?></td>
                        <td><?= e((string) $user['email']) ?></td>
                        <td><?= e((string) $user['role']) ?></td>
                        <td><?= e($createdAt) ?></td>
                        <td><?= e($lastLoginAt) ?></td>
                        <td>
                            <form method="post" action="users.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                <input type="hidden" name="role" value="<?= e($nextRole) ?>">
                                <button
                                    type="submit"
                                    <?= $isSelf && $nextRole === 'user' ? 'disabled' : '' ?>
                                >
                                    <?= $nextRole === 'admin' ? 'Promote to admin' : 'Demote to user' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
