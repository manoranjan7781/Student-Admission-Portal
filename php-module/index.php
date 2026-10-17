<?php
/** Home: admins see a dashboard, students are sent straight to their own profile. */
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
$user = require_login();

if ($user['role'] === 'student') {
    redirect('view_student.php?id=' . (int) $user['student_id']);
}

$pdo = db();
$totals = $pdo->query('SELECT (SELECT COUNT(*) FROM students) AS students,
                              (SELECT COUNT(*) FROM student_files) AS files')->fetch();
$recent = $pdo->query('SELECT id, name, roll_number, course, created_at FROM students ORDER BY id DESC LIMIT 5')->fetchAll();

$pageTitle = 'Dashboard';
$active = 'home';
require __DIR__ . '/includes/header.php';
?>
<div class="page-title">
  <h1>Admin dashboard</h1>
  <a class="btn" href="add_student.php">Add student</a>
</div>
<div class="cards">
  <div class="card"><h2><?= (int) $totals['students'] ?></h2><p class="muted">Registered students</p></div>
  <div class="card"><h2><?= (int) $totals['files'] ?></h2><p class="muted">Uploaded files</p></div>
</div>
<h2 style="margin-top:1.5rem">Recently added</h2>
<div class="table-wrap card" style="padding:0">
  <table>
    <thead><tr><th>Name</th><th>Roll no.</th><th>Course</th><th>Added</th></tr></thead>
    <tbody>
    <?php if (!$recent): ?>
      <tr><td colspan="4" class="muted">No students yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($recent as $s): ?>
      <tr>
        <td><a href="view_student.php?id=<?= (int) $s['id'] ?>"><?= e($s['name']) ?></a></td>
        <td><?= e($s['roll_number']) ?></td>
        <td><?= e($s['course']) ?></td>
        <td><?= e($s['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
