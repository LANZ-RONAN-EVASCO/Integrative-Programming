<?php
require_once __DIR__ . '/includes/functions.php';
$latest = db()->query("SELECT a.*, c.name AS category FROM announcements a JOIN announcement_categories c ON c.id = a.category_id
                       WHERE a.status = 'published' AND a.published_at <= NOW() ORDER BY a.is_urgent DESC, a.published_at DESC LIMIT 3")->fetchAll();
$events = db()->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date, start_time LIMIT 3")->fetchAll();
$info = site_info();
$pageTitle = 'Home'; $bodyClass = 'public-page';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<section class="hero"<?= function_exists('hero_photo_attr') ? hero_photo_attr() : '' ?>>
  <div class="container">
    <span class="eyebrow">Official barangay portal</span>
    <h1>Welcome to <?= e($info['name'] ?? 'Barangay Landayan') ?></h1>
    <p><?= e($info['description'] ?? '') ?></p>
    <div class="hero-actions">
      <a class="btn btn-light" href="<?= e(BASE_URL) ?>/announcements.php">View latest announcements <?= icon('arrow') ?></a>
      <a class="btn btn-alert" href="<?= e(BASE_URL) ?>/report.php"><?= icon('camera') ?> Report to the Barangay</a>
      <a class="btn btn-outline-light" href="#events">Upcoming events</a>
    </div>
  </div>
</section>

<section class="info-strip" aria-label="Office information">
  <div class="container info-grid">
    <div class="info-item"><span class="info-icon"><?= icon('pin', 20) ?></span><div><span>Address</span><strong><?= e($info['address'] ?? '') ?></strong></div></div>
    <div class="info-item"><span class="info-icon"><?= icon('clock', 20) ?></span><div><span>Office hours</span><strong><?= e($info['office_hours'] ?? '') ?></strong></div></div>
    <div class="info-item"><span class="info-icon"><?= icon('phone', 20) ?></span><div><span>Phone</span><strong><?= e($info['phone'] ?? '') ?></strong></div></div>
    <div class="info-item"><span class="info-icon"><?= icon('mail', 20) ?></span><div><span>Email</span><strong><?= e($info['email'] ?? '') ?></strong></div></div>
  </div>
</section>

<main>
  <section class="section">
    <div class="container">
      <div class="section-head">
        <div><h2>Latest announcements</h2><p>Important notices and updates from your barangay.</p></div>
        <a class="link-arrow" href="<?= e(BASE_URL) ?>/announcements.php">View all announcements <?= icon('arrow') ?></a>
      </div>
      <div class="card-grid">
        <?php foreach ($latest as $a) include __DIR__ . '/includes/announcement-card.php'; ?>
        <?php if (!$latest): ?><p class="empty">No announcements have been published yet.</p><?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="report-cta">
        <div><h2>See a problem in your community?</h2><p>Take a photo, describe it, and send it straight to the barangay. It only takes a minute.</p></div>
        <a class="btn btn-alert" href="<?= e(BASE_URL) ?>/report.php"><?= icon('camera') ?> Report to the Barangay</a>
      </div>
    </div>
  </section>

  <section class="section" id="events">
    <div class="container">
      <div class="section-head">
        <div><h2>Upcoming events</h2><p>Mark your calendar for these community activities.</p></div>
        <a class="link-arrow" href="<?= e(BASE_URL) ?>/events.php">View all events <?= icon('arrow') ?></a>
      </div>
      <div class="card-grid">
        <?php foreach ($events as $ev) include __DIR__ . '/includes/event-card.php'; ?>
        <?php if (!$events): ?><p class="empty">No upcoming events right now.</p><?php endif; ?>
      </div>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
