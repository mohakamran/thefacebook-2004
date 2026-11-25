<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();

const SEARCH_FIELDS = [
    'status' => 'Status', 'sex' => 'Sex', 'class_year' => 'Year', 'concentration' => 'Concentration',
    'residence' => 'Residence', 'hometown' => 'Home Town', 'highschool' => 'High School', 'interests' => 'Interests',
];
const VISIBLE_SQL = "(u.privacy = 'everyone' OR u.id = :me OR EXISTS (SELECT 1 FROM friendships f WHERE f.status = 'accepted'
    AND ((f.requester_id = :me AND f.addressee_id = u.id) OR (f.addressee_id = :me AND f.requester_id = u.id))))";

function like(string $s): string
{
    return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s) . '%';
}

$q = get_str('q', 100);
$mode = get_str('mode', 20) === 'advanced' ? 'advanced' : 'basic';
$allFields = isset($_GET['all']);
$field = get_str('field', 30);
$results = null;
$where = [];
$params = [':me' => $me['id']];

if ($field !== '' && isset(SEARCH_FIELDS[$field]) && $q !== '') {
    // Clicked a value on someone's profile: exact match on that field among visible profiles.
    $where[] = "u.$field = :v COLLATE NOCASE";
    $where[] = VISIBLE_SQL;
    $params[':v'] = $q;
} elseif ($mode === 'advanced' && isset($_GET['go'])) {
    if (($n = get_str('name', 100)) !== '') { $where[] = "u.name LIKE :name ESCAPE '\\'"; $params[':name'] = like($n); }
    foreach (SEARCH_FIELDS as $k => $_) {
        $v = get_str($k, 100);
        if ($v === '') continue;
        $where[] = in_array($k, ['status', 'sex', 'class_year'], true) ? "u.$k = :$k" : "u.$k LIKE :$k ESCAPE '\\'";
        $params[":$k"] = in_array($k, ['status', 'sex', 'class_year'], true) ? $v : like($v);
    }
    if ($where) $where[] = VISIBLE_SQL;
} elseif ($q !== '') {
    if ($allFields) {
        $ors = ["u.name LIKE :q ESCAPE '\\'"];
        foreach (SEARCH_FIELDS as $k => $_) $ors[] = "u.$k LIKE :q ESCAPE '\\'";
        $where[] = '(' . implode(' OR ', $ors) . ')';
        $where[] = VISIBLE_SQL;
    } else {
        $where[] = "u.name LIKE :q ESCAPE '\\'";
    }
    $params[':q'] = like($q);
}

if ($where) {
    if (!str_contains(implode(' ', $where), ':me')) unset($params[':me']);
    $st = db()->prepare('SELECT u.* FROM users u WHERE ' . implode(' AND ', $where) . ' ORDER BY u.name LIMIT 100');
    $st->execute($params);
    $results = $st->fetchAll();
}

page_start($mode === 'advanced' ? 'Advanced Search' : 'Search');
?>
<?php if ($mode === 'basic'): ?>
  <form method="get" action="search.php" class="searchform" role="search">
    <label for="s-q">Search for:</label>
    <input type="text" id="s-q" name="q" class="inputtext" value="<?= e($q) ?>">
    <span class="btnrow">
      <button type="submit" class="inputsubmit">Search Names</button>
      <button type="submit" name="all" value="1" class="inputsubmit">Search all Fields</button>
    </span>
    <a href="search.php?mode=advanced" class="small">advanced search</a>
  </form>
<?php else: ?>
  <form method="get" action="search.php" class="formgrid">
    <input type="hidden" name="mode" value="advanced"><input type="hidden" name="go" value="1">
    <label for="a-name">Name:</label>
    <input type="text" id="a-name" name="name" class="inputtext" value="<?= e(get_str('name', 100)) ?>">
    <?php foreach (SEARCH_FIELDS as $k => $label): ?>
      <label for="a-<?= $k ?>"><?= e($label) ?>:</label>
      <?php if ($k === 'status' || $k === 'sex'): ?>
        <select id="a-<?= $k ?>" name="<?= $k ?>" class="inputtext select-short">
          <option value="">(any)</option>
          <?php foreach ($k === 'status' ? STATUSES : SEXES as $opt): ?>
            <option value="<?= e($opt) ?>"<?= get_str($k) === $opt ? ' selected' : '' ?>><?= e($opt) ?></option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <input type="text" id="a-<?= $k ?>" name="<?= $k ?>" class="inputtext" value="<?= e(get_str($k, 100)) ?>">
      <?php endif; ?>
    <?php endforeach; ?>
    <div class="formgrid-full center btnrow">
      <button type="submit" class="inputsubmit">Search</button>
      <a href="search.php" class="small">basic search</a>
    </div>
  </form>
<?php endif; ?>

<?php if ($results !== null): ?>
  <?php box_start('Search Results (' . count($results) . ')'); ?>
    <?php if ($field !== '' && isset(SEARCH_FIELDS[$field])): ?>
      <p class="muted">People whose <?= e(strtolower(SEARCH_FIELDS[$field])) ?> is "<?= e($q) ?>".</p>
    <?php endif; ?>
    <?php if (!$results): ?>
      <p>No one matched your search.</p>
    <?php else: ?>
      <div class="cardlist">
        <?php foreach ($results as $r) echo user_card($r); ?>
      </div>
    <?php endif; ?>
  <?php box_end(); ?>
<?php endif; ?>
<?php
page_end();
