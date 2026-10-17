<?php
/**
 * Session handling, CSRF protection, login state and authorisation helpers.
 * Every page starts with:  require __DIR__ . '/includes/auth.php';
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/** Start the session with hardened cookie settings. */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,          // cookie dies when the browser closes
        'path'     => '/',
        'secure'   => $https,     // only sent over HTTPS when the site uses HTTPS
        'httponly' => true,       // JavaScript cannot read the cookie (limits XSS damage)
        'samesite' => 'Lax',      // not sent with cross-site POSTs (extra CSRF defence)
    ]);
    session_start();
}
start_secure_session();

// Never show PHP errors / stack traces to visitors (they are written to the server log instead)
ini_set('display_errors', '0');
set_exception_handler(static function (Throwable $ex): void {
    error_log('Uncaught: ' . $ex->getMessage() . ' in ' . $ex->getFile() . ':' . $ex->getLine());
    if (!headers_sent()) {
        show_error_page(500, 'Something went wrong on the server. Please try again later.');
    }
});

// Basic security headers for every page
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

/* ----------------------------- CSRF ------------------------------------ */

/** Returns this session's CSRF token, creating it on first use. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Hidden input to place inside every state-changing <form>. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Reject the request unless the posted token matches the session token. */
function csrf_verify(): void
{
    $sent = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals(csrf_token(), $sent)) {     // hash_equals = timing-safe comparison
        show_error_page(400, 'Security check failed (invalid or expired form). Please go back, reload the page and try again.');
    }
}

/* ------------------------- Login state --------------------------------- */

/** The logged-in user as an array (id, username, role, student_id) or null. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

/** Call at the top of protected pages: sends guests to the login page. */
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
    return $user;
}

/** Call on admin-only pages. Logged-in students get 403 Forbidden. */
function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        show_error_page(403, 'You do not have permission to open this page.');
    }
    return $user;
}

/** Authorisation rule: admins see every student, a student sees only themselves. */
function can_access_student(int $studentId): bool
{
    $user = current_user();
    if ($user === null) {
        return false;
    }
    return $user['role'] === 'admin' || (int) ($user['student_id'] ?? 0) === $studentId;
}

/** Create the logged-in session. The session id is regenerated to stop session fixation. */
function login_user(array $row): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'         => (int) $row['id'],
        'username'   => $row['username'],
        'role'       => $row['role'],
        'student_id' => $row['student_id'] !== null ? (int) $row['student_id'] : null,
    ];
    csrf_token();
}

/** Completely destroy the session (data + cookie). */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
