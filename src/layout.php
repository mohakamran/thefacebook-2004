<?php
/**
 * Shared page chrome: header band, sidebar, main box, footer.
 */
declare(strict_types=1);

function page_start(string $mainTitle, ?string $docTitle = null, string $titleRight = ''): void
{
    $u = current_user();
    $docTitle = $docTitle ?? $mainTitle;
    ?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Thefacebook | <?= e($docTitle) ?></title>
<script>document.documentElement.className = 'js';</script>
<link rel="stylesheet" href="assets/style.css">
<link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
<script src="assets/app.js" defer></script>
</head>
<body>
<div id="page">
  <div id="header">
    <a href="index.php" id="header-art" aria-hidden="true" tabindex="-1"></a>
    <div id="header-right">
      <a href="index.php" id="wordmark">[ thefacebook ]</a>
      <button type="button" id="nav-toggle" aria-expanded="false" aria-controls="nav">menu</button>
      <ul id="nav">
      <?php if ($u): ?>
        <li><a href="index.php">home</a></li>
        <li><a href="search.php">search</a></li>
        <li><a href="global.php">global</a></li>
        <li><a href="socialnet.php">social net</a></li>
        <li><a href="invite.php">invite</a></li>
        <li><a href="faq.php">faq</a></li>
        <li><form method="post" action="logout.php" class="inline-form"><?= csrf_field() ?><button type="submit" class="linklike">logout</button></form></li>
      <?php else: ?>
        <li><a href="index.php">login</a></li>
        <li><a href="register.php">register</a></li>
        <li><a href="about.php">about</a></li>
      <?php endif; ?>
      </ul>
    </div>
  </div>
  <div id="columns">
    <div id="sidebar">
    <?php if ($u): ?>
      <div class="sidebox">
        <form method="get" action="search.php" class="quicksearch" role="search">
          <label for="qs" class="visually-hidden">Quick search</label>
          <input type="text" id="qs" name="q" class="inputtext" value="">
          <div class="qs-row"><span>quick search</span> <button type="submit" class="inputsubmit">go</button></div>
        </form>
      </div>
      <div class="sidebox">
        <ul class="mylinks">
          <?php $pending = pending_request_count((int)$u['id']); $unread = unread_count((int)$u['id']); ?>
          <li><a href="profile.php">My Profile</a> <a href="edit.php">[ edit ]</a></li>
          <li><a href="friends.php">My Friends</a><?= $pending ? ' <span class="count">(' . $pending . ')</span>' : '' ?></li>
          <li><a href="groups.php">My Groups</a></li>
          <li><a href="parties.php">My Parties</a></li>
          <li><a href="messages.php">My Messages</a><?= $unread ? ' <span class="count">(' . $unread . ')</span>' : '' ?></li>
          <li><a href="account.php">My Account</a></li>
          <li><a href="privacy-settings.php">My Privacy</a></li>
        </ul>
      </div>
    <?php else: ?>
      <div class="sidebox">
        <form method="post" action="login.php" class="sidelogin" data-validate="login" novalidate>
          <?= csrf_field() ?>
          <label for="side-email">Email:</label>
          <input type="email" id="side-email" name="email" class="inputtext" autocomplete="username" required>
          <label for="side-pass">Password:</label>
          <input type="password" id="side-pass" name="password" class="inputtext" autocomplete="current-password" required>
          <div class="btnrow">
            <a href="register.php" class="inputsubmit">register</a>
            <button type="submit" class="inputsubmit">login</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
    </div>
    <div id="main">
      <div class="main-title"><span><?= e($mainTitle) ?></span><?php if ($titleRight !== ''): ?><span class="title-right"><?= e($titleRight) ?></span><?php endif; ?></div>
      <div class="main-body">
      <?php foreach (take_flashes() as $f): ?>
        <div class="flash flash-<?= e($f['kind']) ?>" role="status"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>
<?php
}

function page_end(): void
{
    ?>
      </div>
    </div>
  </div>
  <div id="footer">
    <div class="footlinks">
      <a href="about.php">about</a>
      <a href="contact.php">contact</a>
      <a href="faq.php">faq</a>
      <a href="terms.php">terms</a>
      <a href="privacy.php">privacy</a>
    </div>
    <div>a Mark Zuckerberg production</div>
    <div>Thefacebook &copy; 2004</div>
    <div class="unofficial">Unofficial fan recreation for learning purposes &middot; not affiliated with Meta</div>
  </div>
</div>
</body>
</html>
<?php
}

/** A sub-box with a blue header bar, as used all over the 2004 site. */
function box_start(string $title, string $class = '', string $id = ''): void
{
    echo '<div class="box ' . e($class) . '"' . ($id !== '' ? ' id="' . e($id) . '"' : '') . '>';
    echo '<div class="box-title">' . e($title) . '</div><div class="box-body">';
}

function box_end(): void
{
    echo '</div></div>';
}

/** A link to a person's profile. */
function user_link(array $u): string
{
    return '<a href="profile.php?id=' . (int)$u['id'] . '">' . e($u['name']) . '</a>';
}

/** Small user card with avatar, used in lists. */
function user_card(array $u, string $extra = ''): string
{
    // Name and picture are always listed; other details follow the person's privacy setting.
    $details = can_view_profile(current_user(), $u)
        ? '<div class="muted">' . e($u['status']) . ($u['class_year'] ? " '" . e(substr($u['class_year'], -2)) : '') . '</div>'
          . ($u['residence'] ? '<div class="muted">' . e($u['residence']) . '</div>' : '')
        : '<div class="muted">Profile visible to friends only</div>';
    return '<div class="usercard"><a href="profile.php?id=' . (int)$u['id'] . '"><img src="' . e(avatar_url($u)) . '" alt="" width="50" height="50"></a>'
        . '<div class="usercard-info">' . user_link($u) . $details . $extra . '</div></div>';
}
