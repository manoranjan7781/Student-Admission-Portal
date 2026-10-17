<?php
/**
 * Shared helper functions: escaping, flash messages, redirects,
 * error pages and server-side validation of student data.
 */
declare(strict_types=1);

// Keep these lists identical to frontend/index.html and frontend/script.js
const COURSES = [
    'B.E. Computer Science and Engineering',
    'B.E. Electronics and Communication Engineering',
    'B.Tech Information Technology',
    'B.E. Mechanical Engineering',
    'B.Tech Artificial Intelligence and Data Science',
];

/** Escape text for safe output inside HTML (prevents XSS). Use on EVERY user-supplied value. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Store a one-time message (shown on the next page, then removed). */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Read and clear all pending flash messages. */
function pull_flashes(): array
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

/** Send a redirect and stop the script (a header alone does not stop execution). */
function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

/** Friendly error page used for 400/403/404/405/500. Never prints technical details. */
function show_error_page(int $code, string $message): void
{
    http_response_code($code);
    $pageTitle = 'Error ' . $code;
    $active = '';
    if (!headers_sent()) {
        header('Cache-Control: no-store');
    }
    require __DIR__ . '/header.php';
    echo '<div class="card narrow"><h1>' . $code . '</h1><p>' . e($message) . '</p>'
       . '<p><a class="btn secondary" href="index.php">Back to home</a></p></div>';
    require __DIR__ . '/footer.php';
    exit;
}

/** Password policy (same rules as script.js). Returns an error message or null if OK. */
function password_error(string $pw): ?string
{
    // bcrypt only uses the first 72 bytes, so longer passwords are refused instead of silently cut
    if (strlen($pw) > 72) {
        return 'Password must be at most 72 characters.';
    }
    if (strlen($pw) < 8 || !preg_match('/[a-z]/', $pw) || !preg_match('/[A-Z]/', $pw)
        || !preg_match('/[0-9]/', $pw) || !preg_match('/[^A-Za-z0-9]/', $pw)) {
        return 'Password needs 8+ characters with upper-case, lower-case, a digit and a symbol.';
    }
    return null;
}

/**
 * Validate student form input on the SERVER.
 *
 * @param array    $in               raw $_POST data
 * @param int|null $editingId        student id when editing (ignored in duplicate checks)
 * @param bool     $passwordRequired true when creating, false when editing (blank = keep)
 * @return array [list of error strings, array of cleaned values]
 */
function validate_student_input(PDO $pdo, array $in, ?int $editingId, bool $passwordRequired): array
{
    $errors = [];
    $clean = [
        'name'        => trim((string) ($in['name'] ?? '')),
        'roll_number' => strtoupper(trim((string) ($in['roll_number'] ?? ''))),
        'email'       => strtolower(trim((string) ($in['email'] ?? ''))),
        'course'      => (string) ($in['course'] ?? ''),
        'password'    => (string) ($in['password'] ?? ''),
    ];

    if (mb_strlen($clean['name']) < 3 || mb_strlen($clean['name']) > 100
        || !preg_match("/^\p{L}[\p{L}\p{M} .'\-]*$/u", $clean['name'])) {
        $errors[] = 'Name must be 3-100 characters (letters, spaces, dots, apostrophes, hyphens).';
    }
    if (!preg_match('/^[A-Z0-9\-\/]{4,20}$/', $clean['roll_number'])) {
        $errors[] = 'Roll number must be 4-20 letters, digits, "-" or "/".';
    }
    if (strlen($clean['email']) > 150 || !filter_var($clean['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!in_array($clean['course'], COURSES, true)) {   // allow-list: only known courses accepted
        $errors[] = 'Please choose a course from the list.';
    }
    if ($passwordRequired || $clean['password'] !== '') {
        $pwError = password_error($clean['password']);
        if ($pwError !== null) {
            $errors[] = $pwError;
        }
        if (($in['confirm_password'] ?? $clean['password']) !== $clean['password']) {
            $errors[] = 'Passwords do not match.';
        }
    }

    // Duplicate check (the UNIQUE keys in MySQL are the final guard against races)
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT roll_number, email FROM students
                               WHERE (roll_number = :r OR email = :e) AND id <> :id');
        $stmt->execute([':r' => $clean['roll_number'], ':e' => $clean['email'], ':id' => $editingId ?? 0]);
        foreach ($stmt->fetchAll() as $row) {
            if ($row['roll_number'] === $clean['roll_number']) {
                $errors[] = 'This roll number is already registered.';
            }
            if (strcasecmp($row['email'], $clean['email']) === 0) {
                $errors[] = 'This email is already registered.';
            }
        }
        $errors = array_values(array_unique($errors));
    }
    return [$errors, $clean];
}

/** Human readable file size, e.g. 1.4 MB */
function format_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    }
    return number_format(max($bytes, 1) / 1024, 1) . ' KB';
}

/** True when the PDOException is a duplicate-key violation (MySQL error 1062). */
function is_duplicate_key(PDOException $ex): bool
{
    return ($ex->errorInfo[1] ?? 0) === 1062;
}
