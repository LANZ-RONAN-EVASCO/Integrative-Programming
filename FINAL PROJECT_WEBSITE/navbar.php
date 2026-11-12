<?php require_once __DIR__ . '/brand-logo.php'; ?>
<?php //194
$navPage = basename($_SERVER['PHP_SELF'] ?? '');
$navLinks = [
    ['index.php', 'Home', ['index.php']],
    ['about.php', 'About', ['about.php']],
    ['announcements.php', 'Announcements', ['announcements.php', 'announcement-details.php']],
    ['events.php', 'Events', ['events.php', 'event-details.php']],
    ['services.php', 'Services', ['services.php']],
    ['report.php', 'Report', ['report.php']],
    ['contact.php', 'Contact', ['contact.php']],
]; ?>
<header class="site-nav">
  <div class="site-nav-inner">
    <a class="brand" href="<?= e(BASE_URL) ?>/index.php">
      <span class="brand-mark"><?= brand_logo() ?></span>
      <span><?= e(site_name()) ?><small>San Pedro City, Laguna</small></span>
    </a>
    <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="siteNav"><?= icon('menu', 22) ?></button>
    <nav id="siteNav" aria-label="Main">
      <?php foreach ($navLinks as [$file, $label, $matches]): ?>
        <a href="<?= e(BASE_URL) ?>/<?= e($file) ?>" class="<?= in_array($navPage, $matches, true) ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(BASE_URL) ?>/admin/login.php"><?= icon('lock') ?> Admin login</a>
    </nav>
  </div>
</header>
