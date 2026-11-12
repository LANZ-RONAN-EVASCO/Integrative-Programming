<?php
require_once __DIR__ . '/../../includes/auth.php';
$user = require_permission('officials.view');

if (is_post()) {
    csrf_verify();
    require_permission('officials.delete');
    $stmt = db()->prepare('SELECT full_name, photo_path FROM barangay_officials WHERE id = ?');
    $stmt->execute([(int)($_POST['id'] ?? 0)]);
    if ($row = $stmt->fetch()) {
        db()->prepare('DELETE FROM barangay_officials WHERE id = ?')->execute([(int)$_POST['id']]);
        delete_upload($row['photo_path']);
        log_activity($user['id'], 'Deleted official: ' . $row['full_name']);
        flash('success', 'Official deleted.');
    }
    redirect('admin/officials/index.php');
}
$rows = db()->query('SELECT * FROM barangay_officials ORDER BY display_order, id')->fetchAll();

$pageTitle = 'Officials'; $bodyClass = 'admin-page';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head">
    <div><span class="kicker">Barangay</span><h1>Officials</h1><p>The barangay officials shown on the public About page.</p></div>
    <a class="btn btn-primary" href="<?= e(BASE_URL) ?>/admin/officials/create.php"><?= icon('plus') ?> Add official</a>
  </header>
  <div class="panel table-wrap">
    <table>
      <thead><tr><th>Order</th><th>Photo</th><th>Name</th><th>Position</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="muted"><?= (int)$r['display_order'] ?></td>
          <td><?php if ($r['photo_path']): ?><img class="thumb-round" src="<?= e(BASE_URL . '/' . $r['photo_path']) ?>" alt=""><?php else: ?><span class="thumb-round initials" aria-hidden="true"><?= e(initials((string)$r['full_name'])) ?></span><?php endif; ?></td>
          <td><span class="title"><?= e($r['full_name']) ?></span></td>
          <td><?= e($r['position']) ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="<?= e(BASE_URL) ?>/admin/officials/edit.php?id=<?= (int)$r['id'] ?>"><?= icon('edit') ?> Edit</a>
            <form method="post" data-confirm="Delete this official?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-danger btn-sm"><?= icon('trash') ?> Delete</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="muted" style="text-align:center;padding:2.2rem">No officials yet. Add the first one.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
