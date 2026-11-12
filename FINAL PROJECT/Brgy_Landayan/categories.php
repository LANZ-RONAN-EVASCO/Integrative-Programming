<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_permission('categories.manage');
$errors = [];

if (is_post()) {
    csrf_verify();
    $do = $_POST['do'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';

    if ($do === 'add' || $do === 'rename') {
        if ($name === '' || mb_strlen($name) > 80) {
            $errors[] = 'Enter a category name of up to 80 characters.';
        } else {
            $stmt = db()->prepare('SELECT id FROM announcement_categories WHERE name = ? AND id <> ?');
            $stmt->execute([$name, $do === 'rename' ? $id : 0]);
            if ($stmt->fetch()) {
                $errors[] = 'A category named "' . $name . '" already exists.';
            } elseif ($do === 'add') {
                db()->prepare('INSERT INTO announcement_categories (name) VALUES (?)')->execute([$name]);
                log_activity($user['id'], 'Added category: ' . $name);
                flash('success', 'Category added.');
                redirect('admin/categories.php');
            } else {
                db()->prepare('UPDATE announcement_categories SET name = ? WHERE id = ?')->execute([$name, $id]);
                log_activity($user['id'], 'Renamed category to: ' . $name);
                flash('success', 'Category renamed.');
                redirect('admin/categories.php');
            }
        }
    } elseif ($do === 'delete') {
        $stmt = db()->prepare('SELECT COUNT(*) FROM announcements WHERE category_id = ?');
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors[] = 'That category still has announcements. Move or delete them first.';
        } else {
            db()->prepare('DELETE FROM announcement_categories WHERE id = ?')->execute([$id]);
            log_activity($user['id'], 'Deleted a category');
            flash('success', 'Category deleted.');
            redirect('admin/categories.php');
        }
    }
}
$rows = db()->query('SELECT c.id, c.name, COUNT(a.id) AS total FROM announcement_categories c
                     LEFT JOIN announcements a ON a.category_id = c.id GROUP BY c.id, c.name ORDER BY c.id')->fetchAll();

$pageTitle = 'Categories'; $bodyClass = 'admin-page';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head"><div><span class="kicker">Barangay</span><h1>Announcement categories</h1><p>Group announcements so residents can filter them.</p></div></header>
  <?php if ($errors): ?>
    <div class="editor-error-box" role="alert"><?= icon('alert', 22) ?><div><strong>Please review the following:</strong><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div></div>
  <?php endif; ?>

  <form method="post" class="panel form-narrow">
    <?= csrf_field() ?><input type="hidden" name="do" value="add">
    <label for="new_name" style="margin-top:0">Add a category</label>
    <div class="input-row"><input id="new_name" name="name" required maxlength="80" placeholder="e.g. Peace and Order"><button class="btn btn-primary"><?= icon('plus') ?> Add</button></div>
  </form>

  <div class="panel table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Announcements</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="do" value="rename"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input name="name" required maxlength="80" value="<?= e($r['name']) ?>" aria-label="Category name"><button class="btn btn-ghost btn-sm">Rename</button></form>
          </td>
          <td class="muted"><?= (int)$r['total'] ?></td>
          <td class="actions">
            <?php if ((int)$r['total'] === 0): ?>
            <form method="post" data-confirm="Delete this category?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-danger btn-sm"><?= icon('trash') ?> Delete</button></form>
            <?php else: ?><span class="muted">In use</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
