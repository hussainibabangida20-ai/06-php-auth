<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $loaded = false;
    static $user = null;

    if ($loaded) {
        return $user;
    }

    $userId = $_SESSION['user_id'] ?? null;
    if (!is_int($userId) || $userId < 1) {
        $loaded = true;
        return null;
    }

    $statement = db()->prepare(
        'SELECT id, name, email, role, created_at, last_login_at FROM users WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $userId]);
    $row = $statement->fetch();

    if ($row === false) {
        unset($_SESSION['user_id'], $_SESSION['role']);
        $loaded = true;
        return null;
    }

    $user = $row;
    $user['id'] = (int) $row['id'];
    $loaded = true;

    return $user;
}

function require_login(): void
{
    if (current_user() !== null) {
        return;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $intendedUrl = safe_intended_url(is_string($requestUri) ? $requestUri : null);
        if ($intendedUrl !== null) {
            $_SESSION['_intended_url'] = $intendedUrl;
        }
    }

    flash('notice', 'Please sign in to continue.');
    redirect('login.php');
}

function require_guest(): void
{
    if (current_user() !== null) {
        redirect('dashboard.php');
    }
}

function require_role(string $role): void
{
    require_login();

    $user = current_user();
    if ($user === null || $user['role'] !== $role) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }
}

function safe_intended_url(?string $url): ?string
{
    if ($url === null || $url === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $url) === 1) {
        return null;
    }

    $parts = parse_url($url);
    if (!is_array($parts)
        || isset($parts['scheme'])
        || isset($parts['host'])
        || isset($parts['user'])
        || isset($parts['pass'])
        || !isset($parts['path'])
        || !str_starts_with($parts['path'], '/')
        || str_starts_with($parts['path'], '//')
    ) {
        return null;
    }

    $candidate = $parts['path'];
    if (isset($parts['query'])) {
        $candidate .= '?' . $parts['query'];
    }

    if (preg_match('#\A/(?!/)[A-Za-z0-9._~!$&\'()*+,;=:@%/?-]*\z#D', $candidate) !== 1
        || preg_match('/%(?![A-Fa-f0-9]{2})/', $candidate) === 1
    ) {
        return null;
    }

    return $candidate;
}

function auth_reset_session(string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Cannot reset an inactive session.');
    }

    $cookie = session_get_cookie_params();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ]);
    }

    if (!session_destroy()) {
        throw new RuntimeException('Unable to destroy the current session.');
    }
    if (!session_start()) {
        throw new RuntimeException('Unable to start a new session.');
    }

    $_SESSION['_last_activity'] = time();
    flash('success', $message);
}

function auth_enforce_idle_timeout(): void
{
    $now = time();
    $lastActivity = $_SESSION['_last_activity'] ?? null;
    $isAuthenticated = isset($_SESSION['user_id']);

    if ($isAuthenticated
        && is_int($lastActivity)
        && $now - $lastActivity >= 1800
    ) {
        auth_reset_session('You were signed out after 30 minutes of inactivity.');
        return;
    }

    $_SESSION['_last_activity'] = $now;
}
