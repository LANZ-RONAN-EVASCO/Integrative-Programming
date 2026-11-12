<?php require_once __DIR__ . '/brand-logo.php'; ?>
<?php
//108
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$selfPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$inFolder = static fn(string $folder): bool => str_contains($selfPath, '/' . $folder . '/');

$newReports = 0;
$unreadMessages = 0;
try { $newReports = (int)db()->query("SELECT COUNT(*) FROM reports WHERE status = 'new'")->fetchColumn(); } catch (Throwable $ex) { /*109*/ }
try { $unreadMessages = (int)db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn(); } catch (Throwable $ex) { /*110*/ }
$sbUser = function_exists('current_user') ? current_user() : null;

$menu = [
    'MAIN MENU' => [
        ['admin/dashboard.php', 'Dashboard', 'home', $currentPage === 'dashboard.php', 'dashboard.view', 0],
        ['admin/announcements/index.php', 'Announcements', 'megaphone', $inFolder('announcements'), 'announcements.view', 0],
        ['admin/events/index.php', 'Events', 'calendar', $inFolder('events'), 'events.view', 0],
        ['admin/reports/index.php', 'Reports', 'alert', $inFolder('reports'), 'reports.view', $newReports],
        ['admin/messages/index.php', 'Messages', 'mail', $inFolder('messages'), 'messages.view', $unreadMessages],
    ],
    'BARANGAY' => [
        ['admin/services/index.php', 'Services', 'briefcase', $inFolder('services'), 'services.view', 0],
        ['admin/officials/index.php', 'Officials', 'users', $inFolder('officials'), 'officials.view', 0],
        ['admin/categories.php', 'Categories', 'tag', $currentPage === 'categories.php', 'categories.manage', 0],
        ['admin/barangay-info.php', 'Barangay information', 'settings', $currentPage === 'barangay-info.php', 'settings.update', 0],
        ['admin/homepage-photo.php', 'Homepage photo', 'camera', $currentPage === 'homepage-photo.php', 'settings.update', 0],
    ],
    'SYSTEM' => [
        ['admin/users/index.php', 'Users', 'user', $inFolder('users'), 'users.manage', 0],
        ['admin/activity-log.php', 'Activity log', 'list', $currentPage === 'activity-log.php', 'logs.view', 0],
    ],
];
?>
<button type="button" class="admin-menu-toggle" id="adminMenuToggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="adminDrawer"><?= icon('menu', 22) ?></button>
<div class="admin-drawer-overlay" id="adminDrawerOverlay"></div>

<aside class="admin-drawer" id="adminDrawer">
  <div class="drawer-header">
    <div class="drawer-brand">
      <span class="brand-mark"><?= brand_logo() ?></span>
      <div><strong><?= e(site_name()) ?></strong><small>Administration</small></div>
    </div>
    <button type="button" class="drawer-close" id="adminDrawerClose" aria-label="Close navigation menu"><?= icon('x', 18) ?></button>
  </div>

  <?php foreach ($menu as $heading => $items): ?>
    <?php $visible = array_filter($items, static fn(array $item): bool => has_permission($item[4])); ?>
    <?php if (!$visible) continue; ?>
    <div class="drawer-section-label"><?= e($heading) ?></div>
    <nav class="drawer-navigation" aria-label="<?= e(ucfirst(strtolower($heading))) ?>">
      <?php foreach ($visible as [$file, $label, $iconName, $isActive, , $badge]): ?>
        <a href="<?= e(BASE_URL) ?>/<?= e($file) ?>" class="drawer-link <?= $isActive ? 'active' : '' ?>"><?= icon($iconName) ?><span><?= e($label) ?></span><?php if ($badge): ?><span class="count-badge"><?= (int)$badge ?></span><?php endif; ?></a>
      <?php endforeach; ?>
      <?php if ($heading === 'MAIN MENU'): ?>
        <a href="<?= e(BASE_URL) ?>/index.php" class="drawer-link" target="_blank" rel="noopener"><?= icon('building') ?><span>View public site</span></a>
      <?php endif; ?>
    </nav>
  <?php endforeach; ?>

  <div class="drawer-section-label">ACCOUNT</div>
  <nav class="drawer-navigation" aria-label="Account">
    <a href="<?= e(BASE_URL) ?>/admin/change-password.php" class="drawer-link <?= $currentPage === 'change-password.php' ? 'active' : '' ?>"><?= icon('lock') ?><span>Change password</span></a>
    <a href="<?= e(BASE_URL) ?>/admin/logout.php" class="drawer-link drawer-logout"><?= icon('logout') ?><span>Log out</span></a>
  </nav>

  <div class="drawer-footer"><span class="drawer-footer-indicator"></span><span>Signed in<?= !empty($sbUser['name']) ? ' as <strong style="color:#fff">' . e($sbUser['name']) . '</strong>' : '' ?><small style="margin:.15rem 0 0"><?= is_administrator() ? 'Administrator' : 'Editor' ?></small></span></div>
</aside>

<script>
(function () {
    const toggle = document.getElementById('adminMenuToggle');
    const drawer = document.getElementById('adminDrawer');
    const overlay = document.getElementById('adminDrawerOverlay');
    const closeButton = document.getElementById('adminDrawerClose');

    if (!toggle || !drawer || !overlay || !closeButton) return;

    function openDrawer() {
        drawer.classList.add('is-open');
        overlay.classList.add('is-visible');

        drawer.setAttribute('aria-hidden', 'false');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Close navigation menu');

        toggle.classList.add('is-active');
        document.body.classList.add('drawer-open');
    }

    function closeDrawer() {
        drawer.classList.remove('is-open');
        overlay.classList.remove('is-visible');

        drawer.setAttribute('aria-hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open navigation menu');

        toggle.classList.remove('is-active');
        document.body.classList.remove('drawer-open');
    }

    toggle.addEventListener('click', function () {
        if (drawer.classList.contains('is-open')) {
            closeDrawer();
        } else {
            openDrawer();
        }
    });

    closeButton.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDrawer();
        }
    });

    drawer.querySelectorAll('.drawer-link').forEach(function (link) {
        link.addEventListener('click', closeDrawer);
    });
})();
</script>

<?php //111
if (function_exists('has_permission') && (has_permission('reports.view') || has_permission('messages.view'))): ?>
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/admin-alerts.css?v=1">
<script src="<?= e(BASE_URL) ?>/assets/js/admin-alerts.js?v=1" data-endpoint="<?= e(BASE_URL) ?>/admin/notifications.php" data-base="<?= e(BASE_URL) ?>" defer></script>
<?php endif; ?>
