<?php
require_once __DIR__ . '/../../includes/auth.php';
$user = require_permission('events.view');

//12
if (is_post()) {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare('SELECT title, poster_path FROM events WHERE id = ?');
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) {
        if (!has_permission('events.delete')) {
            flash('error', 'Only administrators can delete events.');
        } else {
            db()->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
            delete_upload($row['poster_path']);
            log_activity($user['id'], 'Deleted event: ' . $row['title']);
            flash('success', 'Event deleted.');
        }
    }
    redirect('admin/events/index.php');
}

db()->exec("UPDATE events SET status = 'completed' WHERE event_date < CURDATE() AND status = 'upcoming'"); //13
$show = $_GET['show'] ?? '';
if (!in_array($show, ['upcoming', 'past'], true)) $show = '';
$where = ['upcoming' => 'WHERE event_date >= CURDATE()', 'past' => 'WHERE event_date < CURDATE()'][$show] ?? '';

$c = db()->query('SELECT COUNT(*) AS total, COALESCE(SUM(event_date >= CURDATE()), 0) AS upcoming FROM events')->fetch();
$pg = paginate((int)db()->query("SELECT COUNT(*) FROM events $where")->fetchColumn(), 15);
$rows = db()->query("SELECT * FROM events $where ORDER BY event_date DESC, start_time DESC LIMIT " . (int)$pg['per_page'] . ' OFFSET ' . (int)$pg['offset'])->fetchAll();

$pageTitle = 'Events'; $bodyClass = 'admin-page';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head">
    <div><span class="kicker">Community calendar</span><h1>Events</h1><p>Add and manage the activities shown on the public Events page.</p></div>
    <?php if (has_permission('events.create')): ?><a class="btn btn-primary" href="<?= e(BASE_URL) ?>/admin/events/create.php"><?= icon('plus') ?> New event</a><?php endif; ?>
  </header>

  <nav class="tabs" aria-label="Filter events">
    <a class="<?= $show === '' ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/admin/events/index.php">All <b><?= (int)$c['total'] ?></b></a>
    <a class="<?= $show === 'upcoming' ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/admin/events/index.php?show=upcoming">Upcoming <b><?= (int)$c['upcoming'] ?></b></a>
    <a class="<?= $show === 'past' ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/admin/events/index.php?show=past">Past <b><?= (int)$c['total'] - (int)$c['upcoming'] ?></b></a>
  </nav>

  <div class="panel table-wrap">
    <table>
      <thead><tr><th>Event</th><th>Date</th><th>Time</th><th>Venue</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $isPast = $r['event_date'] < date('Y-m-d'); ?>
        <tr>
          <td><span class="title"><?= e($r['title']) ?></span></td>
          <td class="muted"><?= e(format_date($r['event_date'])) ?></td>
          <td class="muted"><?= e(time_range((string)$r['start_time'], $r['end_time'])) ?></td>
          <td><?= e($r['venue']) ?></td>
          <td><span class="status-pill <?= $isPast ? 'status-archived' : 'status-published' ?>"><?= $isPast ? 'Past' : 'Upcoming' ?></span></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="<?= e(BASE_URL) ?>/event-details.php?id=<?= (int)$r['id'] ?>" target="_blank" rel="noopener"><?= icon('eye') ?> View</a>
            <?php if (has_permission('events.update')): ?><a class="btn btn-ghost btn-sm" href="<?= e(BASE_URL) ?>/admin/events/edit.php?id=<?= (int)$r['id'] ?>"><?= icon('edit') ?> Edit</a><?php endif; ?>
            <?php if (has_permission('events.delete')): ?>
            <form method="post" data-confirm="Delete this event permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-danger btn-sm"><?= icon('trash') ?> Delete</button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="muted" style="text-align:center;padding:2.2rem">No events here yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pager($pg, $show !== '' ? ['show' => $show] : []) ?>
</main>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
