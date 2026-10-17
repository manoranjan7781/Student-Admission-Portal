<?php
/**
 * READ (single): profile page with uploaded images/documents.
 * Admins may open any student; a student may open only their own page.
 */
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    show_error_page(400, 'Invalid student id.');
}
if (!can_access_student($id)) {
    show_error_page(403, 'You do not have permission to view this profile.');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, name, roll_number, email, course, created_at, updated_at FROM students WHERE id = :id');
$stmt->execute([':id' => $id]);
$student = $stmt->fetch();
if (!$student) {
    show_error_page(404, 'Student not found.');
}
$files = $pdo->prepare('SELECT id, category, original_filename, stored_filename, file_path, mime_type, file_size, uploaded_at
                        FROM student_files WHERE student_id = :id ORDER BY category, id');
$files->execute([':id' => $id]);
$files = $files->fetchAll();

// Split: the newest profile image is the main photo, everything else is a document
$photo = null;
$docs = [];
foreach ($files as $f) {
    if ($f['category'] === 'profile_image') { $photo = $f; } else { $docs[] = $f; }
}
$labels = ['profile_image' => 'Profile image', 'id_proof' => 'ID proof', 'certificate' => 'Certificate', 'other' => 'Other'];

$pageTitle = $student['name'];
$active = is_admin() ? 'students' : 'home';
require __DIR__ . '/includes/header.php';
?>
<div class="page-title">
  <h1><?= e($student['name']) ?></h1>
  <?php if (is_admin()): ?>
    <div class="btn-row" style="margin:0">
      <a class="btn secondary" href="view_students.php">All students</a>
      <a class="btn" href="edit_student.php?id=<?= (int) $student['id'] ?>">Edit</a>
    </div>
  <?php endif; ?>
</div>

<section class="card profile">
  <div>
    <?php if ($photo): ?>
      <img class="photo" src="download.php?id=<?= (int) $photo['id'] ?>" alt="Profile photo of <?= e($student['name']) ?>">
    <?php else: ?>
      <div class="photo avatar ph" role="img" aria-label="No profile photo uploaded" style="display:flex;border-radius:10px">No photo</div>
    <?php endif; ?>
  </div>
  <dl class="details">
    <dt>Roll number</dt><dd><?= e($student['roll_number']) ?></dd>
    <dt>Email</dt><dd><?= e($student['email']) ?></dd>
    <dt>Course</dt><dd><?= e($student['course']) ?></dd>
    <dt>Registered</dt><dd><?= e($student['created_at']) ?></dd>
    <dt>Last updated</dt><dd><?= e($student['updated_at']) ?></dd>
  </dl>
</section>

<h2 style="margin-top:1.5rem">Uploaded files</h2>
<?php if (!$files): ?>
  <p class="muted">No files uploaded for this student yet.</p>
<?php else: ?>
  <div class="card table-wrap" style="padding:0">
    <table>
      <caption class="sr-only">Metadata of uploaded files</caption>
      <thead><tr><th>Type</th><th>Original name</th><th>Stored as</th><th>MIME</th><th>Size</th><th>Uploaded</th></tr></thead>
      <tbody>
      <?php foreach ($files as $f): ?>
        <tr>
          <td><?= e($labels[$f['category']] ?? $f['category']) ?></td>
          <td><?= e($f['original_filename']) ?></td>
          <td><code><?= e($f['file_path']) ?></code></td>
          <td><?= e($f['mime_type']) ?></td>
          <td><?= e(format_bytes((int) $f['file_size'])) ?></td>
          <td><?= e($f['uploaded_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($docs): ?>
  <h3 style="margin-top:1.25rem">Documents</h3>
  <ul class="file-list">
    <?php foreach ($docs as $f): ?>
      <li>
        <strong><?= e($labels[$f['category']] ?? $f['category']) ?></strong>
        <?php if (str_starts_with($f['mime_type'], 'image/')): ?>
          <!-- Images are shown inline; the bytes come from download.php after an access check -->
          <img class="preview" src="download.php?id=<?= (int) $f['id'] ?>" alt="<?= e($labels[$f['category']] ?? 'Document') ?>: <?= e($f['original_filename']) ?>">
        <?php endif; ?>
        <span>
          <a href="download.php?id=<?= (int) $f['id'] ?>" target="_blank" rel="noopener">Open <?= e($f['original_filename']) ?></a>
          &middot; <a href="download.php?id=<?= (int) $f['id'] ?>&amp;download=1">Download</a>
        </span>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
