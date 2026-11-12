<?php //189 ?>
<article class="event-card">
  <div class="event-date"><b><?= e(format_date($ev['event_date'], 'j')) ?></b><span><?= e(format_date($ev['event_date'], 'M')) ?></span></div>
  <div>
    <h3><a href="<?= e(BASE_URL) ?>/event-details.php?id=<?= (int)$ev['id'] ?>"><?= e($ev['title']) ?></a></h3>
    <p class="event-line"><?= icon('clock') ?> <?= e(format_date($ev['event_date'], 'M j, Y')) ?>, <?= e(time_range((string)$ev['start_time'], $ev['end_time'] ?? null)) ?></p>
    <p class="event-line"><?= icon('pin') ?> <?= e($ev['venue']) ?></p>
    <p class="muted"><?= e(mb_strimwidth((string)$ev['description'], 0, 110, '…', 'UTF-8')) ?></p>
  </div>
</article>
