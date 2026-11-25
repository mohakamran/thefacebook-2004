<?php
require __DIR__ . '/../src/bootstrap.php';

page_start('Privacy Policy', 'Privacy');
?>
<h1 class="page-heading">[ Privacy ]</h1>
<div class="narrow">
<?php box_start('What we store'); ?>
  <p>Everything you enter (your name, email address, profile fields, messages, pokes and pictures) is stored in a SQLite database file on the computer running this recreation, in the <code>data/</code> folder. Pictures are stored in <code>public/uploads/</code>.</p>
  <p>Passwords are stored as one-way hashes. Nothing is sent to any outside service, and no email is ever sent.</p>
<?php box_end(); ?>
<?php box_start('Who can see your profile'); ?>
  <p>Only logged-in members can see profiles. You can limit your profile to confirmed friends on the <a href="privacy-settings.php">My Privacy</a> page.</p>
<?php box_end(); ?>
</div>
<?php
page_end();
