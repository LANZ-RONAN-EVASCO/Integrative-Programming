<?php //112 ?>
<article class="card<?= $a['is_urgent'] ? ' card-urgent' : '' ?>">
  <div class="card-media">
    <?php if ($a['image_path']): ?><img src="<?= e(BASE_URL . '/' . $a['image_path']) ?>" alt="" loading="lazy"><?php else: ?><?= icon('megaphone') ?><?php endif; ?>
  </div>
  <div class="card-body">
    <p class="meta">
      <?php if ($a['is_urgent']): ?><span class="badge badge-urgent"><?= icon('alert') ?> Urgent</span><?php endif; ?>
      <span class="badge"><?= e($a['category']) ?></span>
      <time><?= e(date('M j, Y', strtotime($a['published_at']))) ?></time>
    </p>
    <h3><a href="<?= e(BASE_URL) ?>/announcement-details.php?id=<?= (int)$a['id'] ?>"><?= e($a['title']) ?></a></h3>
    <p><?= e($a['summary']) ?></p>
  </div>
</article>
