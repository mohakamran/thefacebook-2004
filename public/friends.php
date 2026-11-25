<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$uid = (int)$me['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $action = post_str('action', 20);
    $other = get_int('id');
    $o = $other ? find_user($other) : null;
    if (!$o || $other === $uid) redirect('friends.php');
    $state = friendship_state($uid, $other);

    switch ($action) {
        case 'add':
            if ($state === 'none') {
                db()->prepare("INSERT INTO friendships (requester_id, addressee_id, status, created_at) VALUES (?,?,'pending',?)")
                    ->execute([$uid, $other, now()]);
                flash('A friend request has been sent to ' . $o['name'] . '.');
            } elseif ($state === 'received') {
                db()->prepare("UPDATE friendships SET status = 'accepted' WHERE requester_id = ? AND addressee_id = ?")->execute([$other, $uid]);
                flash('You are now friends with ' . $o['name'] . '.');
            }
            break;
        case 'confirm':
            if ($state === 'received') {
                db()->prepare("UPDATE friendships SET status = 'accepted' WHERE requester_id = ? AND addressee_id = ?")->execute([$other, $uid]);
                flash('You are now friends with ' . $o['name'] . '.');
            }
            break;
        case 'reject':   // the requester is never told (see FAQ)
        case 'cancel':
        case 'remove':
            db()->prepare('DELETE FROM friendships WHERE (requester_id = :a AND addressee_id = :b) OR (requester_id = :b AND addressee_id = :a)')
                ->execute([':a' => $uid, ':b' => $other]);
            flash(match ($action) { 'reject' => 'Request rejected.', 'cancel' => 'Request cancelled.', default => $o['name'] . ' was removed from your friends.' });
            break;
    }
    // Buttons on the My Friends page send back=friends; everything else returns to the person's profile.
    redirect(post_str('back', 20) === 'friends' ? 'friends.php' : 'profile.php?id=' . $other);
}

$incoming = db()->prepare("SELECT u.* FROM users u JOIN friendships f ON f.requester_id = u.id WHERE f.addressee_id = ? AND f.status = 'pending' ORDER BY f.created_at");
$incoming->execute([$uid]);
$incoming = $incoming->fetchAll();
$outgoing = db()->prepare("SELECT u.* FROM users u JOIN friendships f ON f.addressee_id = u.id WHERE f.requester_id = ? AND f.status = 'pending' ORDER BY f.created_at");
$outgoing->execute([$uid]);
$outgoing = $outgoing->fetchAll();
$friends = friends_of($uid);

// Friends' friends you are not yet connected to.
$fof = db()->prepare("WITH mine AS (
        SELECT CASE WHEN requester_id = :me THEN addressee_id ELSE requester_id END AS fid
        FROM friendships WHERE status = 'accepted' AND (requester_id = :me OR addressee_id = :me)),
    theirs AS (
        SELECT CASE WHEN f.requester_id = m.fid THEN f.addressee_id ELSE f.requester_id END AS uid
        FROM friendships f JOIN mine m ON (f.requester_id = m.fid OR f.addressee_id = m.fid) WHERE f.status = 'accepted')
    SELECT u.*, COUNT(*) AS mutual FROM theirs t JOIN users u ON u.id = t.uid
    WHERE t.uid <> :me AND t.uid NOT IN (SELECT fid FROM mine)
      AND NOT EXISTS (SELECT 1 FROM friendships x WHERE (x.requester_id = :me AND x.addressee_id = u.id) OR (x.addressee_id = :me AND x.requester_id = u.id))
    GROUP BY u.id ORDER BY mutual DESC, u.name LIMIT 8");
$fof->execute([':me' => $uid]);
$fof = $fof->fetchAll();

function friend_button(string $action, int $id, string $label, string $class = 'linklike'): string
{
    return '<form method="post" action="friends.php" class="inline-form">' . csrf_field()
        . '<input type="hidden" name="action" value="' . e($action) . '"><input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="back" value="friends">'
        . '<button type="submit" class="' . e($class) . '">' . e($label) . '</button></form>';
}

page_start('My Friends');
?>
<?php if ($incoming): box_start('Friend Requests'); ?>
  <p>These people have listed you as a friend. Confirm them to add them to your friends.</p>
  <div class="cardlist">
    <?php foreach ($incoming as $r) echo user_card($r, '<div class="card-actions">' . friend_button('confirm', (int)$r['id'], 'Confirm', 'inputsubmit') . ' ' . friend_button('reject', (int)$r['id'], 'Reject', 'inputsubmit inputbutton-grey') . '</div>'); ?>
  </div>
<?php box_end(); endif; ?>

<?php box_start('Friends (' . count($friends) . ')'); ?>
  <?php if ($friends): ?>
    <div class="cardlist">
      <?php foreach ($friends as $f) echo user_card($f, '<div class="card-actions"><a href="messages.php?to=' . (int)$f['id'] . '">message</a></div>'); ?>
    </div>
  <?php else: ?>
    <p>You have no friends yet. <a href="search.php">Search</a> for people you know.</p>
  <?php endif; ?>
<?php box_end(); ?>

<?php if ($outgoing): box_start('Awaiting Confirmation'); ?>
  <div class="cardlist">
    <?php foreach ($outgoing as $o) echo user_card($o, '<div class="card-actions">' . friend_button('cancel', (int)$o['id'], 'cancel request') . '</div>'); ?>
  </div>
<?php box_end(); endif; ?>

<?php if ($fof): box_start("Your Friends' Friends"); ?>
  <div class="cardlist">
    <?php foreach ($fof as $p) echo user_card($p, '<div class="muted">' . (int)$p['mutual'] . ' mutual friend' . ($p['mutual'] > 1 ? 's' : '') . '</div><div class="card-actions">' . friend_button('add', (int)$p['id'], 'add to friends') . '</div>'); ?>
  </div>
<?php box_end(); endif; ?>
<?php
page_end();
