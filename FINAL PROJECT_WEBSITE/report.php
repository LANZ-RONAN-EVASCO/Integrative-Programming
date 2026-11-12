<?php
//216
require_once __DIR__ . '/includes/functions.php';

$categories = ['Road & drainage', 'Garbage & sanitation', 'Street light', 'Flooding', 'Noise & disturbance', 'Safety & security', 'Stray animals', 'Other'];
$maxPhotos = 5;
$errors = [];
$old = ['category' => '', 'description' => '', 'location' => '', 'reporter_name' => '', 'contact' => ''];

//217
$done = $_SESSION['report_done'] ?? null;
unset($_SESSION['report_done']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    //218
    if (trim($_POST['website'] ?? '') !== '') redirect('report.php');

    foreach ($old as $k => $_) $old[$k] = trim((string)($_POST[$k] ?? ''));

    if (!in_array($old['category'], $categories, true)) $errors[] = 'Choose what kind of problem you are reporting.';
    if (mb_strlen($old['description']) < 10 || mb_strlen($old['description']) > 1000) $errors[] = 'Describe the problem in 10 to 1000 characters.';
    if (mb_strlen($old['location']) < 3 || mb_strlen($old['location']) > 255) $errors[] = 'Enter where the problem is (street, purok or landmark).';
    if (mb_strlen($old['reporter_name']) > 100) $errors[] = 'Your name is too long.';
    if (mb_strlen($old['contact']) > 50) $errors[] = 'The contact number or email is too long.';

    //219
    $ipHash = hash('sha256', client_ip());
    if (!$errors) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM reports WHERE ip_hash = ? AND created_at > (NOW() - INTERVAL 1 HOUR)');
        $stmt->execute([$ipHash]);
        if ((int)$stmt->fetchColumn() >= 3) $errors[] = 'You have sent several reports recently. Please try again in an hour, or call the barangay office.';
    }

    //220
    $files = [];
    if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
        foreach (array_keys($_FILES['photos']['name']) as $i) {
            if (($_FILES['photos']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            $files[] = [
                'name' => $_FILES['photos']['name'][$i], 'type' => $_FILES['photos']['type'][$i],
                'tmp_name' => $_FILES['photos']['tmp_name'][$i], 'error' => $_FILES['photos']['error'][$i],
                'size' => $_FILES['photos']['size'][$i],
            ];
        }
    }
    if (count($files) > $maxPhotos) $errors[] = "You can attach up to $maxPhotos photos.";

    $saved = [];
    if (!$errors) {
        try {
            foreach ($files as $f) {
                $path = save_image_upload($f, 'reports', 5242880);
                if ($path) $saved[] = $path;
            }
        } catch (RuntimeException $ex) {
            foreach ($saved as $p) delete_upload($p);
            $saved = [];
            $errors[] = $ex->getMessage();
        }
    }

    if (!$errors) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO reports (category, description, location, reporter_name, contact, ip_hash) VALUES (?,?,?,?,?,?)')
                ->execute([$old['category'], $old['description'], $old['location'], $old['reporter_name'] ?: null, $old['contact'] ?: null, $ipHash]);
            $id = (int)$pdo->lastInsertId();
            $code = sprintf('BL-%s-%04d', date('Y'), $id);
            $pdo->prepare('UPDATE reports SET tracking_code = ? WHERE id = ?')->execute([$code, $id]);
            $img = $pdo->prepare('INSERT INTO report_images (report_id, image_path) VALUES (?, ?)');
            foreach ($saved as $p) $img->execute([$id, $p]);
            $pdo->commit();
        } catch (Throwable $ex) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            foreach ($saved as $p) delete_upload($p);
            $errors[] = 'Your report could not be saved. Please try again.';
        }
    }

    if (!$errors) {
        log_activity(null, 'New resident report received: ' . $code);
        $_SESSION['report_done'] = $code;
        redirect('report.php');
    }
}

$pageTitle = 'Report to the Barangay'; $bodyClass = 'public-page';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<section class="page-band">
  <div class="container">
    <span class="eyebrow">Resident reports</span>
    <h1>Report to the Barangay</h1>
    <p>See something that needs attention? Send us a photo and a short description so we can act on it.</p>
  </div>
</section>

<main class="container page-main report-wrap">
<?php if ($done): ?>
  <section class="panel report-success">
    <span class="success-mark"><?= icon('check', 34) ?></span>
    <h2>Report received</h2>
    <p class="muted">Thank you. The barangay has been notified. Keep this code if you need to follow up.</p>
    <p class="tracking-code"><?= e($done) ?></p>
    <div class="article-actions" style="justify-content:center;border:0;padding:0;margin-top:1rem">
      <a class="btn btn-primary" href="<?= e(BASE_URL) ?>/report.php">Send another report</a>
      <a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/index.php">Back to home</a>
    </div>
  </section>
<?php else: ?>
  <?php if ($errors): ?>
    <div class="alert alert-error" role="alert"><?= icon('alert') ?><div><strong>Please review the following:</strong><ul style="margin:.3rem 0 0;padding-left:1.1rem"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div></div>
  <?php endif; ?>

  <form id="reportForm" class="report-form" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="hp" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>

    <section class="panel report-step">
      <div class="step-head"><span class="step-no">1</span><div><h2>Take or add photos</h2><p class="muted">A clear photo helps us respond faster. Up to <?= (int)$maxPhotos ?> photos.</p></div></div>

      <input id="photosInput" class="sr-only" type="file" name="photos[]" accept="image/*" multiple>
      <input id="cameraInput" class="sr-only" type="file" accept="image/*" capture="environment">

      <!-- Phones: the camera opens first -->
      <div class="camera-step">
        <label class="camera-btn" for="cameraInput"><?= icon('camera', 34) ?><span>Take a photo of the problem</span></label>
        <div class="camera-links">
          <label class="link-arrow" for="photosInput"><?= icon('image') ?> Choose from gallery</label>
          <button type="button" class="link-btn" id="skipPhoto">Skip photo</button>
        </div>
      </div>

      <!-- Desktop: drop or pick files -->
      <label class="dropzone" id="dropzone" for="photosInput">
        <?= icon('image', 32) ?>
        <strong>Drop photos here or click to browse</strong>
        <small>JPG, PNG or WebP. Photos are resized automatically.</small>
      </label>

      <div class="preview-grid" id="previewGrid"></div>
      <label class="link-arrow add-more" for="cameraInput" id="addMore"><?= icon('camera') ?> Take another photo</label>
    </section>

    <div class="report-details">
      <section class="panel report-step">
        <div class="step-head"><span class="step-no">2</span><div><h2>What is the problem?</h2><p class="muted">Choose the closest category.</p></div></div>
        <div class="chip-grid">
          <?php foreach ($categories as $i => $c): ?>
            <label class="chip"><input type="radio" name="category" value="<?= e($c) ?>" <?= $old['category'] === $c ? 'checked' : '' ?> <?= $i === 0 ? 'required' : '' ?>><span><?= e($c) ?></span></label>
          <?php endforeach; ?>
        </div>
        <label for="description">Describe what you see <span class="required-mark">*</span></label>
        <textarea id="description" name="description" rows="5" minlength="10" maxlength="1000" required placeholder="What happened? Since when? Is it dangerous?"><?= e($old['description']) ?></textarea>
        <label for="location">Where is it? <span class="required-mark">*</span></label>
        <input id="location" name="location" maxlength="255" required placeholder="Street, purok or nearby landmark" value="<?= e($old['location']) ?>">
      </section>

      <section class="panel report-step">
        <div class="step-head"><span class="step-no">3</span><div><h2>Your contact details</h2><p class="muted">Optional. Add them if you want the barangay to reach you.</p></div></div>
        <div class="two-col">
          <div><label for="reporter_name">Name</label><input id="reporter_name" name="reporter_name" maxlength="100" autocomplete="name" value="<?= e($old['reporter_name']) ?>"></div>
          <div><label for="contact">Phone or email</label><input id="contact" name="contact" maxlength="50" autocomplete="tel" value="<?= e($old['contact']) ?>"></div>
        </div>
        <button class="btn btn-primary btn-block" id="reportSubmit" style="margin-top:1.4rem"><?= icon('arrow') ?> Send report</button>
        <small style="text-align:center">For emergencies, call the barangay office or 911 instead.</small>
      </section>
    </div>
  </form>
<?php endif; ?>
</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
