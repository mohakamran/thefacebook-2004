<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$id = get_int('id') ?: (int)$me['id'];
$p = find_user($id);
if (!$p) {
    http_response_code(404);
    page_start('Profile not found');
    echo '<p>Sorry, we could not find that person.</p>';
    page_end();
    exit;
}

$self = (int)$p['id'] === (int)$me['id'];
$state = friendship_state((int)$me['id'], (int)$p['id']);
$visible = can_view_profile($me, $p);
$friends = friends_of((int)$p['id']);

$groups = db()->prepare('SELECT g.id, g.name FROM tfb_groups g JOIN group_members m ON m.group_id = g.id WHERE m.user_id = ? ORDER BY g.name');
$groups->execute([$p['id']]);
$groups = $groups->fetchAll();

function info_row(string $label, string $value, string $field = ''): void
{
    if ($value === '') return;
    $v = $field !== '' ? '<a href="search.php?field=' . e($field) . '&amp;q=' . e(rawurlencode($value)) . '">' . e($value) . '</a>' : e($value);
    echo '<tr><th>' . e($label) . ':</th><td>' . $v . '</td></tr>';
}

$memberSince = fmt_date($p['created_at']);
if (str_starts_with($p['created_at'], '2004-02-04')) $memberSince = 'The beginning (' . $memberSince . ')';

page_start($p['name'] . "'s Profile", $p['name'], $self ? 'This is you' : '');
?>
<div class="profile">
  <div class="profile-left">
    <?php box_start('Picture'); ?>
      <div class="center"><img src="<?= e(avatar_url($p)) ?>" alt="<?= e($p['name']) ?>" class="profile-pic"></div>
    <?php box_end(); ?>

    <ul class="actionlinks">
    <?php if ($self): ?>
      <li><a href="edit.php">Edit My Profile</a></li>
      <li><a href="friends.php">View My Friends</a></li>
    <?php else: ?>
      <li><a href="messages.php?to=<?= (int)$p['id'] ?>">Send <?= e(first_name($p)) ?> a Message</a></li>
      <li><form method="post" action="poke.php" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button type="submit" class="linklike">Poke <?= e(pronoun_obj($p)) ?>!</button></form></li>
    <?php endif; ?>
    </ul>

    <?php if (!$self): box_start('Connection'); ?>
      <?php if ($state === 'friends'): ?>
        <p>You are friends with <?= e(first_name($p)) ?>.</p>
        <form method="post" action="friends.php" class="inline-form" data-confirm="Remove <?= e($p['name']) ?> from your friends?"><?= csrf_field() ?>
          <input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button type="submit" class="linklike">Remove from friends</button></form>
      <?php elseif ($state === 'sent'): ?>
        <p>You have asked <?= e(first_name($p)) ?> to be your friend. Waiting for confirmation.</p>
      <?php elseif ($state === 'received'): ?>
        <p><?= e(first_name($p)) ?> wants to be your friend.</p>
        <form method="post" action="friends.php" class="inline-form"><?= csrf_field() ?>
          <input type="hidden" name="action" value="confirm"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button type="submit" class="inputsubmit">Confirm</button></form>
      <?php else: ?>
        <p>You are not friends with <?= e(first_name($p)) ?>.</p>
        <form method="post" action="friends.php" class="inline-form"><?= csrf_field() ?>
          <input type="hidden" name="action" value="add"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button type="submit" class="inputsubmit">Add to Friends</button></form>
      <?php endif; ?>
    <?php box_end(); endif; ?>

    <?php if ($visible): box_start('Friends at ' . SCHOOL_NAME); ?>
      <?php if ($friends): ?>
        <p class="muted"><?= e(first_name($p)) ?> has <?= count($friends) ?> friend<?= count($friends) === 1 ? '' : 's' ?>.</p>
        <div class="friendgrid">
        <?php foreach (array_slice($friends, 0, 6) as $f): ?>
          <a href="profile.php?id=<?= (int)$f['id'] ?>"><img src="<?= e(avatar_url($f)) ?>" alt="" width="50" height="50"><span><?= e($f['name']) ?></span></a>
        <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="muted">No friends yet.</p>
      <?php endif; ?>
    <?php box_end(); endif; ?>
  </div>

  <div class="profile-right">
    <?php box_start('Information'); ?>
    <?php if ($visible): ?>
      <table class="info">
        <tr><td colspan="2" class="info-head">Account Info:</td></tr>
        <?php info_row('Name', $p['name']); info_row('Member Since', $memberSince); info_row('Last Update', fmt_date($p['updated_at'])); ?>
        <tr><td colspan="2" class="info-head">Basic Info:</td></tr>
        <?php
        info_row('School', SCHOOL_NAME);
        info_row('Status', $p['status'], 'status');
        info_row('Sex', $p['sex'], 'sex');
        info_row('Year', $p['class_year'], 'class_year');
        info_row('Concentration', $p['concentration'], 'concentration');
        info_row('Residence', $p['residence'], 'residence');
        info_row('Birthday', $p['birthday']);
        info_row('Home Town', $p['hometown'], 'hometown');
        info_row('High School', $p['highschool'], 'highschool');
        ?>
        <?php if ($p['looking_for'] || $p['interests'] || $p['about']): ?>
        <tr><td colspan="2" class="info-head">Personal Info:</td></tr>
        <?php info_row('Looking For', $p['looking_for']); info_row('Interests', $p['interests']); ?>
        <?php if ($p['about']): ?><tr><th>About Me:</th><td><?= nl2br(e($p['about'])) ?></td></tr><?php endif; ?>
        <?php endif; ?>
        <?php if ($state === 'friends' || $self): ?>
        <tr><td colspan="2" class="info-head">Contact Info:</td></tr>
        <tr><th>Email:</th><td><?= e($p['email']) ?></td></tr>
        <?php endif; ?>
      </table>
    <?php else: ?>
      <p><?= e($p['name']) ?> only shares their profile with friends. Add <?= e(first_name($p)) ?> as a friend to see more.</p>
    <?php endif; ?>
    <?php box_end(); ?>

    <?php if ($visible && $groups): box_start('Groups'); ?>
      <ul class="bullets">
        <?php foreach ($groups as $g): ?><li><a href="group.php?id=<?= (int)$g['id'] ?>"><?= e($g['name']) ?></a></li><?php endforeach; ?>
      </ul>
    <?php box_end(); endif; ?>
  </div>
</div>
<?php
page_end();
