<?php
require_once __DIR__ . '/includes/auth.php';
//32
$staff = current_user() !== null;
$stmt = db()->prepare("SELECT a.*, c.name AS category FROM announcements a JOIN announcement_categories c ON c.id = a.category_id
                       WHERE a.id = ? AND ((a.status = 'published' AND a.published_at <= NOW()) OR ? = 1)");
$stmt->execute([(int)($_GET['id'] ?? 0), $staff ? 1 : 0]);
$a = $stmt->fetch();
if (!$a) http_response_code(404);
$isLive = $a && $a['status'] === 'published' && !empty($a['published_at']) && strtotime((string)$a['published_at']) <= time();
$pageTitle = $a['title'] ?? 'Not found'; $bodyClass = 'public-page';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<section class="page-band"><div class="container"><a class="link-arrow" style="color:#fff" href="<?= e(BASE_URL) ?>/announcements.php"><?= icon('back') ?> All announcements</a></div></section>
<main class="article">
<?php if (!$a): ?>
  <h1>Announcement not found</h1><p class="muted">This announcement may have been removed or is not published.</p>
  <p><a class="btn btn-primary" href="<?= e(BASE_URL) ?>/announcements.php"><?= icon('back') ?> Back to announcements</a></p>
<?php else: ?>
  <?php if (!$isLive): ?><p class="alert alert-info"><?= icon('info') ?><span>Staff preview. This announcement is <strong><?= e($a['status']) ?></strong> or scheduled, so the public cannot see it yet. <a href="<?= e(BASE_URL) ?>/admin/announcements/edit.php?id=<?= (int)$a['id'] ?>">Edit it</a></span></p><?php endif; ?>
  <p class="meta"><?php if ($a['is_urgent']): ?><span class="badge badge-urgent"><?= icon('alert') ?> Urgent</span><?php endif; ?>
    <span class="badge"><?= e($a['category']) ?></span> <time><?= e($a['published_at'] ? format_date($a['published_at'], 'F j, Y') : 'Not published yet') ?></time></p>
  <h1><?= e($a['title']) ?></h1>
  <?php if ($a['image_path']): ?><img class="article-img" src="<?= e(BASE_URL . '/' . $a['image_path']) ?>" alt=""><?php endif; ?>
  <div class="article-text"><?= nl2br(e(plain_text($a['content']))) ?></div>
  <div class="article-actions">
    <?php if ($a['attachment_path']): ?><a class="btn btn-primary" href="<?= e(BASE_URL . '/' . $a['attachment_path']) ?>" download><?= icon('download') ?> Download attachment (PDF)</a><?php endif; ?>
    <a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/announcements.php"><?= icon('back') ?> Back to announcements</a>
  </div>
<?php endif; ?>
</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
