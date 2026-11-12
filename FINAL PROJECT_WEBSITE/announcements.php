<?php
require_once __DIR__ . '/includes/functions.php';
//33
$q = is_string($_GET['q'] ?? '') ? trim($_GET['q'] ?? '') : '';
$cat = (int)($_GET['category'] ?? 0);
$sort = ($_GET['sort'] ?? 'new') === 'old' ? 'ASC' : 'DESC';
$perPage = 6;

$where = ["a.status = 'published'", 'a.published_at <= NOW()']; $params = [];
if ($q !== '') { $where[] = '(a.title LIKE ? OR a.summary LIKE ?)'; $params[] = '%' . like_escape($q) . '%'; $params[] = '%' . like_escape($q) . '%'; }
if ($cat) { $where[] = 'a.category_id = ?'; $params[] = $cat; }
$w = implode(' AND ', $where);

$stmt = db()->prepare("SELECT COUNT(*) FROM announcements a WHERE $w");
$stmt->execute($params);
$pages = max(1, (int)ceil($stmt->fetchColumn() / $perPage));
$page = min($pages, max(1, (int)($_GET['page'] ?? 1)));

$stmt = db()->prepare("SELECT a.*, c.name AS category FROM announcements a JOIN announcement_categories c ON c.id = a.category_id
                       WHERE $w ORDER BY a.published_at $sort LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
$stmt->execute($params);
$rows = $stmt->fetchAll();
$cats = db()->query('SELECT id, name FROM announcement_categories ORDER BY id')->fetchAll();

$pageTitle = 'Announcements'; $bodyClass = 'public-page';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<section class="page-band">
  <div class="container">
    <span class="eyebrow">Community updates</span>
    <h1>Announcements</h1>
    <p>Search and browse every notice published by the barangay.</p>
  </div>
</section>
<main class="container page-main">
  <form method="get" class="filters">
    <div class="search-field"><?= icon('search') ?><input name="q" placeholder="Search announcements" value="<?= e($q) ?>" aria-label="Search"></div>
    <select name="category" aria-label="Category">
      <option value="0">All categories</option>
      <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
    </select>
    <select name="sort" aria-label="Sort">
      <option value="new">Newest first</option><option value="old" <?= $sort === 'ASC' ? 'selected' : '' ?>>Oldest first</option>
    </select>
    <button class="btn btn-primary"><?= icon('search') ?> Search</button>
  </form>
  <?php if ($q !== '' || $cat): ?><p class="muted">Showing filtered results. <a href="<?= e(BASE_URL) ?>/announcements.php">Clear search</a></p><?php endif; ?>
  <div class="card-grid">
    <?php foreach ($rows as $a) include __DIR__ . '/includes/announcement-card.php'; ?>
    <?php if (!$rows): ?><p class="empty">No announcements match your search.</p><?php endif; ?>
  </div>
  <?php if ($pages > 1): ?>
  <nav class="pagination" aria-label="Pages">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a class="<?= $i === $page ? 'active' : '' ?>" href="?<?= e(http_build_query(['q' => $q, 'category' => $cat, 'sort' => $_GET['sort'] ?? 'new', 'page' => $i])) ?>"><?= $i ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
