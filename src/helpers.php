<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function flash(string $key, mixed $message = null): mixed
{
    if (func_num_args() > 1) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $value;
}

function old(string $key, string $default = ''): string
{
    $value = $_SESSION['_old'][$key] ?? $default;
    unset($_SESSION['_old'][$key]);
    if (($_SESSION['_old'] ?? []) === []) {
        unset($_SESSION['_old']);
    }

    return e(is_string($value) ? $value : $default);
}
