<?php
/** Login page: verifies the password hash and starts a session. */
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';

if (current_user() !== null) {
    redirect('index.php');
}

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, username, password_hash, role, student_id FROM users WHERE username = :u');
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch();

    // Always run password_verify, even for unknown users, so response time does not
    // reveal whether a username exists. (The dummy hash is a valid bcrypt string.)
    $hash = $row['password_hash'] ?? '$2y$10$d03eVQBZySdB2h6SE9phROwvmMUJINO5U4eZpsm/sGzEsnYC1IZ0y';
    $ok = password_verify($password, $hash) && $row !== false;

    if ($ok) {
        // Upgrade the stored hash automatically if PHP's default algorithm/cost has changed
        if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
            $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id')
                ->execute([':h' => password_hash($password, PASSWORD_DEFAULT), ':id' => $row['id']]);
        }
        $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute([':id' => $row['id']]);
        login_user($row);
        redirect('index.php');
    }
    // One generic message for both "no such user" and "wrong password"
    $error = 'Invalid username or password.';
}

$pageTitle = 'Login';
$active = 'login';
require __DIR__ . '/includes/header.php';
?>
<div class="card narrow">
  <h1>Log in</h1>
  <p class="muted">Administrators manage all students. Students can view their own profile and files.</p>
  <?php if ($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="login.php" novalidate>
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="field">
        <label for="username">Username or email</label>
        <input type="text" id="username" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
    </div>
    <div class="btn-row"><button class="btn" type="submit">Log in</button></div>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
