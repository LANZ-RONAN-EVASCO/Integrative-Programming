<?php
require_once __DIR__ . '/../../includes/auth.php';
$me = require_permission('users.manage');

//30
if (is_post()) {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare('SELECT id, full_name, username, role, is_active FROM users WHERE id = ?');
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) {
        $do = $_POST['do'] ?? '';
        if ($id === (int)$me['id']) {
            flash('error', 'You cannot do that to your own account. Use "Change password" for your password.');
        } elseif ($do === 'toggle') {
            if ($row['is_active'] && $row['role'] === 'administrator' && active_admin_count() <= 1) {
                flash('error', 'You cannot deactivate the only active administrator.');
            } else {
                db()->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$row['is_active'] ? 0 : 1, $id]);
                log_activity($me['id'], ($row['is_active'] ? 'Deactivated' : 'Activated') . ' user: ' . $row['username']);
                flash('success', $row['is_active'] ? 'Account deactivated.' : 'Account activated.');
            }
        } elseif ($do === 'reset') {
            $temp = generate_password();
            db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?')->execute([password_hash($temp, PASSWORD_DEFAULT), $id]);
            log_activity($me['id'], 'Reset password for user: ' . $row['username']);
            $_SESSION['new_credentials'] = ['name' => $row['full_name'], 'username' => $row['username'], 'password' => $temp];
        }
    }
    redirect('admin/users/index.php');
}

$creds = $_SESSION['new_credentials'] ?? null; //31
unset($_SESSION['new_credentials']);
$rows = db()->query('SELECT id, full_name, username, email, role, is_active FROM users ORDER BY is_active DESC, role, full_name')->fetchAll();

$pageTitle = 'Users'; $bodyClass = 'admin-page';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head">
    <div><span class="kicker">System</span><h1>Users</h1><p>Barangay staff who can log in to this admin panel.</p></div>
    <a class="btn btn-primary" href="<?= e(BASE_URL) ?>/admin/users/create.php"><?= icon('plus') ?> Add user</a>
  </header>

  <?php if ($creds): ?>
    <div class="alert alert-success">
      <?= icon('key') ?>
      <div>
        <strong>Temporary password for <?= e($creds['name']) ?> (username: <?= e($creds['username']) ?>):</strong>
        <code class="temp-password"><?= e($creds['password']) ?></code><br>
        Copy it now and give it to them privately. It will not be shown again, and they must change it at their first login.
      </div>
    </div>
  <?php endif; ?>

  <div class="panel table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $self = (int)$r['id'] === (int)$me['id']; ?>
        <tr>
          <td><span class="title"><?= e($r['full_name']) ?></span><?= $self ? ' <span class="muted">(you)</span>' : '' ?></td>
          <td><?= e($r['username']) ?></td>
          <td class="muted"><?= e($r['email']) ?></td>
          <td><span class="badge role-<?= e($r['role']) ?>"><?= $r['role'] === 'administrator' ? 'Administrator' : 'Editor' ?></span></td>
          <td><span class="status-pill <?= $r['is_active'] ? 'status-published' : 'status-archived' ?>"><?= $r['is_active'] ? 'Active' : 'Deactivated' ?></span></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="<?= e(BASE_URL) ?>/admin/users/edit.php?id=<?= (int)$r['id'] ?>"><?= icon('edit') ?> Edit</a>
            <?php if (!$self): ?>
            <form method="post" data-confirm="Reset this user's password? Their current password will stop working."><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="do" value="reset">
              <button class="btn btn-ghost btn-sm"><?= icon('key') ?> Reset password</button></form>
            <form method="post" data-confirm="<?= $r['is_active'] ? 'Deactivate this account? They will not be able to log in.' : 'Activate this account?' ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="do" value="toggle">
              <button class="btn <?= $r['is_active'] ? 'btn-danger' : 'btn-ghost' ?> btn-sm"><?= $r['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="muted">Accounts are deactivated, not deleted, so the activity log always shows who did what.</p>
</main>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
