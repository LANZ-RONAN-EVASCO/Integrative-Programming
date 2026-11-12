<?php
//190
require_once __DIR__ . '/auth.php';
$user = require_permission($id ? 'events.update' : 'events.create');

$ev = ['title' => '', 'description' => '', 'venue' => '', 'event_date' => '', 'start_time' => '', 'end_time' => '', 'poster_path' => null];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([$id]);
    $ev = $stmt->fetch();
    if (!$ev) { http_response_code(404); exit('Event not found.'); }
}

$errors = [];
if (is_post()) {
    csrf_verify();
    $post = static fn(string $k): string => is_string($_POST[$k] ?? null) ? trim($_POST[$k]) : '';
    $oldPoster = $ev['poster_path'];
    $ev = array_merge($ev, [
        'title' => $post('title'), 'description' => $post('description'), 'venue' => $post('venue'),
        'event_date' => $post('event_date'), 'start_time' => $post('start_time'), 'end_time' => $post('end_time'),
    ]);

    $timeOk = static fn(string $t): bool => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t) === 1;
    if ($ev['title'] === '' || mb_strlen($ev['title']) > 200) $errors[] = 'Enter a title of up to 200 characters.';
    if ($ev['description'] === '') $errors[] = 'Enter a description of the event.';
    if ($ev['venue'] === '' || mb_strlen($ev['venue']) > 200) $errors[] = 'Enter the venue (up to 200 characters).';
    $date = DateTime::createFromFormat('!Y-m-d', $ev['event_date']);
    if (!$date || $date->format('Y-m-d') !== $ev['event_date']) $errors[] = 'Choose a valid event date.';
    if (!$timeOk($ev['start_time'])) $errors[] = 'Choose a start time.';
    if ($ev['end_time'] !== '' && !$timeOk($ev['end_time'])) $errors[] = 'The end time is not valid.';
    elseif ($ev['end_time'] !== '' && $timeOk($ev['start_time']) && $ev['end_time'] <= $ev['start_time']) $errors[] = 'The end time must be after the start time.';

    $newPoster = null;
    if (!$errors) {
        try { $newPoster = save_image_upload(upload_field('poster'), 'events'); }
        catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    }

    if (!$errors) {
        $poster = $newPoster ?? (isset($_POST['remove_poster']) ? null : $oldPoster);
        $status = $ev['event_date'] >= date('Y-m-d') ? 'upcoming' : 'completed'; //191
        $end = $ev['end_time'] !== '' ? $ev['end_time'] : null;
        $vals = [$ev['title'], $ev['description'], $ev['venue'], $ev['event_date'], $ev['start_time'], $end, $poster, $status];
        if ($id) {
            db()->prepare('UPDATE events SET title=?, description=?, venue=?, event_date=?, start_time=?, end_time=?, poster_path=?, status=? WHERE id=?')
                ->execute([...$vals, $id]);
        } else {
            db()->prepare('INSERT INTO events (title, description, venue, event_date, start_time, end_time, poster_path, status, created_by) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([...$vals, $user['id']]);
        }
        if ($oldPoster && $oldPoster !== $poster) delete_upload($oldPoster);
        log_activity($user['id'], ($id ? 'Updated' : 'Created') . ' event: ' . $ev['title']);
        flash('success', $id ? 'Event saved.' : 'Event created.');
        redirect('admin/events/index.php');
    }
    $ev['poster_path'] = $oldPoster; //192
}

$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Edit event' : 'New event'; $bodyClass = 'admin-page';
require __DIR__ . '/header.php';
require __DIR__ . '/admin-sidebar.php';
?>
<main class="admin-main">
  <header class="page-head" style="display:block">
    <a class="editor-back-link" href="<?= e(BASE_URL) ?>/admin/events/index.php"><?= icon('back') ?> Back to events</a><br>
    <span class="kicker">Community calendar</span>
    <h1><?= $isEdit ? 'Edit event' : 'Add an event' ?></h1>
    <p><?= $isEdit ? 'Update the details of this event.' : 'Tell residents about an upcoming activity.' ?></p>
  </header>

  <?php if ($errors): ?>
    <div class="editor-error-box" role="alert"><?= icon('alert', 22) ?><div><strong>Please review the following:</strong><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="panel form-wide">
    <?= csrf_field() ?>
    <label for="title" style="margin-top:0">Event title <span class="required-mark">*</span></label>
    <input id="title" name="title" required maxlength="200" placeholder="e.g. Barangay clean-up drive" value="<?= e($ev['title']) ?>">

    <label for="venue">Venue <span class="required-mark">*</span></label>
    <input id="venue" name="venue" required maxlength="200" placeholder="e.g. Barangay covered court" value="<?= e($ev['venue']) ?>">

    <div class="three-col">
      <div><label for="event_date">Date <span class="required-mark">*</span></label><input id="event_date" name="event_date" type="date" required value="<?= e($ev['event_date']) ?>"></div>
      <div><label for="start_time">Start time <span class="required-mark">*</span></label><input id="start_time" name="start_time" type="time" required value="<?= e(substr((string)$ev['start_time'], 0, 5)) ?>"></div>
      <div><label for="end_time">End time (optional)</label><input id="end_time" name="end_time" type="time" value="<?= e(substr((string)$ev['end_time'], 0, 5)) ?>"></div>
    </div>

    <label for="description">Description <span class="required-mark">*</span></label>
    <textarea id="description" name="description" rows="7" required placeholder="What will happen, who should come, what to bring..."><?= e($ev['description']) ?></textarea>

    <div class="editor-upload-box" style="margin-top:1.2rem">
      <div class="upload-box-icon"><?= icon('image', 26) ?></div>
      <label for="poster">Poster image (optional)</label>
      <p>JPG, PNG, GIF or WebP. Maximum 2 MB.</p>
      <?php if ($ev['poster_path']): ?>
        <img class="current-image" src="<?= e(BASE_URL . '/' . $ev['poster_path']) ?>" alt="Current poster">
        <label class="check-row"><input type="checkbox" name="remove_poster" value="1"> Remove the current poster</label>
      <?php endif; ?>
      <input id="poster" name="poster" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
    </div>
    <small>Events move from "Upcoming" to "Past" automatically after their date.</small>

    <div class="form-buttons">
      <button class="btn btn-primary"><?= icon('check') ?> Save event</button>
      <a class="btn btn-ghost" href="<?= e(BASE_URL) ?>/admin/events/index.php">Cancel</a>
    </div>
  </form>
</main>
<?php require __DIR__ . '/footer.php'; ?>
