<?php
/**
 * Processes the public registration form (frontend/index.html).
 * Creates: one students row + one users row (role "student") + optional profile image.
 * All client-side checks are repeated here because the browser can be bypassed.
 */
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../frontend/index.html');
}
csrf_verify();
if (!empty($_POST['website'])) {                       // honeypot filled in -> it is a bot
    show_error_page(400, 'Registration could not be processed.');
}

$pdo = db();
[$errors, $clean] = validate_student_input($pdo, $_POST, null, true);
[$uploadErrors, $uploads] = collect_uploads($_FILES, ['profile_image']);
$errors = array_merge($errors, $uploadErrors);

$stored = [];                                          // files written to disk (for cleanup)
if (!$errors) {
    try {
        $pdo->beginTransaction();                      // student + login + file metadata succeed or fail together
        $pdo->prepare('INSERT INTO students (name, roll_number, email, course) VALUES (:n, :r, :e, :c)')
            ->execute([':n' => $clean['name'], ':r' => $clean['roll_number'], ':e' => $clean['email'], ':c' => $clean['course']]);
        $studentId = (int) $pdo->lastInsertId();

        // The student's login: username = email, password stored ONLY as a bcrypt hash
        $pdo->prepare('INSERT INTO users (username, password_hash, role, student_id) VALUES (:u, :h, "student", :sid)')
            ->execute([':u' => $clean['email'], ':h' => password_hash($clean['password'], PASSWORD_DEFAULT), ':sid' => $studentId]);

        foreach ($uploads as $cat => $v) {
            $stored[] = store_upload($pdo, $studentId, $cat, $v);
        }
        $pdo->commit();
        flash('success', 'Registration successful! You can now log in with your email and password.');
        redirect('login.php');
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        cleanup_files($stored);
        if (is_duplicate_key($ex)) {
            $errors[] = 'This roll number or email is already registered.';
        } else {
            error_log('register.php: ' . $ex->getMessage());
            $errors[] = 'A database error occurred. Nothing was saved. Please try again later.';
        }
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        cleanup_files($stored);
        error_log('register.php: ' . $ex->getMessage());
        $errors[] = 'The file could not be saved. Nothing was registered. Please try again later.';
    }
}

// Validation or save failed: list the problems (the form data is not echoed back)
http_response_code(422);
$pageTitle = 'Registration problem';
$active = 'register';
require __DIR__ . '/includes/header.php';
?>
<div class="card narrow">
  <h1>Registration not completed</h1>
  <div class="alert error" role="alert">
    <strong>Please correct the following:</strong>
    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
  <a class="btn" href="../frontend/index.html">Back to the form</a>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
