<?php
require_once __DIR__ . '/../../includes/auth.php';
$user = require_permission('announcements.view');

//2
if (is_post()) {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare('SELECT title, image_path, attachment_path FROM announcements WHERE id = ?');
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) {
        $do = $_POST['do'] ?? '';
        if ($do === 'delete') {
            if (!has_permission('announcements.delete')) {
                flash('error', 'Only administrators can delete announcements. You can archive it instead.');
            } else {
                db()->prepare('DELETE FROM announcements WHERE id = ?')->execute([$id]);
                delete_upload($row['image_path']);
                delete_upload($row['attachment_path']);
                log_activity($user['id'], 'Deleted announcement: ' . $row['title']);
                flash('success', 'Announcement deleted.');
            }
        } elseif (in_array($do, ['published', 'archived'], true) && has_permission('announcements.update')) {
            db()->prepare('UPDATE announcements SET status = ?, published_at = COALESCE(published_at, NOW()) WHERE id = ?')->execute([$do, $id]);
            log_activity($user['id'], ucfirst($do) . ' announcement: ' . $row['title']);
            flash('success', $do === 'published' ? 'Announcement published.' : 'Announcement archived.');
        }
    }
    redirect('admin/announcements/index.php');
}

$labels = ['published' => 'Published', 'draft' => 'Drafts', 'archived' => 'Archived'];
$status = $_GET['status'] ?? '';
if (!isset($labels[$status])) $status = '';

$counts = array_fill_keys(array_keys($labels), 0);
foreach (db()->query('SELECT status, COUNT(*) AS n FROM announcements GROUP BY status')->fetchAll() as $c) $counts[$c['status']] = (int)$c['n'];

$where = $status !== '' ? 'WHERE a.status = ?' : '';
$params = $status !== '' ? [$status] : [];
$stmt = db()->prepare("SELECT COUNT(*) FROM announcements a $where");
$stmt->execute($params);
$pg = paginate((int)$stmt->fetchColumn(), 15);
$stmt = db()->prepare('SELECT a.id, a.title, a.status, a.is_urgent, a.published_at, c.name AS category FROM announcements a
                       JOIN announcement_categories c ON c.id = a.category_id ' . $where . '
                       ORDER BY a.created_at DESC, a.id DESC LIMIT ' . (int)$pg['per_page'] . ' OFFSET ' . (int)$pg['offset']);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Announcements'; $bodyClass = 'admin-page';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head">
    <div><span class="kicker">Content management</span><h1>Announcements</h1><p>Create, publish, archive and edit announcements.</p></div>
    <?php if (has_permission('announcements.create')): ?><a class="btn btn-primary" href="<?= e(BASE_URL) ?>/admin/announcements/create.php"><?= icon('plus') ?> New announcement</a><?php endif; ?>
  </header>

  <nav class="tabs" aria-label="Filter by status">
    <a class="<?= $status === '' ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/admin/announcements/index.php">All <b><?= array_sum($counts) ?></b></a>
    <?php foreach ($labels as $k => $l): ?><a class="<?= $status === $k ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/admin/announcements/index.php?status=<?= e($k) ?>"><?= e($l) ?> <b><?= $counts[$k] ?></b></a><?php endforeach; ?>
  </nav>

  <div class="panel table-wrap">
    <table>
      <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Published</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $scheduled = $r['status'] === 'published' && $r['published_at'] && strtotime((string)$r['published_at']) > time(); ?>
        <tr>
          <td><span class="title"><?= e($r['title']) ?></span><?= $r['is_urgent'] ? ' <span class="badge badge-urgent">Urgent</span>' : '' ?></td>
          <td><span class="badge"><?= e($r['category']) ?></span></td>
          <td><span class="status-pill status-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span><?= $scheduled ? ' <span class="status-pill status-draft">Scheduled</span>' : '' ?></td>
          <td class="muted"><?= e(format_date($r['published_at'])) ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="<?= e(BASE_URL) ?>/announcement-details.php?id=<?= (int)$r['id'] ?>" target="_blank" rel="noopener"><?= icon('eye') ?> <?= $r['status'] === 'published' && !$scheduled ? 'View' : 'Preview' ?></a>
            <a class="btn btn-ghost btn-sm" href="<?= e(BASE_URL) ?>/admin/announcements/edit.php?id=<?= (int)$r['id'] ?>"><?= icon('edit') ?> Edit</a>
            <?php foreach (['published' => 'Publish', 'archived' => 'Archive'] as $do => $label): if ($r['status'] === $do) continue; ?>
              <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="do" value="<?= $do ?>">
                <button class="btn btn-ghost btn-sm"><?= $label ?></button></form>
            <?php endforeach; ?>
            <?php if (has_permission('announcements.delete')): ?>
            <form method="post" data-confirm="Delete this announcement permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="do" value="delete">
              <button class="btn btn-danger btn-sm"><?= icon('trash') ?> Delete</button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="muted" style="text-align:center;padding:2.2rem">No announcements here yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pager($pg, $status !== '' ? ['status' => $status] : []) ?>
</main>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
