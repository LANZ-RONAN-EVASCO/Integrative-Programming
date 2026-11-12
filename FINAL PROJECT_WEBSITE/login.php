<?php
require_once __DIR__ . '/../includes/auth.php';
if (current_user()) redirect('admin/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($identifier === '' || $password === '') {
        $error = 'Enter your username or email and your password.';
    } elseif (attempt_login($identifier, $password)) {
        redirect('admin/dashboard.php');
    } else {
        //19
        $note = take_flash();
        $error = $note['message'] ?? 'Wrong username, email or password, or the account is deactivated.';
    }
}
$pageTitle = 'Admin login'; $bodyClass = 'auth-page';
require __DIR__ . '/../includes/header.php';
?>
<main class="auth-card">
  <span class="brand-mark"><?= icon('shield') ?></span>
  <h1>Admin login</h1>
  <p class="muted">For authorized barangay personnel only.</p>
  <?php if ($error): ?><p class="alert alert-error"><?= icon('alert') ?><?= e($error) ?></p><?php endif; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <label for="identifier">Username or email</label>
    <input id="identifier" name="identifier" required autocomplete="username" value="<?= e($_POST['identifier'] ?? '') ?>">
    <label for="password">Password</label>
    <div class="input-row">
      <input id="password" name="password" type="password" required autocomplete="current-password">
      <button type="button" class="btn btn-ghost" data-toggle-password="password">Show</button>
    </div>
    <button class="btn btn-primary btn-block">Log in <?= icon('arrow') ?></button>
  </form>
  <a class="auth-back" href="<?= e(BASE_URL) ?>/index.php"><?= icon('back') ?> Back to public site</a>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
