<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login(true); //4
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $hash = $stmt->fetchColumn();
    $new = $_POST['new_password'] ?? '';
    if (!password_verify($_POST['current_password'] ?? '', $hash)) $error = 'Your current password is wrong.';
    elseif (mb_strlen($new) < 10) $error = 'The new password must be at least 10 characters.';
    elseif ($new === ($_POST['current_password'] ?? '')) $error = 'Choose a password that is different from the current one.';
    elseif ($new !== ($_POST['confirm_password'] ?? '')) $error = 'The new passwords do not match.';
    else {
        db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        $_SESSION['user']['must_change_password'] = false;
        session_regenerate_id(true);
        log_activity($user['id'], 'Changed own password');
        flash('success', 'Password changed.');
        redirect('admin/dashboard.php');
    }
}
$pageTitle = 'Change password'; $bodyClass = 'admin-page';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head"><div><span class="kicker">Account</span><h1>Change password</h1><p>Use at least 10 characters. A longer passphrase is stronger.</p></div></header>
  <?php if ($user['must_change_password']): ?><p class="alert alert-error"><?= icon('alert') ?>You must set a new password before using the admin panel.</p><?php endif; ?>
  <?php if ($error): ?><p class="alert alert-error"><?= icon('alert') ?><?= e($error) ?></p><?php endif; ?>
  <form method="post" class="panel form-narrow">
    <?= csrf_field() ?>
    <label for="current_password" style="margin-top:0">Current password</label>
    <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
    <label for="new_password">New password (10+ characters)</label>
    <input id="new_password" name="new_password" type="password" required minlength="10" autocomplete="new-password">
    <label for="confirm_password">Confirm new password</label>
    <input id="confirm_password" name="confirm_password" type="password" required minlength="10" autocomplete="new-password">
    <button class="btn btn-primary" style="margin-top:1.4rem"><?= icon('check') ?> Save new password</button>
  </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
