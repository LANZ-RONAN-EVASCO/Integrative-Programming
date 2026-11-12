<?php
require_once __DIR__ . '/../../includes/auth.php';
$user = require_permission('reports.view');

$labels = ['new' => 'New', 'in_progress' => 'In progress', 'resolved' => 'Resolved'];
$status = $_GET['status'] ?? '';
if (!isset($labels[$status])) $status = '';

$counts = array_fill_keys(array_keys($labels), 0);
foreach (db()->query('SELECT status, COUNT(*) AS n FROM reports GROUP BY status')->fetchAll() as $c) $counts[$c['status']] = (int)$c['n'];

$where = $status !== '' ? 'WHERE r.status = ?' : '';
$params = $status !== '' ? [$status] : [];
$perPage = 15;
$stmt = db()->prepare("SELECT COUNT(*) FROM reports r $where");
$stmt->execute($params);
$pages = max(1, (int)ceil($stmt->fetchColumn() / $perPage));
$page = min($pages, max(1, (int)($_GET['page'] ?? 1)));

$stmt = db()->prepare("SELECT r.id, r.tracking_code, r.category, r.location, r.status, r.created_at,
        (SELECT COUNT(*) FROM report_images i WHERE i.report_id = r.id) AS photos,
        (SELECT image_path FROM report_images i WHERE i.report_id = r.id ORDER BY i.id LIMIT 1) AS thumb
    FROM reports r $where ORDER BY r.created_at DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Reports'; $bodyClass = 'admin-page';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head">
    <div><span class="kicker">Resident reports</span><h1>Reports</h1><p>Issues sent by residents, with photos.</p></div>
  </header>

  <nav class="tabs" aria-label="Filter by status">
    <a class="<?= $status === '' ? 'active' : '' ?>" href="?">All <b><?= array_sum($counts) ?></b></a>
    <?php foreach ($labels as $k => $l): ?><a class="<?= $status === $k ? 'active' : '' ?>" href="?status=<?= e($k) ?>"><?= e($l) ?> <b><?= $counts[$k] ?></b></a><?php endforeach; ?>
  </nav>

  <div class="panel table-wrap">
    <table>
      <thead><tr><th>Report</th><th>Category</th><th>Location</th><th>Status</th><th>Received</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <div class="report-cell">
              <?php if ($r['thumb']): ?><img class="thumb" src="<?= e(BASE_URL . '/' . $r['thumb']) ?>" alt=""><?php else: ?><span class="thumb thumb-empty"><?= icon('image') ?></span><?php endif; ?>
              <div><span class="title"><?= e($r['tracking_code'] ?? ('#' . $r['id'])) ?></span><br><small style="margin:0"><?= (int)$r['photos'] ?> photo<?= (int)$r['photos'] === 1 ? '' : 's' ?></small></div>
            </div>
          </td>
          <td><span class="badge"><?= e($r['category']) ?></span></td>
          <td><?= e($r['location']) ?></td>
          <td><span class="status-pill status-<?= e($r['status']) ?>"><?= e($labels[$r['status']]) ?></span></td>
          <td class="muted"><?= e(date('M j, Y g:i A', strtotime($r['created_at']))) ?></td>
          <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(BASE_URL) ?>/admin/reports/view.php?id=<?= (int)$r['id'] ?>">Open <?= icon('arrow') ?></a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="muted" style="text-align:center;padding:2.2rem">No reports here yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
  <nav class="pagination" aria-label="Pages">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a class="<?= $i === $page ? 'active' : '' ?>" href="?<?= e(http_build_query(['status' => $status, 'page' => $i])) ?>"><?= $i ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
