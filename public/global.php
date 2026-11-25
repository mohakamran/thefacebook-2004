<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$perPage = 20;
$page = max(1, get_int('page'));
$total = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$st = db()->prepare('SELECT * FROM users ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?');
$st->execute([$perPage, ($page - 1) * $perPage]);
$users = $st->fetchAll();

page_start('Global: Everyone at ' . SCHOOL_NAME, 'Global');
?>
<p><?= $total ?> people have joined Thefacebook at <?= e(SCHOOL_NAME) ?> so far. Newest members first.</p>
<div class="cardlist">
  <?php foreach ($users as $u) echo user_card($u, '<div class="muted small">joined ' . e(fmt_date($u['created_at'])) . '</div>'); ?>
</div>
<?php if ($pages > 1): ?>
  <div class="pager">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <?= $i === $page ? '<b>' . $i . '</b>' : '<a href="global.php?page=' . $i . '">' . $i . '</a>' ?>
    <?php endfor; ?>
  </div>
<?php endif; ?>
<?php
page_end();
