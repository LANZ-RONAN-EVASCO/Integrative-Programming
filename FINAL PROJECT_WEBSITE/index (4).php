<?php
require_once __DIR__ . '/../../includes/auth.php';
$user = require_permission('messages.view');

//20
if (is_post()) {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare('SELECT subject FROM contact_messages WHERE id = ?');
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) {
        $do = $_POST['do'] ?? '';
        if (($do === 'read' || $do === 'unread') && has_permission('messages.update')) {
            db()->prepare('UPDATE contact_messages SET is_read = ? WHERE id = ?')->execute([$do === 'read' ? 1 : 0, $id]);
        } elseif ($do === 'delete') {
            if (!has_permission('messages.delete')) {
                flash('error', 'Only administrators can delete messages.');
            } else {
                db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
                log_activity($user['id'], 'Deleted message: ' . $row['subject']);
                flash('success', 'Message deleted.');
            }
        }
    }
    redirect('admin/messages/index.php' . (($_POST['back'] ?? '') === 'unread' ? '?show=unread' : ''));
}

$unreadOnly = ($_GET['show'] ?? '') === 'unread';
$where = $unreadOnly ? 'WHERE is_read = 0' : '';
$c = db()->query('SELECT COUNT(*) AS total, COALESCE(SUM(is_read = 0), 0) AS unread FROM contact_messages')->fetch();
$pg = paginate((int)db()->query("SELECT COUNT(*) FROM contact_messages $where")->fetchColumn(), 15);
$rows = db()->query("SELECT * FROM contact_messages $where ORDER BY created_at DESC, id DESC LIMIT " . (int)$pg['per_page'] . ' OFFSET ' . (int)$pg['offset'])->fetchAll();

$pageTitle = 'Messages'; $bodyClass = 'admin-page';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head">
    <div><span class="kicker">Inbox</span><h1>Messages</h1><p>Messages sent from the public Contact page.</p></div>
  </header>

  <nav class="tabs" aria-label="Filter messages">
    <a class="<?= $unreadOnly ? '' : 'active' ?>" href="<?= e(BASE_URL) ?>/admin/messages/index.php">All <b><?= (int)$c['total'] ?></b></a>
    <a class="<?= $unreadOnly ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/admin/messages/index.php?show=unread">Unread <b><?= (int)$c['unread'] ?></b></a>
  </nav>

  <div class="panel table-wrap">
    <table>
      <thead><tr><th>Status</th><th>From</th><th>Subject</th><th>Received</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr<?= $r['is_read'] ? '' : ' class="row-unread"' ?>>
          <td><span class="status-pill <?= $r['is_read'] ? 'status-archived' : 'status-draft' ?>"><?= $r['is_read'] ? 'Read' : 'New' ?></span></td>
          <td><span class="title"><?= e($r['name']) ?></span><br><small style="margin:0"><?= e($r['email']) ?></small></td>
          <td><?= e($r['subject']) ?></td>
          <td class="muted"><?= e(format_datetime($r['created_at'])) ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="<?= e(BASE_URL) ?>/admin/messages/view.php?id=<?= (int)$r['id'] ?>"><?= icon('eye') ?> Open</a>
            <?php if (has_permission('messages.update')): ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="do" value="<?= $r['is_read'] ? 'unread' : 'read' ?>"><input type="hidden" name="back" value="<?= $unreadOnly ? 'unread' : '' ?>">
              <button class="btn btn-ghost btn-sm"><?= $r['is_read'] ? 'Mark unread' : 'Mark read' ?></button></form>
            <?php endif; ?>
            <?php if (has_permission('messages.delete')): ?>
            <form method="post" data-confirm="Delete this message permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="do" value="delete"><input type="hidden" name="back" value="<?= $unreadOnly ? 'unread' : '' ?>">
              <button class="btn btn-danger btn-sm"><?= icon('trash') ?> Delete</button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="muted" style="text-align:center;padding:2.2rem"><?= $unreadOnly ? 'No unread messages.' : 'No messages yet.' ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pager($pg, $unreadOnly ? ['show' => 'unread'] : []) ?>
</main>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
