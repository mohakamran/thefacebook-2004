<?php
require __DIR__ . '/../src/bootstrap.php';

if (current_user()) redirect('index.php');

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $email = mb_strtolower(post_str('email', 120));
    $password = (string)($_POST['password'] ?? '');

    $st = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $st->execute([$email]);
    $row = $st->fetch();
    if ($row && password_verify($password, $row['password_hash'])) {
        if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
        }
        log_in((int)$row['id']);
        redirect('index.php');
    }
    $error = 'Incorrect email or password.';
}

page_start('Login');
?>
<h1 class="page-heading">[ Login ]</h1>
<?php if ($error): ?><div class="flash flash-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<div class="flash flash-error js-errors" role="alert" hidden></div>
<form method="post" action="login.php" class="formgrid" data-validate="login" novalidate>
  <?= csrf_field() ?>
  <label for="l-email">Email:</label>
  <input type="email" id="l-email" name="email" class="inputtext" value="<?= e($email) ?>" autocomplete="username" required>
  <label for="l-pass">Password:</label>
  <input type="password" id="l-pass" name="password" class="inputtext" autocomplete="current-password" required>
  <div class="formgrid-full center btnrow">
    <button type="submit" class="inputsubmit">Login</button>
    <a href="register.php" class="inputsubmit">Register</a>
  </div>
</form>
<?php
page_end();
