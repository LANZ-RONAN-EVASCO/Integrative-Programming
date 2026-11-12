<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_permission('settings.update');

//14
if (!function_exists('hero_resize_save')) {
    function hero_resize_save(string $tmp, string $mime): string
    {
        $src = false;
        if ($mime === 'image/jpeg') $src = @imagecreatefromjpeg($tmp);
        elseif ($mime === 'image/png') $src = @imagecreatefrompng($tmp);
        elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($tmp);
        if (!$src) throw new RuntimeException('That image could not be read. Try a JPG or PNG.');

        //15
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($tmp);
            $angle = [3 => 180, 6 => -90, 8 => 90][(int)($exif['Orientation'] ?? 1)] ?? 0;
            if ($angle) {
                $rot = imagerotate($src, $angle, 0);
                if ($rot) { imagedestroy($src); $src = $rot; }
            }
        }

        $sw = imagesx($src); $sh = imagesy($src);
        if ($sh > $sw) { imagedestroy($src); throw new RuntimeException('Please use a landscape (wide) photo, not a tall one.'); }
        if ($sw < 200) { imagedestroy($src); throw new RuntimeException('The photo is tiny (' . $sw . ' pixels wide). Please choose a bigger one.'); }

        $w = 1920; $h = 1080;
        $scale = max($w / $sw, $h / $sh);
        $cw = (int)round($w / $scale); $ch = (int)round($h / $scale);
        $sx = (int)(($sw - $cw) / 2);  $sy = (int)(($sh - $ch) / 2);

        $dst = imagecreatetruecolor($w, $h);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $w, $h, $cw, $ch);
        imagedestroy($src);

        $dir = ROOT_PATH . '/uploads/site';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            imagedestroy($dst);
            throw new RuntimeException('The upload folder could not be created.');
        }
        $name = bin2hex(random_bytes(16)) . '.jpg';
        imageinterlace($dst, true);
        $ok = imagejpeg($dst, $dir . '/' . $name, 82);
        imagedestroy($dst);
        if (!$ok) throw new RuntimeException('The photo could not be saved.');
        return 'uploads/site/' . $name;
    }
}

$info = site_info();
$current = trim((string)($info['hero_image'] ?? ''));
$errors = [];
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

if (is_post()) {
    csrf_verify();

    //16
    if (isset($_POST['remove_photo'])) {
        db()->prepare('DELETE FROM barangay_information WHERE info_key = ?')->execute(['hero_image']);
        if ($current !== '') delete_upload($current);
        log_activity($user['id'], 'Removed the homepage photo');
        flash('success', 'Homepage photo removed. The default design is back.');
        redirect('admin/homepage-photo.php');
    }

    //17
    $file = upload_field('photo');
    $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    $newPath = null;

    if ($code === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please choose a photo first.';
    } elseif ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
        $errors[] = 'The photo is too large for the server. Try one under 10 MB.';
    } elseif ($code !== UPLOAD_ERR_OK) {
        $errors[] = 'The upload failed. Please try again.';
    } else {
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            $errors[] = 'The uploaded file is invalid.';
        } elseif ((int)filesize($tmp) > 10485760) {
            $errors[] = 'The photo is larger than 10 MB.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
            if (!is_string($mime) || !isset($allowed[$mime])) {
                $errors[] = 'Use a JPG, PNG or WebP photo.';
            } else {
                try {
                    $newPath = function_exists('imagecreatetruecolor')
                        ? hero_resize_save($tmp, $mime)
                        : save_upload($file, 'site', $allowed, 10485760);   //18
                } catch (RuntimeException $ex) {
                    $errors[] = $ex->getMessage();
                }
            }
        }
    }

    if (!$errors && $newPath) {
        db()->prepare('INSERT INTO barangay_information (info_key, info_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE info_value = ?')
            ->execute(['hero_image', $newPath, $newPath]);
        if ($current !== '' && $current !== $newPath) delete_upload($current);
        log_activity($user['id'], 'Changed the homepage photo');
        flash('success', 'Homepage photo updated.');
        redirect('admin/homepage-photo.php');
    }
}

$pageTitle = 'Homepage photo'; $bodyClass = 'admin-page';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head">
    <div><span class="kicker">Barangay</span><h1>Homepage photo</h1><p>Choose the big background photo shown at the top of the public homepage.</p></div>
    <a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/index.php" target="_blank" rel="noopener"><?= icon('eye') ?> View homepage</a>
  </header>

  <?php if ($errors): ?>
    <div class="editor-error-box" role="alert"><?= icon('alert', 22) ?><div><strong>Please review the following:</strong><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="panel form-wide">
    <?= csrf_field() ?>

    <label style="margin-top:0">Current photo</label>
    <?php if ($current !== '' && is_file(ROOT_PATH . '/' . $current)): ?>
      <img src="<?= e(BASE_URL . '/' . $current) ?>" alt="Current homepage photo" style="max-width:100%;width:520px;border-radius:12px;display:block;margin:.4rem 0 1rem">
    <?php else: ?>
      <p class="muted">No photo chosen. The homepage uses the default blue design.</p>
    <?php endif; ?>

    <label for="photo">Choose a new landscape photo</label>
    <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp">
    <p class="muted">JPG, PNG or WebP, maximum 10 MB. Use a wide (landscape) photo. The site resizes and crops it automatically, and darkens it a little so the white text stays easy to read.</p>

    <div class="form-buttons">
      <button class="btn btn-primary"><?= icon('check') ?> Upload photo</button>
      <?php if ($current !== ''): ?>
        <button class="btn btn-ghost" name="remove_photo" value="1" onclick="return confirm('Remove the homepage photo and use the default design?')">Remove photo</button>
      <?php endif; ?>
    </div>
  </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
