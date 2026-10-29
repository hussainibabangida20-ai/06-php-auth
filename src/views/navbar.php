<?php
declare(strict_types=1);

$scriptDirectory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$appBase = basename($scriptDirectory) === 'admin'
    ? dirname($scriptDirectory)
    : $scriptDirectory;
if ($appBase === '.' || $appBase === '/') {
    $appBase = '';
}
$navbarUser = current_user();
?>
<header class="site-nav">
    <a class="site-brand" href="<?= e($appBase . '/dashboard.php') ?>">SWD Auth</a>
    <nav aria-label="Main navigation">
        <a href="<?= e($appBase . '/dashboard.php') ?>">Dashboard</a>
        <?php if ($navbarUser !== null): ?>
            <a href="<?= e($appBase . '/profile.php') ?>">Profile</a>
        <?php endif; ?>
        <?php if ($navbarUser !== null && $navbarUser['role'] === 'admin'): ?>
            <a href="<?= e($appBase . '/admin/users.php') ?>">Admin</a>
        <?php endif; ?>
    </nav>
</header>
