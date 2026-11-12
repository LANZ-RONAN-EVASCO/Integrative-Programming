<?php
require_once __DIR__ . '/includes/functions.php';
$stmt = db()->prepare('SELECT * FROM events WHERE id = ?');
$stmt->execute([(int)($_GET['id'] ?? 0)]);
$ev = $stmt->fetch();
if (!$ev) http_response_code(404);
$isPast = $ev && $ev['event_date'] < date('Y-m-d');
$pageTitle = $ev['title'] ?? 'Not found'; $bodyClass = 'public-page';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<section class="page-band"><div class="container"><a class="link-arrow" style="color:#fff" href="<?= e(BASE_URL) ?>/events.php"><?= icon('back') ?> All events</a></div></section>
<main class="article">
<?php if (!$ev): ?>
  <h1>Event not found</h1><p class="muted">This event may have been removed.</p>
  <p><a class="btn btn-primary" href="<?= e(BASE_URL) ?>/events.php"><?= icon('back') ?> Back to events</a></p>
<?php else: ?>
  <p class="meta"><span class="badge <?= $isPast ? 'status-archived' : 'status-published' ?>"><?= $isPast ? 'Past event' : 'Upcoming' ?></span></p>
  <h1><?= e($ev['title']) ?></h1>
  <?php if ($ev['poster_path']): ?><img class="article-img" src="<?= e(BASE_URL . '/' . $ev['poster_path']) ?>" alt="Poster for <?= e($ev['title']) ?>"><?php endif; ?>
  <dl class="facts event-facts">
    <div><dt>Date</dt><dd><?= icon('calendar') ?> <?= e(format_date($ev['event_date'], 'l, F j, Y')) ?></dd></div>
    <div><dt>Time</dt><dd><?= icon('clock') ?> <?= e(time_range((string)$ev['start_time'], $ev['end_time'] ?? null)) ?></dd></div>
    <div><dt>Venue</dt><dd><?= icon('pin') ?> <?= e($ev['venue']) ?></dd></div>
  </dl>
  <div class="article-text"><?= nl2br(e($ev['description'])) ?></div>
  <div class="article-actions"><a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/events.php"><?= icon('back') ?> Back to events</a></div>
<?php endif; ?>
</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
