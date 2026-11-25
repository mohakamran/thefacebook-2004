<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $current = (string)($_POST['current'] ?? '');
    $row = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $row->execute([$me['id']]);
    if (!password_verify($current, (string)$row->fetchColumn())) {
        $errors[] = 'Your current password is incorrect.';
    }
    $action = post_str('action', 20);
    if (!$errors && $action === 'name') {
        $name = post_str('name', 80);
        if (mb_strlen($name) < 2) $errors[] = 'Please enter your full name.';
        else {
            db()->prepare('UPDATE users SET name = ?, updated_at = ? WHERE id = ?')->execute([$name, now(), $me['id']]);
            flash('Your name has been changed.');
            redirect('account.php');
        }
    } elseif (!$errors && $action === 'password') {
        $new = (string)($_POST['new'] ?? '');
        if (strlen($new) < 6 || strlen($new) > 200) $errors[] = 'Your new password must be at least 6 characters.';
        elseif ($new !== (string)($_POST['confirm'] ?? '')) $errors[] = 'The new passwords do not match.';
        else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
            session_regenerate_id(true);
            flash('Your password has been changed.');
            redirect('account.php');
        }
    }
}

page_start('My Account');
?>
<?php if ($errors): ?><div class="flash flash-error" role="alert"><?php foreach ($errors as $err) echo '<div>' . e($err) . '</div>'; ?></div><?php endif; ?>
<?php box_start('Account Info'); ?>
  <table class="info">
    <tr><th>Name:</th><td><?= e($me['name']) ?></td></tr>
    <tr><th>Email:</th><td><?= e($me['email']) ?></td></tr>
    <tr><th>Member Since:</th><td><?= e(fmt_date($me['created_at'])) ?></td></tr>
  </table>
<?php box_end(); ?>

<?php box_start('Change Name'); ?>
  <form method="post" action="account.php" class="formgrid">
    <?= csrf_field() ?><input type="hidden" name="action" value="name">
    <label for="a-name">New Name:</label>
    <input type="text" id="a-name" name="name" class="inputtext" maxlength="80" value="<?= e($me['name']) ?>" required>
    <label for="a-cur1">Current Password:</label>
    <input type="password" id="a-cur1" name="current" class="inputtext" autocomplete="current-password" required>
    <div class="formgrid-full center"><button type="submit" class="inputsubmit">Change Name</button></div>
  </form>
<?php box_end(); ?>

<?php box_start('Change Password'); ?>
  <form method="post" action="account.php" class="formgrid">
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <label for="a-cur2">Current Password:</label>
    <input type="password" id="a-cur2" name="current" class="inputtext" autocomplete="current-password" required>
    <label for="a-new">New Password:</label>
    <input type="password" id="a-new" name="new" class="inputtext" autocomplete="new-password" minlength="6" required>
    <label for="a-conf">Confirm:</label>
    <input type="password" id="a-conf" name="confirm" class="inputtext" autocomplete="new-password" minlength="6" required>
    <div class="formgrid-full center"><button type="submit" class="inputsubmit">Change Password</button></div>
  </form>
<?php box_end(); ?>
<?php
page_end();
