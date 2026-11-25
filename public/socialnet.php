<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$uid = (int)$me['id'];

// Ten random people whose privacy settings let you see them (see FAQ: "What is the social net?").
$st = db()->prepare("SELECT u.* FROM users u WHERE u.id <> :me AND (u.privacy = 'everyone' OR EXISTS (
        SELECT 1 FROM friendships f WHERE f.status = 'accepted'
        AND ((f.requester_id = :me AND f.addressee_id = u.id) OR (f.addressee_id = :me AND f.requester_id = u.id))))
    ORDER BY RANDOM() LIMIT 10");
$st->execute([':me' => $uid]);
$random = $st->fetchAll();

// Visualization: you in the middle, friends in a ring, friends' friends in an outer ring.
$friends = friends_of($uid, 12);
$friendIds = array_map(fn($f) => (int)$f['id'], $friends);
$outer = [];
$edges = [];
foreach ($friends as $f) {
    foreach (friends_of((int)$f['id'], 20) as $ff) {
        $fid = (int)$ff['id'];
        if ($fid === $uid) continue;
        if (in_array($fid, $friendIds, true)) {
            $edges[] = [(int)$f['id'], $fid];
            continue;
        }
        if (!isset($outer[$fid]) && count($outer) < 16) $outer[$fid] = $ff;
        if (isset($outer[$fid])) $edges[] = [(int)$f['id'], $fid];
    }
}

$W = 520; $H = 400; $cx = $W / 2; $cy = $H / 2;
$pos = [$uid => [$cx, $cy]];
foreach (array_values($friends) as $i => $f) {
    $a = 2 * M_PI * $i / max(1, count($friends)) - M_PI / 2;
    $pos[(int)$f['id']] = [$cx + 105 * cos($a), $cy + 105 * sin($a)];
}
foreach (array_values($outer) as $i => $o) {
    $a = 2 * M_PI * $i / max(1, count($outer)) - M_PI / 2 + 0.2;
    $pos[(int)$o['id']] = [$cx + 185 * cos($a), $cy + 165 * sin($a)];
}

page_start('Social Net');
?>
<?php box_start('Your Social Network'); ?>
  <?php if (!$friends): ?>
    <p>Your network is empty. <a href="search.php">Find some friends</a> and come back to see a picture of it.</p>
  <?php else: ?>
    <p class="muted">You are in the middle. Your friends form the inner ring and their friends the outer ring. Click anyone to see their profile.</p>
    <svg class="socialviz" viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="Diagram of your social network">
      <g class="edges">
        <?php foreach ($friendIds as $fid): [$x1, $y1] = $pos[$uid]; [$x2, $y2] = $pos[$fid]; ?>
          <line x1="<?= round($x1) ?>" y1="<?= round($y1) ?>" x2="<?= round($x2) ?>" y2="<?= round($y2) ?>" class="e1"/>
        <?php endforeach; ?>
        <?php foreach ($edges as [$a, $b]): if (!isset($pos[$a], $pos[$b])) continue; [$x1, $y1] = $pos[$a]; [$x2, $y2] = $pos[$b]; ?>
          <line x1="<?= round($x1) ?>" y1="<?= round($y1) ?>" x2="<?= round($x2) ?>" y2="<?= round($y2) ?>" class="e2"/>
        <?php endforeach; ?>
      </g>
      <?php
      $nodes = array_merge([['u' => $me, 'cls' => 'me']], array_map(fn($f) => ['u' => $f, 'cls' => 'friend'], $friends), array_map(fn($o) => ['u' => $o, 'cls' => 'fof'], array_values($outer)));
      foreach ($nodes as $n): $u = $n['u']; [$x, $y] = $pos[(int)$u['id']]; ?>
        <a href="profile.php?id=<?= (int)$u['id'] ?>" class="node <?= $n['cls'] ?>">
          <circle cx="<?= round($x) ?>" cy="<?= round($y) ?>" r="<?= $n['cls'] === 'me' ? 14 : ($n['cls'] === 'friend' ? 10 : 7) ?>"/>
          <text x="<?= round($x) ?>" y="<?= round($y) + ($n['cls'] === 'me' ? 27 : 20) ?>"><?= e($n['cls'] === 'me' ? 'You' : first_name($u)) ?></text>
        </a>
      <?php endforeach; ?>
    </svg>
  <?php endif; ?>
<?php box_end(); ?>

<?php box_start('Ten Random People'); ?>
  <p class="muted">A random selection of people you can see. <a href="socialnet.php">Shuffle</a></p>
  <div class="cardlist">
    <?php foreach ($random as $r) echo user_card($r); ?>
  </div>
<?php box_end(); ?>
<?php
page_end();
