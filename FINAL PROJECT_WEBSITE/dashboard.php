<?php
require_once __DIR__ . '/../includes/auth.php';

$user = require_permission('dashboard.view');
$isAdmin = is_administrator();

//5
$a = db()->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'published'), 0) AS published,
        COALESCE(SUM(status = 'draft'), 0) AS draft,
        COALESCE(SUM(status = 'archived'), 0) AS archived
    FROM announcements
")->fetch();

//6
$ev = db()->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(event_date >= CURDATE()), 0) AS upcoming
    FROM events
")->fetch();

//7
$newReports = 0;
$unreadMessages = 0;
try { $newReports = (int)db()->query("SELECT COUNT(*) FROM reports WHERE status = 'new'")->fetchColumn(); } catch (Throwable $ex) { }
try { $unreadMessages = (int)db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn(); } catch (Throwable $ex) { }

//8
if ($isAdmin) {
    $logs = db()->query("
        SELECT l.action, l.created_at, u.full_name
        FROM activity_logs l
        LEFT JOIN users u ON u.id = l.user_id
        ORDER BY l.id DESC
        LIMIT 8
    ")->fetchAll();
} else {
    $stmt = db()->prepare("
        SELECT l.action, l.created_at, u.full_name
        FROM activity_logs l
        LEFT JOIN users u ON u.id = l.user_id
        WHERE l.user_id = ?
        ORDER BY l.id DESC
        LIMIT 8
    ");
    $stmt->execute([$user['id']]);
    $logs = $stmt->fetchAll();
}

//9
$recent = db()->query("
    SELECT id, title, status, created_at
    FROM announcements
    ORDER BY id DESC
    LIMIT 5
")->fetchAll();

//10
$cards = [
    ['label' => 'Total announcements', 'value' => $a['total'], 'icon' => 'megaphone', 'color' => 'blue', 'link' => 'admin/announcements/index.php'],
    ['label' => 'Published', 'value' => $a['published'], 'icon' => 'check', 'color' => 'green', 'link' => 'admin/announcements/index.php?status=published'],
    ['label' => 'Drafts', 'value' => $a['draft'], 'icon' => 'edit', 'color' => 'orange', 'link' => 'admin/announcements/index.php?status=draft'],
    ['label' => 'Archived', 'value' => $a['archived'], 'icon' => 'archive', 'color' => 'gray', 'link' => 'admin/announcements/index.php?status=archived'],
    ['label' => 'Upcoming events', 'value' => $ev['upcoming'], 'icon' => 'clock', 'color' => 'teal', 'link' => 'admin/events/index.php?show=upcoming'],
    ['label' => 'Total events', 'value' => $ev['total'], 'icon' => 'calendar', 'color' => 'purple', 'link' => 'admin/events/index.php'],
    ['label' => 'New reports', 'value' => $newReports, 'icon' => 'alert', 'color' => 'orange', 'link' => 'admin/reports/index.php?status=new'],
    ['label' => 'Unread messages', 'value' => $unreadMessages, 'icon' => 'mail', 'color' => 'blue', 'link' => 'admin/messages/index.php?show=unread'],
];

$pageTitle = 'Dashboard';
$bodyClass = 'admin-page';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head">
    <div>
      <span class="kicker">Administration overview</span>
      <h1>Welcome back, <?= e($user['name']) ?></h1>
      <p>Manage your barangay announcements and community updates.</p>
    </div>
    <span class="badge"><?= icon('calendar') ?> <?= e(date('F j, Y')) ?></span>
  </header>

  <section class="cta-banner">
    <div>
      <h2>What's happening in <?= e(site_name()) ?>?</h2>
      <p>Keep residents informed about notices, activities, advisories and updates.</p>
      <div class="hero-actions" style="margin-top:0">
        <a href="<?= e(BASE_URL) ?>/admin/announcements/create.php" class="btn btn-light"><?= icon('plus') ?> Create new announcement</a>
        <a href="<?= e(BASE_URL) ?>/admin/events/create.php" class="btn btn-outline-light"><?= icon('calendar') ?> Add an event</a>
      </div>
    </div>
    <span class="big-icon"><?= icon('megaphone', 96) ?></span>
  </section>

  <section class="stat-grid" aria-label="Content overview">
    <?php foreach ($cards as $card): ?>
      <a class="stat-card" href="<?= e(BASE_URL . '/' . $card['link']) ?>">
        <span class="stat-icon <?= e($card['color']) ?>"><?= icon($card['icon'], 20) ?></span>
        <strong class="stat-number"><?= number_format((int)$card['value']) ?></strong>
        <span class="stat-label"><?= e($card['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </section>

  <div class="dash-grid">
    <section class="panel">
      <div class="panel-heading">
        <h2>Recent announcements</h2>
        <a class="link-arrow" href="<?= e(BASE_URL) ?>/admin/announcements/index.php">View all <?= icon('arrow') ?></a>
      </div>
      <?php foreach ($recent as $item): ?>
        <div class="list-row">
          <span class="ic"><?= icon('file', 20) ?></span>
          <div class="grow"><strong><?= e($item['title']) ?></strong><span class="sub"><?= e(format_date($item['created_at'])) ?></span></div>
          <span class="status-pill status-<?= e($item['status']) ?>"><?= e(ucfirst($item['status'])) ?></span>
        </div>
      <?php endforeach; ?>
      <?php if (!$recent): ?>
        <div class="dashboard-empty"><strong>No announcements yet</strong><p>Create your first announcement to get started.</p>
          <a class="btn btn-primary btn-sm" href="<?= e(BASE_URL) ?>/admin/announcements/create.php">Create announcement</a></div>
      <?php endif; ?>
    </section>

    <section class="panel">
      <div class="panel-heading">
        <h2><?= $isAdmin ? 'Recent activity' : 'Your recent activity' ?></h2>
        <?php if ($isAdmin): ?><a class="link-arrow" href="<?= e(BASE_URL) ?>/admin/activity-log.php">Full log <?= icon('arrow') ?></a><?php endif; ?>
      </div>
      <?php foreach ($logs as $log): ?>
        <article class="activity-item">
          <strong><?= e($log['full_name'] ?? 'System') ?></strong>
          <p><?= e($log['action']) ?></p>
          <time><?= e(format_datetime($log['created_at'], 'M j, Y · g:i A')) ?></time>
        </article>
      <?php endforeach; ?>
      <?php if (!$logs): ?><div class="dashboard-empty"><strong>No activity recorded</strong><p>Administrative activity will appear here.</p></div><?php endif; ?>
    </section>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
