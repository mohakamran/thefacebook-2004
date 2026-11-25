<?php
require __DIR__ . '/../src/bootstrap.php';

if (current_user()) redirect('index.php');

$errors = [];
$name = $email = $status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $name = post_str('name', 80);
    $status = post_str('status', 40);
    $email = mb_strtolower(post_str('email', 120));
    $password = (string)($_POST['password'] ?? '');
    $terms = !empty($_POST['terms']);

    if ($name === '' || mb_strlen($name) < 2) $errors[] = 'Please enter your full name.';
    if (!in_array($status, STATUSES, true)) $errors[] = 'Please choose your status.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (ALLOWED_EMAIL_DOMAIN !== '' && !str_ends_with($email, '@' . ALLOWED_EMAIL_DOMAIN)) {
        $errors[] = 'You must register with your @' . ALLOWED_EMAIL_DOMAIN . ' email address.';
    }
    if (strlen($password) < 6) $errors[] = 'Your password must be at least 6 characters.';
    if (strlen($password) > 200) $errors[] = 'Your password is too long.';
    if (!$terms) $errors[] = 'You must agree to the Terms of Use.';

    if (!$errors) {
        $st = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetchColumn()) $errors[] = 'An account with that email address already exists.';
    }

    if (!$errors) {
        $st = db()->prepare('INSERT INTO users (name, email, password_hash, status, created_at, updated_at) VALUES (?,?,?,?,?,?)');
        $st->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $status, now(), now()]);
        log_in((int)db()->lastInsertId());
        flash('Welcome to Thefacebook! You can now add more information and a picture to your profile.');
        redirect('edit.php');
    }
}

page_start('Registration');
?>
<p class="intro">To register for thefacebook.com, just fill in the four fields below. You will have a chance to enter additional information and submit a picture once you have registered.</p>

<?php if ($errors): ?>
  <div class="flash flash-error" role="alert"><?php foreach ($errors as $err) echo '<div>' . e($err) . '</div>'; ?></div>
<?php endif; ?>
<div class="flash flash-error js-errors" role="alert" hidden></div>

<form method="post" action="register.php" class="formgrid" data-validate="register" data-domain="<?= e(ALLOWED_EMAIL_DOMAIN) ?>" novalidate>
  <?= csrf_field() ?>
  <label for="r-name">Name:</label>
  <input type="text" id="r-name" name="name" class="inputtext" value="<?= e($name) ?>" maxlength="80" autocomplete="name" required>

  <label for="r-status">Status:</label>
  <select id="r-status" name="status" class="inputtext select-short" required>
    <option value=""></option>
    <?php foreach (STATUSES as $s): ?>
      <option value="<?= e($s) ?>"<?= $s === $status ? ' selected' : '' ?>><?= e($s) ?></option>
    <?php endforeach; ?>
  </select>

  <label for="r-email">Email: <span class="muted">(<?= e(strtolower(SCHOOL_NAME)) ?>)</span></label>
  <input type="email" id="r-email" name="email" class="inputtext" value="<?= e($email) ?>" maxlength="120" autocomplete="email" required>

  <label for="r-pass">Password*: <span class="muted">(not fas)</span></label>
  <input type="password" id="r-pass" name="password" class="inputtext" minlength="6" autocomplete="new-password" required>

  <div class="formgrid-full checkrow">
    <input type="checkbox" id="r-terms" name="terms" value="1" required>
    <label for="r-terms">I have read and understood the <a href="terms.php">Terms of Use</a>, and I agree to them.</label>
  </div>
  <div class="formgrid-full footnote">
    <span>*</span> You can choose any password. It does not have to be, and should not be, your FAS password.
  </div>
  <div class="formgrid-full center">
    <button type="submit" class="inputsubmit">Register Now!</button>
  </div>
</form>
<?php
page_end();
