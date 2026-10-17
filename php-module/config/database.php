<?php
/**
 * Database connection (PDO) and configuration loader.
 * Credentials come from config.local.php (git-ignored) or environment variables.
 */
declare(strict_types=1);

/** Returns the merged configuration array (file values, overridden by env vars). */
function app_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $cfg = [
        'db_host' => '127.0.0.1', 'db_port' => 3306, 'db_name' => 'student_portal',
        'db_user' => 'root', 'db_pass' => '', 'upload_dir' => __DIR__ . '/../uploads',
    ];
    $local = __DIR__ . '/config.local.php';
    if (is_file($local)) {
        $cfg = array_merge($cfg, (array) require $local);
    }
    // Environment variables win, which is handy on servers where files can't be edited
    foreach (['db_host' => 'DB_HOST', 'db_name' => 'DB_NAME', 'db_user' => 'DB_USER',
              'db_pass' => 'DB_PASS', 'upload_dir' => 'UPLOAD_DIR'] as $key => $env) {
        $val = getenv($env);
        if ($val !== false && $val !== '') {
            $cfg[$key] = $val;
        }
    }
    return $cfg;
}

/**
 * Returns one shared PDO connection.
 * Safe error handling: the real error goes to the server log; the visitor only
 * sees a generic message, so passwords / host names are never leaked.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = app_config();
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['db_host'], $c['db_port'], $c['db_name']);
    try {
        $pdo = new PDO($dsn, (string) $c['db_user'], (string) $c['db_pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw exceptions on SQL errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared statements
        ]);
    } catch (PDOException $ex) {
        error_log('DB connection failed: ' . $ex->getMessage());
        show_error_page(500, 'The database is not available right now. Please try again later.');
    }
    return $pdo;
}
