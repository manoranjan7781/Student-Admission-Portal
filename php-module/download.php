<?php
/**
 * Secure file endpoint: streams a stored file only after checking that
 *   1. the user is logged in, 2. the user may access that student's data.
 * The browser never receives (or chooses) a file-system path - only a database id.
 *   ?id=12            -> shown inline (images / PDF open in the browser)
 *   ?id=12&download=1 -> forces a "Save as" download
 */
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/upload.php';

if (current_user() === null) {
    http_response_code(401);
    exit('Please log in first.');
}
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    exit('Invalid file id.');
}
$stmt = db()->prepare('SELECT student_id, original_filename, file_path, mime_type, file_size FROM student_files WHERE id = :id');
$stmt->execute([':id' => $id]);
$file = $stmt->fetch();
if (!$file) {
    http_response_code(404);
    exit('File not found.');
}
if (!can_access_student((int) $file['student_id'])) {
    http_response_code(403);
    exit('You do not have permission to open this file.');
}
$path = safe_upload_path($file['file_path']);        // blocks "../" tricks, verifies the file exists
if ($path === null) {
    error_log('Missing file on disk for id ' . $id);
    http_response_code(404);
    exit('The file is missing on the server.');
}

session_write_close();                                // free the session lock while streaming

// Only these types may be displayed inline; anything else is always downloaded
$inlineOk = in_array($file['mime_type'], ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true);
$disposition = ($inlineOk && empty($_GET['download'])) ? 'inline' : 'attachment';
// Header-safe filename: ASCII fallback + RFC 5987 UTF-8 version
$ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $file['original_filename']) ?: 'file';

header('Content-Type: ' . ($inlineOk ? $file['mime_type'] : 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header("Content-Disposition: $disposition; filename=\"$ascii\"; filename*=UTF-8''" . rawurlencode($file['original_filename']));
header('X-Content-Type-Options: nosniff');            // browser must not guess a different type
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
header('Cache-Control: private, max-age=0, no-cache');
readfile($path);
