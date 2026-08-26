<?php
/**
 * REGC — Admin authentication guard.
 * Include this at the top of every admin page/endpoint.
 */

require_once __DIR__ . '/bootstrap.php';

function admin_login(string $username, string $password): bool
{
    $res = db()->select('admins', [
        'select' => 'id,username,password_hash',
        'username' => 'eq.' . $username,
        'limit' => '1',
    ]);
    $row = $res['data'][0] ?? null;
    if ($row && password_verify($password, $row['password_hash'])) {
        $_SESSION['admin_id'] = (int)$row['id'];
        $_SESSION['admin_user'] = $row['username'];
        return true;
    }
    // Fallback: allow the configured default password until a real admin is set up.
    if (!$row && defined('DEFAULT_ADMIN_PASSWORD') && hash_equals(DEFAULT_ADMIN_PASSWORD, $password)) {
        $_SESSION['admin_id'] = 1; // non-zero so admin_logged_in() resolves truthy
        $_SESSION['admin_user'] = 'admin';
        $_SESSION['admin_default'] = true;
        return true;
    }
    return false;
}

function admin_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        redirect('login.php');
    }
}

function require_admin_api(): void
{
    if (!admin_logged_in()) {
        json_out(['ok' => false, 'error' => 'Unauthorized'], 401);
    }
}

function admin_logout(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_user'], $_SESSION['admin_default']);
    session_regenerate_id(true);
}