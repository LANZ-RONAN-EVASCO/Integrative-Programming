<?php
//221
//222
//223
require_once __DIR__ . '/includes/functions.php';
try {
    $exists = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'administrator'")->fetchColumn() > 0;
} catch (PDOException $e) {
    exit('The database tables are missing. Import database.sql in phpMyAdmin first (README, step 3), then reload this page.');
}
$password = null;
if (!$exists) {
    $password = substr(bin2hex(random_bytes(8)), 0, 12);
    db()->prepare("INSERT INTO users (full_name, username, email, password_hash, role, must_change_password)
                   VALUES ('Barangay Administrator', 'admin', 'admin@example.com', ?, 'administrator', 1)")
        ->execute([password_hash($password, PASSWORD_DEFAULT)]);
    log_activity(null, 'Initial administrator account created');
}
$pageTitle = 'Setup'; $bodyClass = 'auth-page';
require __DIR__ . '/includes/header.php';
?>
<main class="auth-card">
  <span class="brand-mark"><?= icon('shield') ?></span>
  <h1>System setup</h1>
  <?php if ($password): ?>
    <p class="alert alert-success">Administrator created. Save these now. They will not be shown again.</p>
    <p>Username: <strong>admin</strong><br>Temporary password: <strong><?= e($password) ?></strong></p>
    <p class="muted">You will be asked to change the password at first login. Then delete setup.php.</p>
    <a class="btn btn-primary btn-block" href="<?= e(BASE_URL) ?>/admin/login.php">Go to admin login</a>
  <?php else: ?>
    <p class="alert alert-error">An administrator already exists, so setup is disabled. Delete setup.php.</p>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
