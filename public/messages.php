<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$uid = (int)$me['id'];
$errors = [];
$subject = $body = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $action = post_str('action', 20);
    if ($action === 'send') {
        $to = find_user(get_int('to'));
        $subject = post_str('subject', 120);
        $body = post_str('body', 5000);
        if (!$to) $errors[] = 'Please choose who to send the message to.';
        elseif ((int)$to['id'] === $uid) $errors[] = 'You cannot send a message to yourself.';
        if ($subject === '') $subject = '(no subject)';
        if ($body === '') $errors[] = 'Please write a message.';
        if (!$errors) {
            db()->prepare('INSERT INTO messages (sender_id, recipient_id, subject, body, created_at) VALUES (?,?,?,?,?)')
                ->execute([$uid, $to['id'], $subject, $body, now()]);
            flash('Your message to ' . $to['name'] . ' has been sent.');
            redirect('messages.php?box=sent');
        }
        $_GET['to'] = (string)get_int('to');
    } elseif ($action === 'delete') {
        $mid = get_int('id');
        db()->prepare('UPDATE messages SET deleted_by_recipient = 1 WHERE id = ? AND recipient_id = ?')->execute([$mid, $uid]);
        db()->prepare('UPDATE messages SET deleted_by_sender = 1 WHERE id = ? AND sender_id = ?')->execute([$mid, $uid]);
        flash('Message deleted.');
        redirect('messages.php' . (post_str('box', 10) === 'sent' ? '?box=sent' : ''));
    }
}

$box = get_str('box', 10) === 'sent' ? 'sent' : 'inbox';

function tabs(string $active): void
{
    $tabs = ['inbox' => ['Inbox', 'messages.php'], 'sent' => ['Sent Messages', 'messages.php?box=sent'], 'compose' => ['Compose', 'messages.php?to=0']];
    echo '<div class="tabs">';
    foreach ($tabs as $k => [$label, $href]) {
        echo $k === $active ? '<span class="tab active">' . e($label) . '</span>' : '<a class="tab" href="' . e($href) . '">' . e($label) . '</a>';
    }
    echo '</div>';
}

// ---- read a message ----
if (isset($_GET['id'])) {
    $st = db()->prepare('SELECT m.*, s.name AS sender_name, r.name AS recipient_name FROM messages m
        JOIN users s ON s.id = m.sender_id JOIN users r ON r.id = m.recipient_id
        WHERE m.id = ? AND ((m.recipient_id = ? AND m.deleted_by_recipient = 0) OR (m.sender_id = ? AND m.deleted_by_sender = 0))');
    $st->execute([get_int('id'), $uid, $uid]);
    $m = $st->fetch();
    if (!$m) redirect('messages.php');
    $incoming = (int)$m['recipient_id'] === $uid;
    if ($incoming && !$m['read_at']) {
        db()->prepare('UPDATE messages SET read_at = ? WHERE id = ?')->execute([now(), $m['id']]);
    }
    page_start('My Messages', 'Message');
    tabs($incoming ? 'inbox' : 'sent');
    box_start($m['subject']);
    ?>
    <table class="info">
      <tr><th>From:</th><td><a href="profile.php?id=<?= (int)$m['sender_id'] ?>"><?= e($m['sender_name']) ?></a></td></tr>
      <tr><th>To:</th><td><a href="profile.php?id=<?= (int)$m['recipient_id'] ?>"><?= e($m['recipient_name']) ?></a></td></tr>
      <tr><th>Sent:</th><td><?= e(fmt_datetime($m['created_at'])) ?></td></tr>
    </table>
    <div class="message-body"><?= nl2br(e($m['body'])) ?></div>
    <div class="dashed-top actions">
      <?php if ($incoming): ?><a href="messages.php?to=<?= (int)$m['sender_id'] ?>&amp;re=<?= (int)$m['id'] ?>">reply</a> :: <?php endif; ?>
      <form method="post" action="messages.php" class="inline-form" data-confirm="Delete this message?"><?= csrf_field() ?>
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
        <input type="hidden" name="box" value="<?= $incoming ? 'inbox' : 'sent' ?>">
        <button type="submit" class="linklike">delete</button></form>
    </div>
    <?php
    box_end();
    page_end();
    exit;
}

// ---- compose ----
if (isset($_GET['to'])) {
    $to = find_user(get_int('to'));
    if ($subject === '' && ($re = get_int('re'))) {
        $st = db()->prepare('SELECT subject FROM messages WHERE id = ? AND recipient_id = ?');
        $st->execute([$re, $uid]);
        if ($s = $st->fetchColumn()) $subject = str_starts_with($s, 'Re: ') ? $s : 'Re: ' . $s;
    }
    $friends = friends_of($uid);
    page_start('My Messages', 'Compose');
    tabs('compose');
    if ($errors) echo '<div class="flash flash-error" role="alert">' . implode('', array_map(fn($x) => '<div>' . e($x) . '</div>', $errors)) . '</div>';
    ?>
    <div class="flash flash-error js-errors" role="alert" hidden></div>
    <form method="post" action="messages.php" class="formgrid" data-validate="message" novalidate>
      <?= csrf_field() ?><input type="hidden" name="action" value="send">
      <label for="m-to">To:</label>
      <?php if ($to && (int)$to['id'] !== $uid): ?>
        <div><input type="hidden" name="to" value="<?= (int)$to['id'] ?>"><?= user_link($to) ?></div>
      <?php else: ?>
        <select id="m-to" name="to" class="inputtext" required>
          <option value="">Choose a friend…</option>
          <?php foreach ($friends as $f): ?><option value="<?= (int)$f['id'] ?>"><?= e($f['name']) ?></option><?php endforeach; ?>
        </select>
      <?php endif; ?>
      <label for="m-subject">Subject:</label>
      <input type="text" id="m-subject" name="subject" class="inputtext" maxlength="120" value="<?= e($subject) ?>">
      <label for="m-body">Message:</label>
      <textarea id="m-body" name="body" class="inputtext" rows="8" maxlength="5000" required><?= e($body) ?></textarea>
      <div class="formgrid-full center"><button type="submit" class="inputsubmit">Send Message</button></div>
    </form>
    <?php
    page_end();
    exit;
}

// ---- list ----
if ($box === 'sent') {
    $st = db()->prepare('SELECT m.*, u.id AS other_id, u.name AS other_name FROM messages m JOIN users u ON u.id = m.recipient_id
        WHERE m.sender_id = ? AND m.deleted_by_sender = 0 ORDER BY m.created_at DESC, m.id DESC');
} else {
    $st = db()->prepare('SELECT m.*, u.id AS other_id, u.name AS other_name FROM messages m JOIN users u ON u.id = m.sender_id
        WHERE m.recipient_id = ? AND m.deleted_by_recipient = 0 ORDER BY m.created_at DESC, m.id DESC');
}
$st->execute([$uid]);
$list = $st->fetchAll();

page_start('My Messages');
tabs($box);
?>
<?php if (!$list): ?>
  <p class="spaced">You have no <?= $box === 'sent' ? 'sent ' : '' ?>messages.</p>
<?php else: ?>
  <table class="msglist">
    <thead><tr><th><?= $box === 'sent' ? 'To' : 'From' ?></th><th>Subject</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach ($list as $m): $unread = $box === 'inbox' && !$m['read_at']; ?>
      <tr class="<?= $unread ? 'unread' : '' ?>">
        <td data-label="<?= $box === 'sent' ? 'To' : 'From' ?>"><a href="profile.php?id=<?= (int)$m['other_id'] ?>"><?= e($m['other_name']) ?></a></td>
        <td data-label="Subject"><a href="messages.php?id=<?= (int)$m['id'] ?>"><?= e($m['subject']) ?></a><?= $unread ? ' <span class="new">new</span>' : '' ?></td>
        <td data-label="Date" class="muted"><?= e(fmt_datetime($m['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php
page_end();
