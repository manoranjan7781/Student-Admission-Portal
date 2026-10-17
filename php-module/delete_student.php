<?php
/** DELETE: POST + CSRF only. Removes the student, login, file rows (FK cascade) and files on disk. */
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/upload.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    show_error_page(405, 'Deleting must be done with the Delete button.');
}
csrf_verify();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    show_error_page(400, 'Invalid student id.');
}
$pdo = db();
try {
    $paths = $pdo->prepare('SELECT file_path FROM student_files WHERE student_id = :id');
    $paths->execute([':id' => $id]);
    $paths = $paths->fetchAll(PDO::FETCH_COLUMN);

    $del = $pdo->prepare('DELETE FROM students WHERE id = :id');   // cascades to users + student_files
    $del->execute([':id' => $id]);
    if ($del->rowCount() === 0) {
        show_error_page(404, 'Student not found.');
    }
    // Database rows are gone; now remove the files from disk
    foreach ($paths as $rel) {
        if ($abs = safe_upload_path($rel)) { cleanup_files([$abs]); }
    }
    remove_student_folder($id);
    flash('success', 'Student and their files were deleted.');
} catch (PDOException $ex) {
    error_log('delete_student.php: ' . $ex->getMessage());
    flash('error', 'Could not delete the student because of a database problem.');
}
redirect('view_students.php');
