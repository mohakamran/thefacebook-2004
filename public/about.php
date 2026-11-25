<?php
require __DIR__ . '/../src/bootstrap.php';

page_start('About Thefacebook', 'About');
?>
<h1 class="page-heading">[ About ]</h1>
<div class="narrow">
<?php box_start('The Project'); ?>
  <p>Thefacebook is an online directory that connects people through social networks at colleges and universities.</p>
<?php box_end(); ?>

<?php box_start('The People'); ?>
  <table class="people">
    <tr><th><a href="contact.php">Mark Zuckerberg</a></th><td>Founder, Master and Commander, Enemy of the State.</td></tr>
    <tr><th><a href="contact.php">Eduardo Saverin</a></th><td>Business Stuff, Corporate Stuff, Brazilian Affairs.</td></tr>
    <tr><th><a href="contact.php">Andrew McCollum</a></th><td>Graphic Art, General Rockstar.</td></tr>
  </table>
  <p class="spaced"><a href="contact.php">Contact us.</a></p>
  <p class="muted small">This page reproduces the 2004 site's credits for historical context. This recreation was not made by, and is not endorsed by, any of the people listed.</p>
<?php box_end(); ?>
</div>
<div class="center"><a href="index.php" class="inputsubmit">Home</a></div>
<?php
page_end();
