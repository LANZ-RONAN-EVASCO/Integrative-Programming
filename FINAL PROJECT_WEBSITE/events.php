<?php
require_once __DIR__ . '/includes/functions.php';
$past = ($_GET['show'] ?? '') === 'past';
$where = $past ? 'event_date < CURDATE()' : 'event_date >= CURDATE()';
$order = $past ? 'event_date DESC, start_time DESC' : 'event_date ASC, start_time ASC';
$pg = paginate((int)db()->query("SELECT COUNT(*) FROM events WHERE $where")->fetchColumn(), 9);
$events = db()->query("SELECT * FROM events WHERE $where ORDER BY $order LIMIT " . (int)$pg['per_page'] . ' OFFSET ' . (int)$pg['offset'])->fetchAll();
$pageTitle = 'Events'; $bodyClass = 'public-page';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<section class="page-band">
  <div class="container">
    <span class="eyebrow">Community calendar</span>
    <h1>Events</h1>
    <p>Activities, meetings and programs happening in the barangay.</p>
  </div>
</section>
<main class="container page-main">
  <nav class="tabs" aria-label="Event filter">
    <a class="<?= $past ? '' : 'active' ?>" href="<?= e(BASE_URL) ?>/events.php">Upcoming</a>
    <a class="<?= $past ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/events.php?show=past">Past events</a>
  </nav>
  <div class="card-grid">
    <?php foreach ($events as $ev) include __DIR__ . '/includes/event-card.php'; ?>
    <?php if (!$events): ?><p class="empty"><?= $past ? 'No past events yet.' : 'No upcoming events right now. Please check back soon.' ?></p><?php endif; ?>
  </div>
  <?= pager($pg, $past ? ['show' => 'past'] : []) ?>
</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
