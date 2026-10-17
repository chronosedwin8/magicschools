<?php
/**
 * Autenticación de clientes del portal.
 */

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = !empty($_SESSION['user_id'])
            ? db_one('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']])
            : null;
    }
    return $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    db_exec('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$userId]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? '';
        flash('info', 'Inicia sesión para acceder al portal de clientes.');
        redirect('portal/login.php');
    }
    return $user;
}

function is_teacher(?array $user = null): bool
{
    $user ??= current_user();
    return $user !== null && ($user['role'] ?? '') === 'teacher';
}

/**
 * Ficha del docente dentro de su licencia (license_members).
 */
function current_member(?array $user = null): ?array
{
    static $member = false;
    $user ??= current_user();
    if ($user === null) {
        return null;
    }
    if ($member === false) {
        $member = db_one('SELECT * FROM license_members WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$user['id']]);
    }
    return $member;
}

/**
 * Licencia con la que trabaja el usuario actual:
 * el docente la hereda de su institución; el cliente institucional usa la suya.
 */
function current_license(?array $user = null): ?array
{
    static $license = false;
    $user ??= current_user();
    if ($user === null) {
        return null;
    }
    if ($license !== false) {
        return $license;
    }
    if (is_teacher($user)) {
        $member = current_member($user);
        $license = $member
            ? db_one('SELECT l.*, u.institution FROM licenses l JOIN users u ON u.id = l.user_id WHERE l.id = ?', [$member['license_id']])
            : null;
    } else {
        $license = db_one(
            'SELECT l.*, u.institution FROM licenses l JOIN users u ON u.id = l.user_id
              WHERE l.user_id = ? AND l.status = "active" AND l.expires_at > NOW()
              ORDER BY l.expires_at DESC LIMIT 1',
            [$user['id']]
        );
    }
    return $license;
}

/**
 * Páginas de herramientas: exige sesión y una licencia activa.
 */
function require_tools_access(): array
{
    $user = require_login();
    $license = current_license($user);
    if (!$license || !license_is_usable($license)) {
        flash('warning', is_teacher($user)
            ? 'La licencia de tu institución no está activa. Avisa a quien la administra.'
            : 'Necesitas una licencia activa para usar las herramientas.');
        redirect(is_teacher($user) ? 'portal/' : 'portal/licencias.php');
    }
    return [$user, $license];
}

function is_admin(?array $user = null): bool
{
    $user ??= current_user();
    return $user !== null && ($user['role'] ?? '') === 'admin';
}

function require_admin(): array
{
    $user = require_login();
    if (!is_admin($user)) {
        http_response_code(403);
        flash('error', 'No tienes permisos de administrador.');
        redirect('portal/');
    }
    return $user;
}

function attempt_login(string $email, string $password): ?array
{
    $user = db_one('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))]);
    if ($user && password_verify($password, $user['password_hash'])) {
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        return $user;
    }
    return null;
}
