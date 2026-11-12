<?php
require_once __DIR__ . '/../../includes/auth.php';
$user = require_permission('messages.view');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM contact_messages WHERE id = ?');
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) { http_response_code(404); exit('Message not found.'); }

if (is_post()) {
    csrf_verify();
    $do = $_POST['do'] ?? '';
    if ($do === 'unread' && has_permission('messages.update')) {
        db()->prepare('UPDATE contact_messages SET is_read = 0 WHERE id = ?')->execute([$id]);
        flash('success', 'Marked as unread.');
    } elseif ($do === 'delete') {
        if (!has_permission('messages.delete')) {
            flash('error', 'Only administrators can delete messages.');
        } else {
            db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
            log_activity($user['id'], 'Deleted message: ' . $m['subject']);
            flash('success', 'Message deleted.');
        }
    }
    redirect('admin/messages/index.php');
}
//21
if (!$m['is_read'] && has_permission('messages.update')) {
    db()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$id]);
}

$pageTitle = 'Message'; $bodyClass = 'admin-page';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head" style="display:block">
    <a class="editor-back-link" href="<?= e(BASE_URL) ?>/admin/messages/index.php"><?= icon('back') ?> Back to messages</a><br>
    <span class="kicker">Message</span>
    <h1><?= e($m['subject']) ?></h1>
    <p>Received <?= e(format_datetime($m['created_at'], 'F j, Y \a\t g:i A')) ?></p>
  </header>
  <section class="panel form-wide">
    <dl class="facts">
      <dt>From</dt><dd><?= e($m['name']) ?> &lt;<a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>&gt;</dd>
      <dt>Message</dt><dd><?= nl2br(e($m['message'])) ?></dd>
    </dl>
    <div class="form-buttons">
      <a class="btn btn-primary" href="mailto:<?= e($m['email']) ?>?subject=<?= e(rawurlencode('Re: ' . $m['subject'])) ?>"><?= icon('mail') ?> Reply by email</a>
      <?php if (has_permission('messages.update')): ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="do" value="unread"><button class="btn btn-ghost">Mark unread</button></form>
      <?php endif; ?>
      <?php if (has_permission('messages.delete')): ?>
      <form method="post" data-confirm="Delete this message permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="do" value="delete"><button class="btn btn-danger"><?= icon('trash') ?> Delete</button></form>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
