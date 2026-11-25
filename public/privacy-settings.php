<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$options = [
    'everyone' => 'Everyone at ' . SCHOOL_NAME . ' who is logged in',
    'friends' => 'Only people I have confirmed as friends',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $v = post_str('privacy', 20);
    if (isset($options[$v])) {
        db()->prepare('UPDATE users SET privacy = ? WHERE id = ?')->execute([$v, $me['id']]);
        flash('Your privacy settings have been saved.');
    }
    redirect('privacy-settings.php');
}

page_start('My Privacy');
?>
<?php box_start('Who can see my profile?'); ?>
  <form method="post" action="privacy-settings.php">
    <?= csrf_field() ?>
    <?php foreach ($options as $k => $label): ?>
      <div class="checkrow">
        <input type="radio" id="pv-<?= $k ?>" name="privacy" value="<?= $k ?>"<?= $me['privacy'] === $k ? ' checked' : '' ?>>
        <label for="pv-<?= $k ?>"><?= e($label) ?></label>
      </div>
    <?php endforeach; ?>
    <p class="muted">Your name and picture always appear in search results so friends can find you. Everything else follows this setting. Your email address is only shown to confirmed friends.</p>
    <div class="center"><button type="submit" class="inputsubmit">Save</button></div>
  </form>
<?php box_end(); ?>
<?php
page_end();
