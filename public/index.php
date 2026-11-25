<?php
require __DIR__ . '/../src/bootstrap.php';

$u = current_user();

if (!$u) {
    page_start('Welcome to Thefacebook!', 'Welcome');
    ?>
    <h1 class="page-heading">[ Welcome to Thefacebook ]</h1>
    <p>Thefacebook is an online directory that connects people through social networks at colleges.</p>
    <p>We have opened up Thefacebook for popular consumption at <b><?= e(SCHOOL_NAME) ?> University</b>.</p>
    <p class="tight">You can use Thefacebook to:</p>
    <ul class="bullets">
      <li>Search for people at your school</li>
      <li>Find out who are in your classes</li>
      <li>Look up your friends' friends</li>
      <li>See a visualization of your social network</li>
    </ul>
    <p>To get started, click below to register. If you have already registered, you can log in.</p>
    <div class="center btnrow">
      <a href="register.php" class="inputsubmit">Register</a>
      <a href="login.php" class="inputsubmit">Login</a>
    </div>
    <?php
    page_end();
    exit;
}

// ---- logged-in home ----
$uid = (int)$u['id'];
$pokes = db()->prepare('SELECT p.id, p.created_at, u.id AS uid, u.name FROM pokes p JOIN users u ON u.id = p.poker_id
    WHERE p.pokee_id = ? ORDER BY p.created_at DESC');
$pokes->execute([$uid]);
$pokes = $pokes->fetchAll();
$pending = pending_request_count($uid);
$unread = unread_count($uid);
$friendCount = count(friends_of($uid));

page_start('Welcome to Thefacebook!', 'Home');
?>
<h1 class="page-heading">[ Welcome, <?= e(first_name($u)) ?> ]</h1>

<?php if ($pokes): box_start('Pokes'); ?>
  <?php foreach ($pokes as $p): ?>
    <div class="row-split">
      <span>You have been poked by <a href="profile.php?id=<?= (int)$p['uid'] ?>"><?= e($p['name']) ?></a>.</span>
      <span class="actions">
        <form method="post" action="poke.php" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['uid'] ?>"><button class="linklike">poke back</button></form>
        ::
        <form method="post" action="poke.php" class="inline-form"><?= csrf_field() ?><input type="hidden" name="remove" value="<?= (int)$p['id'] ?>"><button class="linklike">remove</button></form>
      </span>
    </div>
  <?php endforeach; ?>
<?php box_end(); endif; ?>

<?php box_start('Your Status'); ?>
  <ul class="bullets">
    <li><?php if ($pending): ?><a href="friends.php">You have <?= $pending ?> friend request<?= $pending > 1 ? 's' : '' ?> to confirm.</a><?php else: ?>No new friend requests.<?php endif; ?></li>
    <li><?php if ($unread): ?><a href="messages.php">You have <?= $unread ?> new message<?= $unread > 1 ? 's' : '' ?>.</a><?php else: ?>No new messages.<?php endif; ?></li>
    <li>You have <a href="friends.php"><?= $friendCount ?> friend<?= $friendCount === 1 ? '' : 's' ?></a> at <?= e(SCHOOL_NAME) ?>.</li>
    <?php if (empty($u['picture'])): ?><li><a href="edit.php">Upload a picture</a> so your friends can recognize you.</li><?php endif; ?>
  </ul>
<?php box_end(); ?>

<?php box_start('Things to do'); ?>
  <ul class="bullets">
    <li><a href="search.php">Search for people at your school</a></li>
    <li><a href="search.php?mode=advanced">Find out who are in your classes</a></li>
    <li><a href="friends.php">Look up your friends' friends</a></li>
    <li><a href="socialnet.php">See a visualization of your social network</a></li>
    <li><a href="groups.php">Browse groups</a></li>
  </ul>
<?php box_end(); ?>
<?php
page_end();
