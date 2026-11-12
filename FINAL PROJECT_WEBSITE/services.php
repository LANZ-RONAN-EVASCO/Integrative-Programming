<?php
require_once __DIR__ . '/includes/functions.php';
$services = db()->query('SELECT * FROM barangay_services WHERE is_active = 1 ORDER BY display_order, id')->fetchAll();
$info = site_info();
$pageTitle = 'Services'; $bodyClass = 'public-page';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<section class="page-band">
  <div class="container">
    <span class="eyebrow">Barangay services</span>
    <h1>Services and requirements</h1>
    <p>Bring the listed requirements to the barangay hall during office hours.</p>
  </div>
</section>
<main class="container page-main">
  <div class="card-grid">
    <?php foreach ($services as $s): $reqs = text_list($s['requirements']); ?>
      <article class="service-card">
        <span class="info-icon"><?= icon('file', 22) ?></span>
        <h3><?= e($s['name']) ?></h3>
        <p class="muted"><?= nl2br(e($s['description'])) ?></p>
        <?php if ($reqs): ?>
          <h4>Requirements</h4>
          <ul class="req-list"><?php foreach ($reqs as $r): ?><li><?= icon('check') ?> <?= e($r) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
    <?php if (!$services): ?><p class="empty">No services are listed yet.</p><?php endif; ?>
  </div>
  <div class="report-cta services-cta">
    <div><h2>Need help or have a question?</h2><p><?= e($info['office_hours'] ?? '') ?> &middot; <?= e($info['address'] ?? '') ?></p></div>
    <a class="btn btn-primary" href="<?= e(BASE_URL) ?>/contact.php"><?= icon('mail') ?> Contact the barangay</a>
  </div>
</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
