<?php
//195
require_once __DIR__ . '/auth.php';
$user = require_permission($id ? 'officials.update' : 'officials.create');

$o = ['full_name' => '', 'position' => '', 'photo_path' => null,
      'display_order' => (int)db()->query('SELECT COALESCE(MAX(display_order), 0) + 1 FROM barangay_officials')->fetchColumn()];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM barangay_officials WHERE id = ?');
    $stmt->execute([$id]);
    $o = $stmt->fetch();
    if (!$o) { http_response_code(404); exit('Official not found.'); }
}

$errors = [];
if (is_post()) {
    csrf_verify();
    $post = static fn(string $k): string => is_string($_POST[$k] ?? null) ? trim($_POST[$k]) : '';
    $oldPhoto = $o['photo_path'];
    $o = array_merge($o, ['full_name' => $post('full_name'), 'position' => $post('position'), 'display_order' => (int)($_POST['display_order'] ?? 0)]);
    if ($o['full_name'] === '' || mb_strlen($o['full_name']) > 100) $errors[] = 'Enter the full name (up to 100 characters).';
    if ($o['position'] === '' || mb_strlen($o['position']) > 100) $errors[] = 'Enter the position (up to 100 characters).';

    $newPhoto = null;
    if (!$errors) {
        try { $newPhoto = save_image_upload(upload_field('photo'), 'officials'); }
        catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    }
    if (!$errors) {
        $photo = $newPhoto ?? (isset($_POST['remove_photo']) ? null : $oldPhoto);
        $vals = [$o['full_name'], $o['position'], $photo, $o['display_order']];
        if ($id) db()->prepare('UPDATE barangay_officials SET full_name=?, position=?, photo_path=?, display_order=? WHERE id=?')->execute([...$vals, $id]);
        else db()->prepare('INSERT INTO barangay_officials (full_name, position, photo_path, display_order) VALUES (?,?,?,?)')->execute($vals);
        if ($oldPhoto && $oldPhoto !== $photo) delete_upload($oldPhoto);
        log_activity($user['id'], ($id ? 'Updated' : 'Added') . ' official: ' . $o['full_name']);
        flash('success', $id ? 'Official saved.' : 'Official added.');
        redirect('admin/officials/index.php');
    }
    $o['photo_path'] = $oldPhoto;
}

$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Edit official' : 'Add official'; $bodyClass = 'admin-page';
require __DIR__ . '/header.php';
require __DIR__ . '/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head" style="display:block">
    <a class="editor-back-link" href="<?= e(BASE_URL) ?>/admin/officials/index.php"><?= icon('back') ?> Back to officials</a><br>
    <span class="kicker">Barangay</span>
    <h1><?= $isEdit ? 'Edit official' : 'Add an official' ?></h1>
    <p>Officials are shown on the public About page.</p>
  </header>
  <?php if ($errors): ?>
    <div class="editor-error-box" role="alert"><?= icon('alert', 22) ?><div><strong>Please review the following:</strong><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div></div>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="panel form-narrow">
    <?= csrf_field() ?>
    <label for="full_name" style="margin-top:0">Full name <span class="required-mark">*</span></label>
    <input id="full_name" name="full_name" required maxlength="100" value="<?= e($o['full_name']) ?>">
    <label for="position">Position <span class="required-mark">*</span></label>
    <input id="position" name="position" required maxlength="100" placeholder="e.g. Barangay Kagawad" value="<?= e($o['position']) ?>">
    <label for="display_order">Display order</label>
    <input id="display_order" name="display_order" type="number" min="0" max="9999" value="<?= (int)$o['display_order'] ?>">
    <small>1 is shown first.</small>
    <label for="photo">Photo (optional)</label>
    <?php if ($o['photo_path']): ?>
      <img class="current-image" src="<?= e(BASE_URL . '/' . $o['photo_path']) ?>" alt="Current photo">
      <label class="check-row"><input type="checkbox" name="remove_photo" value="1"> Remove the current photo</label>
    <?php endif; ?>
    <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
    <small>JPG, PNG, GIF or WebP, up to 2 MB.</small>
    <div class="form-buttons">
      <button class="btn btn-primary"><?= icon('check') ?> Save official</button>
      <a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/admin/officials/index.php">Cancel</a>
    </div>
  </form>
</main>
<?php require __DIR__ . '/footer.php'; ?>
