<?php
require __DIR__ . '/../src/bootstrap.php';

$me = require_login();
$errors = [];
$fields = [
    'sex' => ['Sex', 20], 'class_year' => ['Year', 4], 'concentration' => ['Concentration', 80],
    'residence' => ['Residence', 80], 'birthday' => ['Birthday', 40], 'hometown' => ['Home Town', 80],
    'highschool' => ['High School', 80], 'looking_for' => ['Looking For', 120], 'interests' => ['Interests', 300],
    'about' => ['About Me', 2000],
];
$data = $me;

/** Validate an uploaded image, re-encode it with GD (strips metadata and any payload) and return the new filename. */
function save_picture(array $file, int $uid, array &$errors): ?string
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) { $errors[] = 'The picture could not be uploaded. Please try again.'; return null; }
    if ($file['size'] > MAX_UPLOAD_BYTES) { $errors[] = 'Pictures must be smaller than 2 MB.'; return null; }
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_GIF => 'imagecreatefromgif'];
    if (!$info || !isset($types[$info[2]])) { $errors[] = 'Pictures must be JPG, PNG or GIF images.'; return null; }
    [$w, $h] = $info;
    if ($w < 1 || $h < 1 || $w * $h > 25_000_000) { $errors[] = 'That picture is too large.'; return null; }
    $src = @$types[$info[2]]($file['tmp_name']);
    if (!$src) { $errors[] = 'That picture could not be read.'; return null; }

    $maxW = 200;
    $nw = min($maxW, $w);
    $nh = (int)round($h * $nw / $w);
    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0775, true);
    $name = 'u' . $uid . '_' . bin2hex(random_bytes(8)) . '.jpg';
    $ok = imagejpeg($dst, UPLOAD_DIR . '/' . $name, 85);
    if (!$ok) { $errors[] = 'The picture could not be saved.'; return null; }
    return $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    foreach ($fields as $k => [, $max]) $data[$k] = post_str($k, $max);
    if ($data['sex'] !== '' && !in_array($data['sex'], SEXES, true)) $errors[] = 'Please choose a valid option for Sex.';
    if ($data['class_year'] !== '' && !preg_match('/^(19|20)\d\d$/', $data['class_year'])) $errors[] = 'Year should look like 2006.';
    $status = post_str('status', 40);
    if (in_array($status, STATUSES, true)) $data['status'] = $status;

    $newPic = null;
    if (isset($_FILES['picture']) && is_array($_FILES['picture']) && !is_array($_FILES['picture']['error'])) {
        $newPic = $errors ? null : save_picture($_FILES['picture'], (int)$me['id'], $errors);
    }
    $removePic = !empty($_POST['remove_picture']);

    if (!$errors) {
        $pic = $me['picture'];
        if ($newPic || $removePic) {
            if ($pic && preg_match('/^u\d+_[0-9a-f]{16}\.jpg$/', $pic)) @unlink(UPLOAD_DIR . '/' . $pic);
            $pic = $newPic;
        }
        $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
        $st = db()->prepare("UPDATE users SET $sets, status = :status, picture = :picture, updated_at = :u WHERE id = :id");
        $params = [':status' => $data['status'], ':picture' => $pic, ':u' => now(), ':id' => $me['id']];
        foreach ($fields as $k => $_) $params[":$k"] = $data[$k];
        $st->execute($params);
        flash('Your profile has been updated.');
        redirect('profile.php');
    } elseif ($newPic) {
        @unlink(UPLOAD_DIR . '/' . $newPic);
    }
}

page_start('Edit My Profile');
?>
<?php if ($errors): ?>
  <div class="flash flash-error" role="alert"><?php foreach ($errors as $err) echo '<div>' . e($err) . '</div>'; ?></div>
<?php endif; ?>
<div class="flash flash-error js-errors" role="alert" hidden></div>

<form method="post" action="edit.php" enctype="multipart/form-data" class="formgrid" data-validate="edit" novalidate>
  <?= csrf_field() ?>
  <div class="formgrid-full"><?php box_start('Picture'); ?>
    <div class="pic-edit">
      <img src="<?= e(avatar_url($me)) ?>" alt="Your current picture" width="100">
      <div>
        <label for="e-pic">Upload a new picture (JPG, PNG or GIF, up to 2 MB):</label><br>
        <input type="file" id="e-pic" name="picture" accept="image/jpeg,image/png,image/gif">
        <?php if (!empty($me['picture'])): ?>
          <div class="checkrow"><input type="checkbox" id="e-rmpic" name="remove_picture" value="1"> <label for="e-rmpic">Remove my picture</label></div>
        <?php endif; ?>
      </div>
    </div>
  <?php box_end(); ?></div>

  <div class="formgrid-full section-head">Basic Info</div>
  <label for="e-status">Status:</label>
  <select id="e-status" name="status" class="inputtext select-short">
    <?php foreach (STATUSES as $s): ?><option value="<?= e($s) ?>"<?= $s === $data['status'] ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
  </select>
  <label for="e-sex">Sex:</label>
  <select id="e-sex" name="sex" class="inputtext select-short">
    <option value=""></option>
    <?php foreach (SEXES as $s): ?><option value="<?= e($s) ?>"<?= $s === $data['sex'] ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
  </select>
  <?php foreach (['class_year', 'concentration', 'residence', 'birthday', 'hometown', 'highschool'] as $k): ?>
    <label for="e-<?= $k ?>"><?= e($fields[$k][0]) ?>:</label>
    <input type="text" id="e-<?= $k ?>" name="<?= $k ?>" class="inputtext" value="<?= e($data[$k]) ?>" maxlength="<?= $fields[$k][1] ?>"<?= $k === 'class_year' ? ' inputmode="numeric" pattern="(19|20)[0-9]{2}" placeholder="2006"' : '' ?><?= $k === 'birthday' ? ' placeholder="May 14, 1985"' : '' ?>>
  <?php endforeach; ?>

  <div class="formgrid-full section-head">Personal Info</div>
  <label for="e-looking_for">Looking For:</label>
  <input type="text" id="e-looking_for" name="looking_for" class="inputtext" value="<?= e($data['looking_for']) ?>" maxlength="120" placeholder="Friendship, Random play">
  <label for="e-interests">Interests:</label>
  <input type="text" id="e-interests" name="interests" class="inputtext" value="<?= e($data['interests']) ?>" maxlength="300">
  <label for="e-about">About Me:</label>
  <textarea id="e-about" name="about" class="inputtext" rows="5" maxlength="2000"><?= e($data['about']) ?></textarea>

  <div class="formgrid-full center btnrow">
    <button type="submit" class="inputsubmit">Save Changes</button>
    <a href="profile.php" class="inputsubmit inputbutton-grey">Cancel</a>
  </div>
</form>
<?php
page_end();
