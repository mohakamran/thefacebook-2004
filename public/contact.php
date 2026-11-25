<?php
require __DIR__ . '/../src/bootstrap.php';

page_start('Contact Thefacebook', 'Contact');
?>
<h1 class="page-heading">[ Contact ]</h1>
<div class="narrow">
<?php box_start('Get in touch'); ?>
  <p>This is a local, unofficial recreation, so there is nobody on the other end of a contact form.</p>
  <p>If you are running it yourself, questions about the code are best answered by reading the <code>README.md</code> in the project folder.</p>
  <?php if (current_user()): ?>
    <p>To talk to other members, use <a href="messages.php">My Messages</a>.</p>
  <?php endif; ?>
<?php box_end(); ?>
</div>
<div class="center"><a href="index.php" class="inputsubmit">Home</a></div>
<?php
page_end();
