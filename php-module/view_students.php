<?php
/** READ (list): admin sees all students with search and paging. */
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require_admin();

$pdo = db();
$q = trim((string) ($_GET['q'] ?? ''));
$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));

// LIKE pattern is built in PHP and passed as a bound parameter (never concatenated into SQL)
$where = '';
$params = [];
if ($q !== '') {
    // Real prepared statements need a UNIQUE placeholder name per use, hence :q1..:q4
    $where = 'WHERE s.name LIKE :q1 OR s.roll_number LIKE :q2 OR s.email LIKE :q3 OR s.course LIKE :q4';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $params = [':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like];
}

$count = $pdo->prepare("SELECT COUNT(*) FROM students s $where");
$count->execute($params);
$total = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$sql = "SELECT s.id, s.name, s.roll_number, s.email, s.course,
          (SELECT f.id FROM student_files f WHERE f.student_id = s.id AND f.category = 'profile_image'
             ORDER BY f.id DESC LIMIT 1) AS photo_id,
          (SELECT COUNT(*) FROM student_files f WHERE f.student_id = s.id) AS file_count
        FROM students s $where ORDER BY s.name ASC LIMIT :lim OFFSET :off";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll();

$pageTitle = 'Students';
$active = 'students';
require __DIR__ . '/includes/header.php';
?>
<div class="page-title">
  <h1>Students <span class="muted">(<?= $total ?>)</span></h1>
  <a class="btn" href="add_student.php">Add student</a>
</div>

<form method="get" action="view_students.php" class="card" style="margin-bottom:1rem" role="search">
  <div class="field">
    <label for="q">Search by name, roll number, email or course</label>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
      <input type="search" id="q" name="q" value="<?= e($q) ?>" style="flex:1;min-width:200px">
      <button class="btn" type="submit">Search</button>
      <?php if ($q !== ''): ?><a class="btn secondary" href="view_students.php">Clear</a><?php endif; ?>
    </div>
  </div>
</form>

<div class="table-wrap card" style="padding:0">
  <table>
    <thead><tr><th>Photo</th><th>Name</th><th>Roll no.</th><th>Course</th><th>Files</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (!$students): ?>
      <tr><td colspan="6" class="muted">No students found.</td></tr>
    <?php endif; ?>
    <?php foreach ($students as $s): ?>
      <tr>
        <td>
          <?php if ($s['photo_id']): ?>
            <img class="avatar" src="download.php?id=<?= (int) $s['photo_id'] ?>" alt="Photo of <?= e($s['name']) ?>">
          <?php else: ?>
            <span class="avatar ph" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($s['name'], 0, 1))) ?></span>
          <?php endif; ?>
        </td>
        <td><a href="view_student.php?id=<?= (int) $s['id'] ?>"><?= e($s['name']) ?></a><br><span class="muted"><?= e($s['email']) ?></span></td>
        <td><?= e($s['roll_number']) ?></td>
        <td><?= e($s['course']) ?></td>
        <td><?= (int) $s['file_count'] ?></td>
        <td class="actions">
          <a class="btn small secondary" href="view_student.php?id=<?= (int) $s['id'] ?>">View</a>
          <a class="btn small secondary" href="edit_student.php?id=<?= (int) $s['id'] ?>">Edit</a>
          <!-- Delete is a POST form with a CSRF token; the confirm() is only a convenience -->
          <form class="inline" method="post" action="delete_student.php"
                onsubmit="return confirm('Delete <?= e(addslashes($s['name'])) ?> and all uploaded files? This cannot be undone.');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <button class="btn small danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($pages > 1): ?>
  <nav class="btn-row" aria-label="Pagination">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
      <a class="btn small <?= $p === $page ? '' : 'secondary' ?>" <?= $p === $page ? 'aria-current="page"' : '' ?>
         href="view_students.php?page=<?= $p ?>&amp;q=<?= urlencode($q) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </nav>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
