<?php
/**
 * File-upload library (included by add_student.php, edit_student.php, register.php).
 * It is a set of functions, not a page: opening it in a browser just redirects.
 *
 * Security rules implemented here:
 *  1. Size limit per category (checked on the server, not just in the browser).
 *  2. REAL file type detected from the file's bytes with finfo (not the
 *     browser-supplied type, not the file extension).
 *  3. Allow-list of MIME types; the stored extension is derived from the detected
 *     MIME type, so "evil.php" renamed to "photo.jpg" (or the reverse) is useless.
 *  4. Random server-generated file names -> no overwriting, no path tricks.
 *  5. Files go to a folder the user cannot choose, one sub-folder per student.
 */
declare(strict_types=1);

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {   // opened directly in browser
    header('Location: index.php');
    exit;
}

const IMAGE_MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const DOC_MIMES   = IMAGE_MIMES + ['application/pdf' => 'pdf'];

/** Rules for each upload category: allowed MIME types (=> extension) and max bytes. */
function upload_rules(string $category): array
{
    return match ($category) {
        'profile_image' => ['mimes' => IMAGE_MIMES, 'max' => 2 * 1024 * 1024, 'label' => 'Profile image'],
        'id_proof'      => ['mimes' => DOC_MIMES,   'max' => 5 * 1024 * 1024, 'label' => 'ID proof'],
        'certificate'   => ['mimes' => DOC_MIMES,   'max' => 5 * 1024 * 1024, 'label' => 'Certificate'],
        default         => throw new InvalidArgumentException('Unknown upload category'),
    };
}

/** Absolute path of the upload root (created if missing). */
function upload_root(): string
{
    $dir = (string) app_config()['upload_dir'];
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create upload directory');
    }
    $real = realpath($dir);
    if ($real === false) {
        throw new RuntimeException('Upload directory is not accessible');
    }
    return $real;
}

/**
 * Turn a stored relative path into a verified absolute path inside the upload root.
 * Returns null for anything suspicious (path traversal, missing file, outside root).
 */
function safe_upload_path(string $relative): ?string
{
    $root = upload_root();
    $full = realpath($root . DIRECTORY_SEPARATOR . $relative);
    if ($full === false || strncmp($full, $root . DIRECTORY_SEPARATOR, strlen($root) + 1) !== 0 || !is_file($full)) {
        return null;
    }
    return $full;
}

/**
 * Validate ONE uploaded file.
 * @return array ['skip'=>true] when nothing was chosen, or
 *               ['error'=>string] on failure, or
 *               ['tmp','mime','ext','size','original'] on success.
 */
function validate_upload(?array $file, string $category): array
{
    $rules = upload_rules($category);
    $label = $rules['label'];

    if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['skip' => true];
    }
    // PHP's own upload error codes -> friendly messages
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['error' => "$label is larger than the server allows (see upload_max_filesize in php.ini)."];
        case UPLOAD_ERR_PARTIAL:
            return ['error' => "$label was only partly uploaded. Please try again."];
        default:
            error_log('Upload error code ' . $file['error']);
            return ['error' => "$label could not be uploaded because of a server problem."];
    }
    if (!is_uploaded_file($file['tmp_name'])) {            // guards against forged tmp_name values
        return ['error' => "$label was not a valid upload."];
    }
    $size = (int) $file['size'];
    if ($size <= 0) {
        return ['error' => "$label is empty."];
    }
    if ($size > $rules['max']) {
        return ['error' => "$label is too large (" . format_bytes($size) . "). Maximum is " . format_bytes($rules['max']) . '.'];
    }

    // Detect the real type from the file content
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($file['tmp_name']);
    if (!isset($rules['mimes'][$mime])) {
        $allowed = implode(', ', array_values($rules['mimes']));
        return ['error' => "$label must be one of: $allowed. The file you chose is not an allowed type."];
    }
    // Extra check for images: a genuine image must have readable dimensions
    if (str_starts_with($mime, 'image/') && @getimagesize($file['tmp_name']) === false) {
        return ['error' => "$label is not a valid image."];
    }
    // Clean the display name: strip folders and control characters, limit length
    $original = basename(str_replace('\\', '/', (string) $file['name']));
    $original = preg_replace('/[\x00-\x1F\x7F]/', '', $original) ?? 'file';
    $original = mb_substr($original, 0, 200) ?: 'file';

    return ['tmp' => $file['tmp_name'], 'mime' => $mime, 'ext' => $rules['mimes'][$mime],
            'size' => $size, 'original' => $original];
}

/**
 * Validate every file field of a form. Call this BEFORE touching the database.
 * @param array $files $_FILES
 * @return array [errors[], valid[category => validated-file-array]]
 */
function collect_uploads(array $files, array $categories): array
{
    $errors = [];
    $valid = [];
    foreach ($categories as $cat) {
        $result = validate_upload($files[$cat] ?? null, $cat);
        if (isset($result['error'])) {
            $errors[] = $result['error'];
        } elseif (!isset($result['skip'])) {
            $valid[$cat] = $result;
        }
    }
    return [$errors, $valid];
}

/**
 * Move a validated upload into place and record its metadata.
 * Call inside a DB transaction. Returns the absolute path so the caller can
 * delete it again if a LATER step fails (see cleanup_files()).
 */
function store_upload(PDO $pdo, int $studentId, string $category, array $v): string
{
    $root = upload_root();
    $subdir = 's' . $studentId;                                    // one folder per student
    $dir = $root . DIRECTORY_SEPARATOR . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create student upload folder');
    }
    // Random 128-bit name + extension derived from detected MIME. The loop makes
    // an overwrite impossible even in the (astronomically unlikely) case of a clash.
    do {
        $stored = bin2hex(random_bytes(16)) . '.' . $v['ext'];
        $target = $dir . DIRECTORY_SEPARATOR . $stored;
    } while (file_exists($target));

    if (!move_uploaded_file($v['tmp'], $target)) {
        throw new RuntimeException('Could not move uploaded file');
    }
    chmod($target, 0644);

    $stmt = $pdo->prepare(
        'INSERT INTO student_files (student_id, category, original_filename, stored_filename, file_path, mime_type, file_size)
         VALUES (:sid, :cat, :orig, :stored, :path, :mime, :size)'
    );
    $stmt->execute([
        ':sid' => $studentId, ':cat' => $category, ':orig' => $v['original'],
        ':stored' => $stored, ':path' => $subdir . '/' . $stored,     // always forward slashes
        ':mime' => $v['mime'], ':size' => $v['size'],
    ]);
    return $target;
}

/** Delete files from disk (used after a failed transaction or an explicit delete). */
function cleanup_files(array $absolutePaths): void
{
    foreach ($absolutePaths as $p) {
        if (is_string($p) && is_file($p)) {
            @unlink($p);
        }
    }
}

/** Remove a student's upload folder if it is now empty. */
function remove_student_folder(int $studentId): void
{
    $dir = upload_root() . DIRECTORY_SEPARATOR . 's' . $studentId;
    if (is_dir($dir)) {
        @rmdir($dir);       // rmdir only succeeds on an empty directory - a safe default
    }
}
