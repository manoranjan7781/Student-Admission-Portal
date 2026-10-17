<?php
/**
 * Page header + navigation. Set $pageTitle and $active BEFORE including this file.
 * Everything printed with e() is HTML-escaped.
 */
$pageTitle = $pageTitle ?? 'Admission Portal';
$active = $active ?? '';
$user = current_user();
$nav = static function (string $key, string $href, string $label) use ($active): string {
    $cur = $key === $active ? ' aria-current="page"' : '';
    return '<a href="' . e($href) . '"' . $cur . '>' . e($label) . '</a>';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | Admission Portal (PHP)</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
  <div class="bar">
    <a class="brand" href="index.php">Admission Portal &middot; PHP</a>
    <nav aria-label="Main">
      <?php if ($user && $user['role'] === 'admin'): ?>
        <?= $nav('home', 'index.php', 'Dashboard') ?>
        <?= $nav('students', 'view_students.php', 'Students') ?>
        <?= $nav('add', 'add_student.php', 'Add student') ?>
      <?php elseif ($user): ?>
        <?= $nav('home', 'index.php', 'My profile') ?>
      <?php else: ?>
        <?= $nav('register', '../frontend/index.html', 'Register') ?>
        <?= $nav('login', 'login.php', 'Login') ?>
      <?php endif; ?>
      <?php if ($user): ?>
        <span class="who"><?= e($user['username']) ?> <span class="badge <?= e($user['role']) ?>"><?= e($user['role']) ?></span></span>
        <!-- Logout is a POST + CSRF token so another site cannot log you out by an image tag -->
        <form action="logout.php" method="post" class="inline">
          <?= csrf_field() ?>
          <button type="submit" class="linklike">Log out</button>
        </form>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
<?php foreach (pull_flashes() as $f): ?>
  <div class="alert <?= $f['type'] === 'success' ? 'success' : 'error' ?>" role="<?= $f['type'] === 'success' ? 'status' : 'alert' ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>
