<?php
require __DIR__ . '/../src/bootstrap.php';

require_login();
page_start('My Parties');
?>
<?php box_start('Upcoming Parties'); ?>
  <p>You have no upcoming parties.</p>
  <p class="muted">Parties were not part of the February 2004 launch, so this page is just a placeholder. Try <a href="groups.php">groups</a> instead.</p>
<?php box_end(); ?>
<?php
page_end();
