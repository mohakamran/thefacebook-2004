<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
require_post_csrf();
$uid = (int)$me['id'];

if ($rm = (int)($_POST['remove'] ?? 0)) {
    db()->prepare('DELETE FROM pokes WHERE id = ? AND pokee_id = ?')->execute([$rm, $uid]);
    redirect('index.php');
}

$target = find_user(get_int('id'));
if (!$target || (int)$target['id'] === $uid) redirect('index.php');

// Poking back clears the pokes you received from that person.
db()->prepare('DELETE FROM pokes WHERE poker_id = ? AND pokee_id = ?')->execute([$target['id'], $uid]);
// One outstanding poke per pair is plenty.
db()->prepare('DELETE FROM pokes WHERE poker_id = ? AND pokee_id = ?')->execute([$uid, $target['id']]);
db()->prepare('INSERT INTO pokes (poker_id, pokee_id, created_at) VALUES (?,?,?)')->execute([$uid, $target['id'], now()]);
flash('You have poked ' . $target['name'] . '.');
redirect('profile.php?id=' . (int)$target['id']);
