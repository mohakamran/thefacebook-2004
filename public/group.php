<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$uid = (int)$me['id'];
$gid = get_int('id');

$st = db()->prepare('SELECT * FROM tfb_groups WHERE id = ?');
$st->execute([$gid]);
$g = $st->fetch();
if (!$g) {
    http_response_code(404);
    page_start('Group not found');
    echo '<p>That group does not exist. <a href="groups.php">Back to groups</a>.</p>';
    page_end();
    exit;
}

function is_member(int $gid, int $uid): bool
{
    $st = db()->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
    $st->execute([$gid, $uid]);
    return (bool)$st->fetchColumn();
}

$member = is_member($gid, $uid);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    switch (post_str('action', 20)) {
        case 'join':
            db()->prepare('INSERT OR IGNORE INTO group_members (group_id, user_id, joined_at) VALUES (?,?,?)')->execute([$gid, $uid, now()]);
            flash('You have joined ' . $g['name'] . '.');
            break;
        case 'leave':
            db()->prepare('DELETE FROM group_members WHERE group_id = ? AND user_id = ?')->execute([$gid, $uid]);
            flash('The group was removed from My Groups.');
            break;
        case 'post':
            $topic = post_str('topic', 120);
            $body = post_str('body', 3000);
            if (!$member) { flash('Join the group to post on its message board.', 'error'); break; }
            if ($body === '') { flash('Please write something before posting.', 'error'); break; }
            db()->prepare('INSERT INTO group_posts (group_id, user_id, topic, body, created_at) VALUES (?,?,?,?,?)')->execute([$gid, $uid, $topic, $body, now()]);
            flash('Your post was added.');
            break;
        case 'message_all':
            $subject = post_str('subject', 120) ?: '[' . $g['name'] . ']';
            $body = post_str('body', 5000);
            if (!$member) { flash('Only members can message the group.', 'error'); break; }
            if ($body === '') { flash('Please write a message.', 'error'); break; }
            $ins = db()->prepare('INSERT INTO messages (sender_id, recipient_id, subject, body, created_at) VALUES (?,?,?,?,?)');
            $ids = db()->prepare('SELECT user_id FROM group_members WHERE group_id = ? AND user_id <> ?');
            $ids->execute([$gid, $uid]);
            $n = 0;
            foreach ($ids->fetchAll(PDO::FETCH_COLUMN) as $rid) { $ins->execute([$uid, $rid, $subject, $body, now()]); $n++; }
            flash("Your message was sent to $n member" . ($n === 1 ? '' : 's') . '.');
            break;
    }
    redirect('group.php?id=' . $gid);
}

$officers = db()->prepare("SELECT u.id, u.name, m.title FROM group_members m JOIN users u ON u.id = m.user_id WHERE m.group_id = ? AND m.title <> '' ORDER BY m.rowid");
$officers->execute([$gid]);
$officers = $officers->fetchAll();

$members = db()->prepare('SELECT u.* FROM group_members m JOIN users u ON u.id = m.user_id WHERE m.group_id = ? ORDER BY m.joined_at DESC LIMIT 6');
$members->execute([$gid]);
$members = $members->fetchAll();
$memberCount = db()->prepare('SELECT COUNT(*) FROM group_members WHERE group_id = ?');
$memberCount->execute([$gid]);
$memberCount = (int)$memberCount->fetchColumn();

$posts = db()->prepare('SELECT p.*, u.name FROM group_posts p JOIN users u ON u.id = p.user_id WHERE p.group_id = ? ORDER BY p.created_at DESC, p.id DESC LIMIT 20');
$posts->execute([$gid]);
$posts = $posts->fetchAll();

$related = db()->prepare('SELECT g.id, g.name, (SELECT COUNT(*) FROM group_members x WHERE x.group_id = g.id) AS people
    FROM tfb_groups g WHERE g.id <> :g AND EXISTS (
        SELECT 1 FROM group_members a JOIN group_members b ON a.user_id = b.user_id WHERE a.group_id = :g AND b.group_id = g.id)
    ORDER BY people DESC LIMIT 5');
$related->execute([':g' => $gid]);
$related = $related->fetchAll();

page_start($g['name'] . "'s Profile" . ($member ? ' (You are a member of this group)' : ''), $g['name']);
?>
<div class="profile group">
  <div class="profile-left wide">
    <?php box_start('Group Description'); ?>
      <p><?= nl2br(e($g['description'] ?: 'No description yet.')) ?></p>
      <p class="muted">Founded <?= e(fmt_date($g['created_at'])) ?>.</p>
    <?php box_end(); ?>

    <?php if ($officers): box_start('Officers'); ?>
      <table class="officers">
        <?php foreach ($officers as $o): ?>
          <tr><td><?= e($o['title']) ?></td><td class="right"><a href="profile.php?id=<?= (int)$o['id'] ?>"><?= e($o['name']) ?></a></td></tr>
        <?php endforeach; ?>
      </table>
    <?php box_end(); endif; ?>

    <?php box_start('Recent Discussion', '', 'board'); ?>
      <?php if (!$posts): ?><p class="muted">No one has posted yet.</p><?php endif; ?>
      <?php foreach ($posts as $p): ?>
        <div class="post">
          <?php if ($p['topic'] !== ''): ?>
            <div class="post-title"><?= e($p['topic']) ?></div>
            <div class="post-body">
              <em>Posted at <?= e(fmt_datetime($p['created_at'])) ?>.</em><br>
              <a href="profile.php?id=<?= (int)$p['user_id'] ?>"><?= e($p['name']) ?></a> <em>writes</em> "<?= nl2br(e($p['body'])) ?>"
          <?php else: ?>
            <div class="post-body">
              <div class="dashed-bottom"><a href="profile.php?id=<?= (int)$p['user_id'] ?>"><?= e($p['name']) ?></a> wrote at <?= e(fmt_datetime($p['created_at'])) ?>:</div>
              <?= nl2br(e($p['body'])) ?>
          <?php endif; ?>
              <div class="dashed-top actions">
                <a href="#reply">respond</a> :: <a href="messages.php?to=<?= (int)$p['user_id'] ?>">message</a> :: <a href="profile.php?id=<?= (int)$p['user_id'] ?>">view</a>
              </div>
            </div>
        </div>
      <?php endforeach; ?>

      <?php if ($member): ?>
        <form method="post" action="group.php?id=<?= $gid ?>" class="formgrid post-form" id="reply">
          <?= csrf_field() ?><input type="hidden" name="action" value="post">
          <label for="p-topic">Topic:</label>
          <input type="text" id="p-topic" name="topic" class="inputtext" maxlength="120" placeholder="(optional)">
          <label for="p-body">Post:</label>
          <textarea id="p-body" name="body" class="inputtext" rows="3" maxlength="3000" required></textarea>
          <div class="formgrid-full center"><button type="submit" class="inputsubmit">Post</button></div>
        </form>
      <?php else: ?>
        <p class="muted" id="reply">Join this group to post on its message board.</p>
      <?php endif; ?>
    <?php box_end(); ?>
  </div>

  <div class="profile-right narrow-col">
    <?php box_start('Picture'); ?>
      <div class="center"><img src="assets/group.svg" alt="" class="profile-pic"></div>
    <?php box_end(); ?>

    <ul class="actionlinks">
      <?php if ($member): ?>
        <li><a href="#message-all" data-toggle="message-all">Message All Members</a></li>
        <li><a href="#board">Message Board</a></li>
        <li><a href="groups.php">My Groups</a></li>
        <li><form method="post" action="group.php?id=<?= $gid ?>" class="inline-form" data-confirm="Remove this group from My Groups?"><?= csrf_field() ?><input type="hidden" name="action" value="leave"><button type="submit" class="linklike">Remove from My Groups</button></form></li>
      <?php else: ?>
        <li><form method="post" action="group.php?id=<?= $gid ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="join"><button type="submit" class="linklike">Join this Group</button></form></li>
        <li><a href="#board">Message Board</a></li>
      <?php endif; ?>
    </ul>

    <?php if ($member): ?>
      <div id="message-all" class="toggle-panel">
      <?php box_start('Message All Members'); ?>
        <form method="post" action="group.php?id=<?= $gid ?>" class="stacked">
          <?= csrf_field() ?><input type="hidden" name="action" value="message_all">
          <label for="ma-subject">Subject:</label>
          <input type="text" id="ma-subject" name="subject" class="inputtext" maxlength="120">
          <label for="ma-body">Message:</label>
          <textarea id="ma-body" name="body" class="inputtext" rows="4" required></textarea>
          <div class="center"><button type="submit" class="inputsubmit">Send to <?= $memberCount - 1 ?> member<?= $memberCount - 1 === 1 ? '' : 's' ?></button></div>
        </form>
      <?php box_end(); ?>
      </div>
    <?php endif; ?>

    <?php box_start('Related Groups'); ?>
      <?php if ($related): ?>
        <p>People in this group are also in:</p>
        <ul class="bullets">
          <?php foreach ($related as $r): ?><li><a href="group.php?id=<?= (int)$r['id'] ?>"><?= e($r['name']) ?></a> (<?= (int)$r['people'] ?> people)</li><?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="muted">No related groups yet.</p>
      <?php endif; ?>
    <?php box_end(); ?>

    <?php box_start('Members (' . $memberCount . ')'); ?>
      <div class="friendgrid">
        <?php foreach ($members as $m): ?>
          <a href="profile.php?id=<?= (int)$m['id'] ?>"><img src="<?= e(avatar_url($m)) ?>" alt="" width="50" height="50"><span><?= e($m['name']) ?></span></a>
        <?php endforeach; ?>
      </div>
    <?php box_end(); ?>
  </div>
</div>
<?php
page_end();
