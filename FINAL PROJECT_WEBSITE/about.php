<?php
require_once __DIR__ . '/includes/functions.php';
$info = site_info();
$officials = db()->query('SELECT * FROM barangay_officials ORDER BY display_order, id')->fetchAll();
$pageTitle = 'About'; $bodyClass = 'public-page';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<section class="page-band">
  <div class="container">
    <span class="eyebrow">About us</span>
    <h1>About <?= e($info['name'] ?? 'Barangay Landayan') ?></h1>
    <p><?= e($info['description'] ?? '') ?></p>
  </div>
</section>
<main class="container page-main">
  <div class="mission-grid">
    <section class="panel"><span class="info-icon"><?= icon('shield', 22) ?></span><h2>Our mission</h2><p class="muted"><?= nl2br(e($info['mission'] ?? '')) ?></p></section>
    <section class="panel"><span class="info-icon"><?= icon('building', 22) ?></span><h2>Our vision</h2><p class="muted"><?= nl2br(e($info['vision'] ?? '')) ?></p></section>
  </div>

  <div class="section-head about-head"><div><h2>Barangay officials</h2><p>The leaders who serve our community.</p></div></div>
  <div class="official-grid">
    <?php foreach ($officials as $o): ?>
      <article class="official-card">
        <?php if ($o['photo_path']): ?><img class="avatar" src="<?= e(BASE_URL . '/' . $o['photo_path']) ?>" alt="Photo of <?= e($o['full_name']) ?>" loading="lazy">
        <?php else: ?><span class="avatar" aria-hidden="true"><?= e(initials((string)$o['full_name'])) ?></span><?php endif; ?>
        <h3><?= e($o['full_name']) ?></h3>
        <p><?= e($o['position']) ?></p>
      </article>
    <?php endforeach; ?>
    <?php if (!$officials): ?><p class="empty">The list of officials has not been added yet.</p><?php endif; ?>
  </div>

  <div class="section-head about-head"><div><h2>Visit the barangay hall</h2></div></div>
  <div class="panel">
    <p><?= icon('pin') ?> <strong>Address:</strong> <?= e($info['address'] ?? '') ?></p>
    <p><?= icon('clock') ?> <strong>Office hours:</strong> <?= e($info['office_hours'] ?? '') ?></p>
    <p><?= icon('phone') ?> <strong>Phone:</strong> <?= e($info['phone'] ?? '') ?> &nbsp; <?= icon('mail') ?> <strong>Email:</strong> <?= e($info['email'] ?? '') ?></p>
    <p style="margin:0"><a class="btn btn-primary" href="<?= e(BASE_URL) ?>/contact.php"><?= icon('mail') ?> Send us a message</a></p>
  </div>
</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
