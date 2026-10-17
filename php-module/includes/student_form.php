<?php
/**
 * Shared student form fields (used by add_student.php and edit_student.php).
 * Expects: $values (array), $isEdit (bool). Output is escaped with e().
 */
?>
<div class="form-grid two">
  <div class="field">
    <label for="name">Full name <span class="req">*</span></label>
    <input type="text" id="name" name="name" maxlength="100" required value="<?= e($values['name']) ?>">
  </div>
  <div class="field">
    <label for="roll_number">Roll number <span class="req">*</span></label>
    <input type="text" id="roll_number" name="roll_number" maxlength="20" required value="<?= e($values['roll_number']) ?>">
    <span class="hint">4-20 letters, digits, - or /</span>
  </div>
  <div class="field">
    <label for="email">Email <span class="req">*</span></label>
    <input type="email" id="email" name="email" maxlength="150" required value="<?= e($values['email']) ?>">
  </div>
  <div class="field">
    <label for="course">Course <span class="req">*</span></label>
    <select id="course" name="course" required>
      <option value="">-- Select a course --</option>
      <?php foreach (COURSES as $c): ?>
        <option value="<?= e($c) ?>" <?= $values['course'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="password"><?= $isEdit ? 'New password' : 'Password <span class="req">*</span>' ?></label>
    <input type="password" id="password" name="password" maxlength="72" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
    <span class="hint"><?= $isEdit ? 'Leave blank to keep the current password. ' : '' ?>8+ chars, upper &amp; lower case, digit, symbol.</span>
  </div>
  <div class="field">
    <label for="confirm_password">Confirm password</label>
    <input type="password" id="confirm_password" name="confirm_password" maxlength="72" autocomplete="new-password">
  </div>
  <div class="field">
    <label for="profile_image">Profile image</label>
    <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/webp">
    <span class="hint">JPG/PNG/WebP, max 2 MB<?= $isEdit ? '. A new image replaces the current one.' : '.' ?></span>
  </div>
  <div class="field">
    <label for="id_proof">ID proof</label>
    <input type="file" id="id_proof" name="id_proof" accept="application/pdf,image/jpeg,image/png,image/webp">
    <span class="hint">PDF/JPG/PNG/WebP, max 5 MB</span>
  </div>
  <div class="field">
    <label for="certificate">Certificate</label>
    <input type="file" id="certificate" name="certificate" accept="application/pdf,image/jpeg,image/png,image/webp">
    <span class="hint">PDF/JPG/PNG/WebP, max 5 MB</span>
  </div>
</div>
