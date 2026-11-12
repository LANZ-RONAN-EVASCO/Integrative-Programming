<?php
//196
require_once __DIR__ . '/auth.php';
$user = require_permission($id ? 'services.update' : 'services.create');

$s = ['name' => '', 'description' => '', 'requirements' => '', 'is_active' => 1,
      'display_order' => (int)db()->query('SELECT COALESCE(MAX(display_order), 0) + 1 FROM barangay_services')->fetchColumn()];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM barangay_services WHERE id = ?');
    $stmt->execute([$id]);
    $s = $stmt->fetch();
    if (!$s) { http_response_code(404); exit('Service not found.'); }
}

$errors = [];
if (is_post()) {
    csrf_verify();
    $post = static fn(string $k): string => is_string($_POST[$k] ?? null) ? trim($_POST[$k]) : '';
    $s = array_merge($s, ['name' => $post('name'), 'description' => $post('description'), 'requirements' => $post('requirements'),
                          'is_active' => isset($_POST['is_active']) ? 1 : 0, 'display_order' => (int)($_POST['display_order'] ?? 0)]);
    if ($s['name'] === '' || mb_strlen($s['name']) > 120) $errors[] = 'Enter the service name (up to 120 characters).';
    if ($s['description'] === '') $errors[] = 'Enter a description of the service.';
    if (!$errors) {
        $vals = [$s['name'], $s['description'], $s['requirements'] !== '' ? $s['requirements'] : null, $s['is_active'], $s['display_order']];
        if ($id) db()->prepare('UPDATE barangay_services SET name=?, description=?, requirements=?, is_active=?, display_order=? WHERE id=?')->execute([...$vals, $id]);
        else db()->prepare('INSERT INTO barangay_services (name, description, requirements, is_active, display_order) VALUES (?,?,?,?,?)')->execute($vals);
        log_activity($user['id'], ($id ? 'Updated' : 'Added') . ' service: ' . $s['name']);
        flash('success', $id ? 'Service saved.' : 'Service added.');
        redirect('admin/services/index.php');
    }
}

$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Edit service' : 'Add service'; $bodyClass = 'admin-page';
require __DIR__ . '/header.php';
require __DIR__ . '/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head" style="display:block">
    <a class="editor-back-link" href="<?= e(BASE_URL) ?>/admin/services/index.php"><?= icon('back') ?> Back to services</a><br>
    <span class="kicker">Barangay services</span>
    <h1><?= $isEdit ? 'Edit service' : 'Add a service' ?></h1>
    <p>Services appear on the public Services page with their requirements.</p>
  </header>
  <?php if ($errors): ?>
    <div class="editor-error-box" role="alert"><?= icon('alert', 22) ?><div><strong>Please review the following:</strong><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div></div>
  <?php endif; ?>
  <form method="post" class="panel form-wide">
    <?= csrf_field() ?>
    <label for="name" style="margin-top:0">Service name <span class="required-mark">*</span></label>
    <input id="name" name="name" required maxlength="120" placeholder="e.g. Barangay Clearance" value="<?= e($s['name']) ?>">
    <label for="description">Description <span class="required-mark">*</span></label>
    <textarea id="description" name="description" rows="4" required><?= e($s['description']) ?></textarea>
    <label for="requirements">Requirements</label>
    <textarea id="requirements" name="requirements" rows="4" placeholder="One requirement per line"><?= e($s['requirements']) ?></textarea>
    <small>One per line, or separated by semicolons.</small>
    <label for="display_order">Display order</label>
    <input id="display_order" name="display_order" type="number" min="0" max="9999" value="<?= (int)$s['display_order'] ?>">
    <small>1 is shown first.</small>
    <label class="check-row"><input type="checkbox" name="is_active" value="1" <?= $s['is_active'] ? 'checked' : '' ?>> Show this service on the public Services page</label>
    <div class="form-buttons">
      <button class="btn btn-primary"><?= icon('check') ?> Save service</button>
      <a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/admin/services/index.php">Cancel</a>
    </div>
  </form>
</main>
<?php require __DIR__ . '/footer.php'; ?>
