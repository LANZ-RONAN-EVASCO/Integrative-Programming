<?php
//198
require_once __DIR__ . '/auth.php';
$me = require_permission('users.manage');

$u = ['full_name' => '', 'username' => '', 'email' => '', 'role' => 'editor', 'is_active' => 1];
if ($id) {
    $stmt = db()->prepare('SELECT id, full_name, username, email, role, is_active FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) { http_response_code(404); exit('User not found.'); }
}
$isSelf = $id === (int)$me['id']; //199

$errors = [];
if (is_post()) {
    csrf_verify();
    $post = static fn(string $k): string => is_string($_POST[$k] ?? null) ? trim($_POST[$k]) : '';
    $before = $u;
    $u = array_merge($u, ['full_name' => $post('full_name'), 'username' => $post('username'), 'email' => $post('email')]);
    if (!$isSelf) {
        $u['role'] = $post('role') ?: 'editor';
        $u['is_active'] = $id ? (isset($_POST['is_active']) ? 1 : 0) : 1;
    }

    if ($u['full_name'] === '' || mb_strlen($u['full_name']) > 100) $errors[] = 'Enter the full name (up to 100 characters).';
    if (preg_match('/^[A-Za-z0-9._-]{3,50}$/', $u['username']) !== 1) $errors[] = 'The username must be 3 to 50 characters: letters, numbers, dot, dash or underscore.';
    if (!filter_var($u['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($u['email']) > 150) $errors[] = 'Enter a valid email address.';
    if (!in_array($u['role'], ['administrator', 'editor'], true)) $errors[] = 'Choose a role.';

    //200
    if ($id && $before['role'] === 'administrator' && $before['is_active']
        && ($u['role'] !== 'administrator' || !$u['is_active']) && active_admin_count() <= 1) {
        $errors[] = 'This is the only active administrator, so the role and status cannot be changed.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ?');
        $stmt->execute([$u['username'], $u['email'], $id]);
        if ($stmt->fetch()) $errors[] = 'That username or email is already used by another account.';
    }

    if (!$errors) {
        if ($id) {
            db()->prepare('UPDATE users SET full_name=?, username=?, email=?, role=?, is_active=? WHERE id=?')
                ->execute([$u['full_name'], $u['username'], $u['email'], $u['role'], $u['is_active'], $id]);
            log_activity($me['id'], 'Updated user: ' . $u['username']);
            flash('success', 'User saved.');
        } else {
            $temp = generate_password();
            db()->prepare('INSERT INTO users (full_name, username, email, password_hash, role, must_change_password) VALUES (?,?,?,?,?,1)')
                ->execute([$u['full_name'], $u['username'], $u['email'], password_hash($temp, PASSWORD_DEFAULT), $u['role']]);
            log_activity($me['id'], 'Created user: ' . $u['username']);
            $_SESSION['new_credentials'] = ['name' => $u['full_name'], 'username' => $u['username'], 'password' => $temp];
        }
        redirect('admin/users/index.php');
    }
}

$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Edit user' : 'Add user'; $bodyClass = 'admin-page';
require __DIR__ . '/header.php';
require __DIR__ . '/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head" style="display:block">
    <a class="editor-back-link" href="<?= e(BASE_URL) ?>/admin/users/index.php"><?= icon('back') ?> Back to users</a><br>
    <span class="kicker">System</span>
    <h1><?= $isEdit ? 'Edit user' : 'Add a user' ?></h1>
    <p><?= $isEdit ? 'Change this account’s details.' : 'Create an account for a member of the barangay staff.' ?></p>
  </header>
  <?php if ($errors): ?>
    <div class="editor-error-box" role="alert"><?= icon('alert', 22) ?><div><strong>Please review the following:</strong><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div></div>
  <?php endif; ?>
  <form method="post" class="panel form-narrow">
    <?= csrf_field() ?>
    <label for="full_name" style="margin-top:0">Full name <span class="required-mark">*</span></label>
    <input id="full_name" name="full_name" required maxlength="100" value="<?= e($u['full_name']) ?>">
    <label for="username">Username <span class="required-mark">*</span></label>
    <input id="username" name="username" required maxlength="50" autocomplete="off" value="<?= e($u['username']) ?>">
    <label for="email">Email <span class="required-mark">*</span></label>
    <input id="email" name="email" type="email" required maxlength="150" value="<?= e($u['email']) ?>">
    <label for="role">Role</label>
    <select id="role" name="role" <?= $isSelf ? 'disabled' : '' ?>>
      <option value="editor" <?= $u['role'] === 'editor' ? 'selected' : '' ?>>Editor: announcements, events, services, reports and messages</option>
      <option value="administrator" <?= $u['role'] === 'administrator' ? 'selected' : '' ?>>Administrator: full access</option>
    </select>
    <?php if ($isEdit): ?>
      <label class="check-row"><input type="checkbox" name="is_active" value="1" <?= $u['is_active'] ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>> Account is active (can log in)</label>
      <?php if ($isSelf): ?><small>You cannot change your own role or deactivate your own account.</small><?php endif; ?>
    <?php else: ?>
      <small>A temporary password is generated when you save. You will see it once, and the user must change it at first login.</small>
    <?php endif; ?>
    <div class="form-buttons">
      <button class="btn btn-primary"><?= icon('check') ?> Save user</button>
      <a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/admin/users/index.php">Cancel</a>
    </div>
  </form>
</main>
<?php require __DIR__ . '/footer.php'; ?>
