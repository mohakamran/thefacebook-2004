<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$uid = (int)$me['id'];
$errors = [];
$name = $desc = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $name = post_str('name', 80);
    $desc = post_str('description', 2000);
    if (mb_strlen($name) < 3) $errors[] = 'Group names must be at least 3 characters.';
    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO tfb_groups (name, description, created_by, created_at) VALUES (?,?,?,?)')->execute([$name, $desc, $uid, now()]);
        $gid = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO group_members (group_id, user_id, title, joined_at) VALUES (?,?,?,?)')->execute([$gid, $uid, 'founder', now()]);
        $pdo->commit();
        flash('Your group has been created.');
        redirect('group.php?id=' . $gid);
    }
}

$all = db()->query('SELECT g.*, COUNT(m.user_id) AS members FROM tfb_groups g LEFT JOIN group_members m ON m.group_id = g.id
    GROUP BY g.id ORDER BY members DESC, g.name')->fetchAll();
$mine = db()->prepare('SELECT group_id FROM group_members WHERE user_id = ?');
$mine->execute([$uid]);
$mine = array_map('intval', $mine->fetchAll(PDO::FETCH_COLUMN));

page_start('My Groups');
?>
<?php box_start('My Groups'); ?>
  <?php $myGroups = array_filter($all, fn($g) => in_array((int)$g['id'], $mine, true)); ?>
  <?php if ($myGroups): ?>
    <ul class="bullets">
      <?php foreach ($myGroups as $g): ?><li><a href="group.php?id=<?= (int)$g['id'] ?>"><?= e($g['name']) ?></a> <span class="muted">(<?= (int)$g['members'] ?> people)</span></li><?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p>You are not in any groups yet. Join one below, or start your own.</p>
  <?php endif; ?>
<?php box_end(); ?>

<?php box_start('All Groups at ' . SCHOOL_NAME); ?>
  <ul class="bullets">
    <?php foreach ($all as $g): ?><li><a href="group.php?id=<?= (int)$g['id'] ?>"><?= e($g['name']) ?></a> <span class="muted">(<?= (int)$g['members'] ?> people)</span></li><?php endforeach; ?>
  </ul>
<?php box_end(); ?>

<?php box_start('Create a Group'); ?>
  <?php if ($errors): ?><div class="flash flash-error" role="alert"><?php foreach ($errors as $err) echo '<div>' . e($err) . '</div>'; ?></div><?php endif; ?>
  <form method="post" action="groups.php" class="formgrid">
    <?= csrf_field() ?>
    <label for="g-name">Group Name:</label>
    <input type="text" id="g-name" name="name" class="inputtext" maxlength="80" value="<?= e($name) ?>" required minlength="3">
    <label for="g-desc">Description:</label>
    <textarea id="g-desc" name="description" class="inputtext" rows="4" maxlength="2000"><?= e($desc) ?></textarea>
    <div class="formgrid-full center"><button type="submit" class="inputsubmit">Create Group</button></div>
  </form>
<?php box_end(); ?>
<?php
page_end();
