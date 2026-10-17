<?php
/** UPDATE: admin edits details, adds/replaces files, or deletes one file. */
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/upload.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    show_error_page(400, 'Invalid student id.');
}
$pdo = db();
$stmt = $pdo->prepare('SELECT id, name, roll_number, email, course FROM students WHERE id = :id');
$stmt->execute([':id' => $id]);
$student = $stmt->fetch();
if (!$student) {
    show_error_page(404, 'Student not found.');
}

$errors = [];
$values = $student;
$action = (string) ($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_file') {
    /* ---- Remove ONE file (separate small POST form on this page) ---- */
    csrf_verify();
    $fileId = filter_var($_POST['file_id'] ?? '', FILTER_VALIDATE_INT);
    // "AND student_id" makes sure the file really belongs to this student
    $f = $pdo->prepare('SELECT id, file_path FROM student_files WHERE id = :f AND student_id = :s');
    $f->execute([':f' => $fileId ?: 0, ':s' => $id]);
    if ($row = $f->fetch()) {
        $pdo->prepare('DELETE FROM student_files WHERE id = :f')->execute([':f' => $row['id']]);
        if ($path = safe_upload_path($row['file_path'])) { cleanup_files([$path]); }
        remove_student_folder($id);
        flash('success', 'File deleted.');
    } else {
        flash('error', 'File not found.');
    }
    redirect('edit_student.php?id=' . $id);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* ---- Save details + new uploads ---- */
    csrf_verify();
    [$errors, $clean] = validate_student_input($pdo, $_POST, $id, false);
    $values = array_intersect_key($clean, $values) + $values;
    [$uploadErrors, $uploads] = collect_uploads($_FILES, ['profile_image', 'id_proof', 'certificate']);
    $errors = array_merge($errors, $uploadErrors);

    if (!$errors) {
        $written = [];
        $toDelete = [];                       // old profile images replaced by a new one
        try {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE students SET name=:n, roll_number=:r, email=:e, course=:c WHERE id=:id')
                ->execute([':n' => $clean['name'], ':r' => $clean['roll_number'], ':e' => $clean['email'], ':c' => $clean['course'], ':id' => $id]);
            // Keep the login username equal to the (possibly changed) email
            $pdo->prepare('UPDATE users SET username=:u WHERE student_id=:id')->execute([':u' => $clean['email'], ':id' => $id]);
            if ($clean['password'] !== '') {
                $pdo->prepare('UPDATE users SET password_hash=:h WHERE student_id=:id')
                    ->execute([':h' => password_hash($clean['password'], PASSWORD_DEFAULT), ':id' => $id]);
            }
            if (isset($uploads['profile_image'])) {       // remember old photos; delete after commit
                $old = $pdo->prepare("SELECT id, file_path FROM student_files WHERE student_id=:id AND category='profile_image'");
                $old->execute([':id' => $id]);
                $toDelete = $old->fetchAll();
            }
            foreach ($uploads as $cat => $v) {
                $written[] = store_upload($pdo, $id, $cat, $v);
            }
            foreach ($toDelete as $o) {
                $pdo->prepare('DELETE FROM student_files WHERE id=:f')->execute([':f' => $o['id']]);
            }
            $pdo->commit();
            foreach ($toDelete as $o) {                   // only now is it safe to remove the old files
                if ($p = safe_upload_path($o['file_path'])) { cleanup_files([$p]); }
            }
            flash('success', 'Student updated successfully.');
            redirect('view_student.php?id=' . $id);
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            cleanup_files($written);
            if ($ex instanceof PDOException && is_duplicate_key($ex)) {
                $errors[] = 'This roll number or email belongs to another student.';
            } else {
                error_log('edit_student.php: ' . $ex->getMessage());
                $errors[] = 'Could not update because of a server problem. Nothing was changed.';
            }
        }
    }
}

$fs = $pdo->prepare('SELECT id, category, original_filename, mime_type, file_size, uploaded_at FROM student_files WHERE student_id=:id ORDER BY category, id');
$fs->execute([':id' => $id]);
$files = $fs->fetchAll();

$pageTitle = 'Edit ' . $student['name'];
$active = 'students';
$isEdit = true;
require __DIR__ . '/includes/header.php';
?>
<div class="page-title">
  <h1>Edit student</h1>
  <a class="btn secondary" href="view_student.php?id=<?= (int) $id ?>">Cancel</a>
</div>
<section class="card">
  <?php if ($errors): ?>
    <div class="alert error" role="alert"><strong>Please correct the following:</strong>
      <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>
  <form method="post" action="edit_student.php?id=<?= (int) $id ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <?php require __DIR__ . '/includes/student_form.php'; ?>
    <div class="btn-row"><button class="btn" type="submit">Save changes</button></div>
  </form>
</section>

<h2 style="margin-top:1.5rem">Existing files</h2>
<?php if (!$files): ?>
  <p class="muted">No files uploaded yet.</p>
<?php else: ?>
<div class="card table-wrap" style="padding:0">
  <table>
    <thead><tr><th>Type</th><th>Name</th><th>Size</th><th>Uploaded</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($files as $f): ?>
      <tr>
        <td><?= e($f['category']) ?></td>
        <td><a href="download.php?id=<?= (int) $f['id'] ?>" target="_blank" rel="noopener"><?= e($f['original_filename']) ?></a></td>
        <td><?= e(format_bytes((int) $f['file_size'])) ?></td>
        <td><?= e($f['uploaded_at']) ?></td>
        <td>
          <form class="inline" method="post" action="edit_student.php?id=<?= (int) $id ?>" onsubmit="return confirm('Delete this file?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_file">
            <input type="hidden" name="file_id" value="<?= (int) $f['id'] ?>">
            <button class="btn small danger" type="submit">Delete file</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
