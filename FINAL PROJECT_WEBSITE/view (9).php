<?php
require_once __DIR__ . '/../../includes/auth.php';
$user = require_permission('reports.view');

$labels = ['new' => 'New', 'in_progress' => 'In progress', 'resolved' => 'Resolved'];
$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM reports WHERE id = ?');
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { http_response_code(404); exit('Report not found.'); }

$stmt = db()->prepare('SELECT id, image_path FROM report_images WHERE report_id = ? ORDER BY id');
$stmt->execute([$id]);
$images = $stmt->fetchAll();
$code = $r['tracking_code'] ?? ('#' . $r['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $do = $_POST['do'] ?? '';
    if ($do === 'update') {
        require_permission('reports.update');
        $newStatus = $_POST['status'] ?? '';
        if (isset($labels[$newStatus])) {
            $notes = trim($_POST['admin_notes'] ?? '');
            db()->prepare('UPDATE reports SET status = ?, admin_notes = ? WHERE id = ?')->execute([$newStatus, $notes !== '' ? $notes : null, $id]);
            log_activity($user['id'], "Updated report $code to " . $labels[$newStatus]);
            flash('success', 'Report updated.');
        }
        redirect('admin/reports/view.php?id=' . $id);
    }
    if ($do === 'delete') {
        require_permission('reports.delete');
        foreach ($images as $img) delete_upload($img['image_path']);
        db()->prepare('DELETE FROM reports WHERE id = ?')->execute([$id]);
        log_activity($user['id'], "Deleted report $code");
        flash('success', 'Report deleted.');
        redirect('admin/reports/index.php');
    }
}

$pageTitle = 'Report ' . $code; $bodyClass = 'admin-page';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head" style="display:block">
    <a class="editor-back-link" href="<?= e(BASE_URL) ?>/admin/reports/index.php"><?= icon('back') ?> Back to reports</a><br>
    <span class="kicker">Resident report</span>
    <h1><?= e($code) ?> <span class="status-pill status-<?= e($r['status']) ?>" style="vertical-align:middle"><?= e($labels[$r['status']]) ?></span></h1>
    <p>Received <?= e(date('F j, Y \a\t g:i A', strtotime($r['created_at']))) ?></p>
  </header>

  <div class="detail-grid">
    <div class="detail-main">
      <section class="panel">
        <div class="panel-heading"><h2>Photos</h2><span class="badge"><?= count($images) ?></span></div>
        <?php if ($images): ?>
          <div class="gallery">
            <?php foreach ($images as $img): $src = e(BASE_URL . '/' . $img['image_path']); ?>
              <a href="<?= $src ?>" target="_blank" rel="noopener"><img src="<?= $src ?>" alt="Report photo" loading="lazy"></a>
            <?php endforeach; ?>
          </div>
        <?php else: ?><p class="muted" style="margin:0">No photos were attached.</p><?php endif; ?>
      </section>

      <section class="panel">
        <div class="panel-heading"><h2>Details</h2><span class="badge"><?= e($r['category']) ?></span></div>
        <dl class="facts">
          <dt>Description</dt><dd><?= nl2br(e($r['description'])) ?></dd>
          <dt>Location</dt><dd><?= e($r['location']) ?></dd>
          <dt>Reported by</dt><dd><?= e($r['reporter_name'] ?: 'Anonymous') ?></dd>
          <dt>Contact</dt><dd><?= e($r['contact'] ?: 'Not provided') ?></dd>
        </dl>
      </section>
    </div>

    <aside class="detail-side">
      <form method="post" class="panel">
        <?= csrf_field() ?><input type="hidden" name="do" value="update">
        <h2 style="font-size:1.1rem">Handle this report</h2>
        <label for="status">Status</label>
        <select id="status" name="status">
          <?php foreach ($labels as $k => $l): ?><option value="<?= e($k) ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
        </select>
        <label for="admin_notes">Internal notes</label>
        <textarea id="admin_notes" name="admin_notes" rows="5" placeholder="Visible to barangay staff only"><?= e($r['admin_notes'] ?? '') ?></textarea>
        <button class="btn btn-primary btn-block" style="margin-top:1.2rem"><?= icon('check') ?> Save changes</button>
      </form>
      <?php if (has_permission('reports.delete')): ?>
      <form method="post" class="panel" data-confirm="Delete this report and its photos permanently?">
        <?= csrf_field() ?><input type="hidden" name="do" value="delete">
        <button class="btn btn-danger btn-block"><?= icon('trash') ?> Delete report</button>
      </form>
      <?php endif; ?>
    </aside>
  </div>
</main>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
