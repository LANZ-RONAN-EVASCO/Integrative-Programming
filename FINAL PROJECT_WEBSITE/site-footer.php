<?php require_once __DIR__ . '/brand-logo.php'; ?>
<?php //197
if (!isset($info) || !is_array($info)) $info = site_info(); ?>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <a class="brand" href="<?= e(BASE_URL) ?>/index.php"><span class="brand-mark"><?= brand_logo() ?></span><span><?= e($info['name'] ?? 'Barangay Landayan') ?></span></a>
      <p>Official information portal for the residents of <?= e($info['name'] ?? 'Barangay Landayan') ?>. Stay informed about announcements and community events.</p>
    </div>
    <div>
      <h4>Explore</h4>
      <ul class="footer-list">
        <li><a href="<?= e(BASE_URL) ?>/index.php">Home</a></li>
        <li><a href="<?= e(BASE_URL) ?>/about.php">About the barangay</a></li>
        <li><a href="<?= e(BASE_URL) ?>/announcements.php">Announcements</a></li>
        <li><a href="<?= e(BASE_URL) ?>/events.php">Events</a></li>
        <li><a href="<?= e(BASE_URL) ?>/services.php">Services</a></li>
        <li><a href="<?= e(BASE_URL) ?>/report.php">Report to the Barangay</a></li>
        <li><a href="<?= e(BASE_URL) ?>/contact.php">Contact us</a></li>
        <li><a href="<?= e(BASE_URL) ?>/admin/login.php">Admin login</a></li>
      </ul>
    </div>
    <div>
      <h4>Contact</h4>
      <ul class="footer-list">
        <?php if (!empty($info['address'])): ?><li><?= e($info['address']) ?></li><?php endif; ?>
        <?php if (!empty($info['office_hours'])): ?><li><?= e($info['office_hours']) ?></li><?php endif; ?>
        <?php if (!empty($info['phone'])): ?><li><?= e($info['phone']) ?></li><?php endif; ?>
        <?php if (!empty($info['email'])): ?><li><a href="mailto:<?= e($info['email']) ?>"><?= e($info['email']) ?></a></li><?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>&copy; <?= date('Y') ?> <?= e($info['name'] ?? 'Barangay Landayan') ?>. All rights reserved.</span>
    <span>San Pedro City, Laguna</span>
  </div>
</footer>
