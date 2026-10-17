<?php
/** CREATE: admin adds a student (+ login account + optional files). */
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/upload.php';
require_admin();

$pdo = db();
$errors = [];
$values = ['name' => '', 'roll_number' => '', 'email' => '', 'course' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$errors, $clean] = validate_student_input($pdo, $_POST, null, true);
    $values = array_intersect_key($clean, $values) + $values;          // keep what the admin typed (no password)
    [$uploadErrors, $uploads] = collect_uploads($_FILES, ['profile_image', 'id_proof', 'certificate']);
    $errors = array_merge($errors, $uploadErrors);

    if (!$errors) {
        $written = [];
        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO students (name, roll_number, email, course) VALUES (:n, :r, :e, :c)')
                ->execute([':n' => $clean['name'], ':r' => $clean['roll_number'], ':e' => $clean['email'], ':c' => $clean['course']]);
            $id = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO users (username, password_hash, role, student_id) VALUES (:u, :h, "student", :sid)')
                ->execute([':u' => $clean['email'], ':h' => password_hash($clean['password'], PASSWORD_DEFAULT), ':sid' => $id]);
            foreach ($uploads as $cat => $v) {
                $written[] = store_upload($pdo, $id, $cat, $v);
            }
            $pdo->commit();
            flash('success', 'Student added successfully.');
            redirect('view_student.php?id=' . $id);
        } catch (Throwable $ex) {                                      // PDOException or file errors
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            cleanup_files($written);
            if ($ex instanceof PDOException && is_duplicate_key($ex)) {
                $errors[] = 'This roll number or email already exists.';
            } else {
                error_log('add_student.php: ' . $ex->getMessage());
                $errors[] = 'Could not save the student because of a server problem. Nothing was saved.';
            }
        }
    }
}

$pageTitle = 'Add student';
$active = 'add';
$isEdit = false;
require __DIR__ . '/includes/header.php';
?>
<div class="page-title"><h1>Add student</h1></div>
<section class="card">
  <?php if ($errors): ?>
    <div class="alert error" role="alert"><strong>Please correct the following:</strong>
      <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>
  <form method="post" action="add_student.php" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <?php require __DIR__ . '/includes/student_form.php'; ?>
    <div class="btn-row">
      <button class="btn" type="submit">Save student</button>
      <a class="btn secondary" href="view_students.php">Cancel</a>
    </div>
  </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
