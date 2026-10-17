<?php
/**
 * Creates (or resets the password of) an ADMIN account from the command line.
 * No password is ever stored in source code - you type it when asked.
 *
 *   php php-module/bin/create_admin.php              (asks for username + password)
 *   php php-module/bin/create_admin.php myadmin      (asks for password only)
 *
 * It refuses to run in a web server, so nobody can call it from a browser.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

/** Read a line from the terminal; hide typing for passwords where the OS allows. */
function ask(string $prompt, bool $secret = false): string
{
    echo $prompt;
    if ($secret && stripos(PHP_OS, 'WIN') === 0) {
        // Windows: PowerShell secure prompt (best effort; falls back to visible input)
        $cmd = 'powershell -NoProfile -Command "$p = Read-Host -AsSecureString; '
             . '[Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($p))"';
        $out = shell_exec($cmd);
        if (is_string($out) && $out !== '') { return rtrim($out, "\r\n"); }
    } elseif ($secret && function_exists('shell_exec') && @shell_exec('stty -echo 2>/dev/null; echo ok') !== null) {
        $line = fgets(STDIN);
        @shell_exec('stty echo 2>/dev/null');
        echo PHP_EOL;
        return rtrim((string) $line, "\r\n");
    }
    return rtrim((string) fgets(STDIN), "\r\n");
}

$username = $argv[1] ?? trim(ask('Admin username [admin]: '));
$username = $username === '' ? 'admin' : $username;
if (!preg_match('/^[A-Za-z0-9._@-]{3,50}$/', $username)) {
    fwrite(STDERR, "Username must be 3-50 characters (letters, digits, . _ @ -).\n");
    exit(1);
}
$password = getenv('ADMIN_PASSWORD') ?: ask('Admin password: ', true);   // ADMIN_PASSWORD env var is for scripts
if (($err = password_error($password)) !== null) {
    fwrite(STDERR, $err . "\n");
    exit(1);
}
if (!getenv('ADMIN_PASSWORD') && ask('Repeat password: ', true) !== $password) {
    fwrite(STDERR, "Passwords do not match.\n");
    exit(1);
}

try {
    $pdo = db();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $exists = $pdo->prepare('SELECT id, role FROM users WHERE username = :u');
    $exists->execute([':u' => $username]);
    $row = $exists->fetch();
    if ($row && $row['role'] !== 'admin') {
        fwrite(STDERR, "That username belongs to a student account. Choose another.\n");
        exit(1);
    }
    if ($row) {
        $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id')->execute([':h' => $hash, ':id' => $row['id']]);
        echo "Password updated for admin '$username'.\n";
    } else {
        $pdo->prepare('INSERT INTO users (username, password_hash, role, student_id) VALUES (:u, :h, "admin", NULL)')
            ->execute([':u' => $username, ':h' => $hash]);
        echo "Admin '$username' created.\n";
    }
} catch (Throwable $ex) {
    fwrite(STDERR, 'Failed: ' . $ex->getMessage() . "\n");   // CLI only, so details are safe to show
    exit(1);
}
