<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_permission('logs.view');

$q = is_string($_GET['q'] ?? '') ? trim($_GET['q'] ?? '') : '';
$where = $q !== '' ? 'WHERE l.action LIKE ?' : '';
$params = $q !== '' ? ['%' . like_escape($q) . '%'] : [];

$stmt = db()->prepare("SELECT COUNT(*) FROM activity_logs l $where");
$stmt->execute($params);
$pg = paginate((int)$stmt->fetchColumn(), 25);
$stmt = db()->prepare("SELECT l.action, l.ip_address, l.created_at, u.full_name FROM activity_logs l
                       LEFT JOIN users u ON u.id = l.user_id $where ORDER BY l.id DESC LIMIT " . (int)$pg['per_page'] . ' OFFSET ' . (int)$pg['offset']);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Activity log'; $bodyClass = 'admin-page';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head"><div><span class="kicker">System</span><h1>Activity log</h1><p>Who did what, and when, including new resident reports.</p></div></header>
  <form method="get" class="filters-inline">
    <input name="q" placeholder="Search actions, e.g. Deleted" value="<?= e($q) ?>" aria-label="Search the log">
    <button class="btn btn-primary"><?= icon('search') ?> Search</button>
    <?php if ($q !== ''): ?><a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/admin/activity-log.php">Clear</a><?php endif; ?>
  </form>
  <div class="panel table-wrap">
    <table>
      <thead><tr><th>When</th><th>User</th><th>Action</th><th>IP address</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr><td class="muted"><?= e(format_datetime($r['created_at'], 'M j, Y g:i A')) ?></td><td><?= e($r['full_name'] ?? 'System / visitor') ?></td><td><?= e($r['action']) ?></td><td class="muted"><?= e($r['ip_address'] ?? '—') ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="4" class="muted" style="text-align:center;padding:2.2rem">Nothing found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pager($pg, ['q' => $q]) ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
