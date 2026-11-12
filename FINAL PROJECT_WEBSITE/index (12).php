<?php
require_once __DIR__ . '/../../includes/auth.php';
$user = require_permission('services.view');

if (is_post()) {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $do = $_POST['do'] ?? '';
    $stmt = db()->prepare('SELECT name, is_active FROM barangay_services WHERE id = ?');
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) {
        if ($do === 'delete') {
            if (!has_permission('services.delete')) {
                flash('error', 'Only administrators can delete services. You can hide it instead.');
            } else {
                db()->prepare('DELETE FROM barangay_services WHERE id = ?')->execute([$id]);
                log_activity($user['id'], 'Deleted service: ' . $row['name']);
                flash('success', 'Service deleted.');
            }
        } elseif ($do === 'toggle' && has_permission('services.update')) {
            db()->prepare('UPDATE barangay_services SET is_active = ? WHERE id = ?')->execute([$row['is_active'] ? 0 : 1, $id]);
            log_activity($user['id'], ($row['is_active'] ? 'Hid' : 'Showed') . ' service: ' . $row['name']);
            flash('success', $row['is_active'] ? 'Service hidden from the public page.' : 'Service is now visible.');
        }
    }
    redirect('admin/services/index.php');
}
$rows = db()->query('SELECT * FROM barangay_services ORDER BY display_order, id')->fetchAll();

$pageTitle = 'Services'; $bodyClass = 'admin-page';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head">
    <div><span class="kicker">Barangay</span><h1>Services</h1><p>The services and requirements shown on the public Services page.</p></div>
    <?php if (has_permission('services.create')): ?><a class="btn btn-primary" href="<?= e(BASE_URL) ?>/admin/services/create.php"><?= icon('plus') ?> Add service</a><?php endif; ?>
  </header>
  <div class="panel table-wrap">
    <table>
      <thead><tr><th>Order</th><th>Service</th><th>Requirements</th><th>Visible</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="muted"><?= (int)$r['display_order'] ?></td>
          <td><span class="title"><?= e($r['name']) ?></span></td>
          <td class="muted"><?= e(implode(', ', text_list($r['requirements'])) ?: '—') ?></td>
          <td><span class="status-pill <?= $r['is_active'] ? 'status-published' : 'status-archived' ?>"><?= $r['is_active'] ? 'Visible' : 'Hidden' ?></span></td>
          <td class="actions">
            <?php if (has_permission('services.update')): ?>
            <a class="btn btn-ghost btn-sm" href="<?= e(BASE_URL) ?>/admin/services/edit.php?id=<?= (int)$r['id'] ?>"><?= icon('edit') ?> Edit</a>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="do" value="toggle">
              <button class="btn btn-ghost btn-sm"><?= $r['is_active'] ? 'Hide' : 'Show' ?></button></form>
            <?php endif; ?>
            <?php if (has_permission('services.delete')): ?>
            <form method="post" data-confirm="Delete this service?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="do" value="delete">
              <button class="btn btn-danger btn-sm"><?= icon('trash') ?> Delete</button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="muted" style="text-align:center;padding:2.2rem">No services yet. Add the first one.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
