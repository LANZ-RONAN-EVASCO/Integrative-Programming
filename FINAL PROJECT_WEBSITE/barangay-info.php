<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_permission('settings.update');

//3
$fields = [
    'name' => ['Barangay name', 'text', 100],
    'description' => ['Short description (shown on the home page)', 'textarea', 1000],
    'mission' => ['Mission', 'textarea', 2000],
    'vision' => ['Vision', 'textarea', 2000],
    'address' => ['Address', 'text', 255],
    'office_hours' => ['Office hours', 'text', 255],
    'phone' => ['Phone number', 'text', 50],
    'email' => ['Email address', 'email', 150],
];
$values = site_info();
$errors = [];
if (is_post()) {
    csrf_verify();
    foreach ($fields as $key => [$label, $type, $max]) {
        $values[$key] = is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
        if ($key === 'name' && $values[$key] === '') $errors[] = 'The barangay name cannot be empty.';
        if (mb_strlen($values[$key]) > $max) $errors[] = "$label must be $max characters or fewer.";
        if ($type === 'email' && $values[$key] !== '' && !filter_var($values[$key], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    }
    if (!$errors) {
        $stmt = db()->prepare('INSERT INTO barangay_information (info_key, info_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE info_value = ?');
        foreach ($fields as $key => $_) $stmt->execute([$key, $values[$key], $values[$key]]);
        log_activity($user['id'], 'Updated barangay information');
        flash('success', 'Barangay information saved.');
        redirect('admin/barangay-info.php');
    }
}

$pageTitle = 'Barangay information'; $bodyClass = 'admin-page';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head">
    <div><span class="kicker">Barangay</span><h1>Barangay information</h1><p>These details appear on the public Home, About, Services and Contact pages and in the footer.</p></div>
    <a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/about.php" target="_blank" rel="noopener"><?= icon('eye') ?> View About page</a>
  </header>
  <?php if ($errors): ?>
    <div class="editor-error-box" role="alert"><?= icon('alert', 22) ?><div><strong>Please review the following:</strong><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div></div>
  <?php endif; ?>
  <form method="post" class="panel form-wide">
    <?= csrf_field() ?>
    <?php $first = true; foreach ($fields as $key => [$label, $type, $max]): ?>
      <label for="<?= e($key) ?>"<?= $first ? ' style="margin-top:0"' : '' ?>><?= e($label) ?></label>
      <?php if ($type === 'textarea'): ?>
        <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" rows="3" maxlength="<?= (int)$max ?>"><?= e($values[$key] ?? '') ?></textarea>
      <?php else: ?>
        <input id="<?= e($key) ?>" name="<?= e($key) ?>" type="<?= e($type) ?>" maxlength="<?= (int)$max ?>" value="<?= e($values[$key] ?? '') ?>">
      <?php endif; ?>
    <?php $first = false; endforeach; ?>
    <div class="form-buttons"><button class="btn btn-primary"><?= icon('check') ?> Save information</button></div>
  </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
