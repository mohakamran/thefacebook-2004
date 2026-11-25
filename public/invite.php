<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$scheme = (!empty($_SERVER['HTTPS']) ? 'https' : 'http');
$host = preg_replace('/[^A-Za-z0-9.:\[\]-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost:8000');
$link = $scheme . '://' . $host . '/register.php';

page_start('Invite Your Friends', 'Invite');
?>
<h1 class="page-heading">[ Invite ]</h1>
<div class="narrow">
<?php box_start('Invite your friends to Thefacebook'); ?>
  <p>This local recreation never sends email. To invite someone on the same network, copy the message below and send it yourself.</p>
  <label for="invite-text" class="visually-hidden">Invitation text</label>
  <textarea id="invite-text" class="inputtext fullwidth" rows="5" readonly><?= e(first_name($me)) ?> wants you to join Thefacebook, an online directory that connects people through social networks at colleges.

Register here: <?= e($link) ?></textarea>
  <div class="center"><button type="button" class="inputsubmit" data-copy="invite-text">Copy to clipboard</button></div>
<?php box_end(); ?>
</div>
<?php
page_end();
