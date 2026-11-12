<?php
//105
require_once __DIR__ . '/includes/functions.php';

$f = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
$errors = [];

if (is_post()) {
    csrf_verify();

    //106
    if (trim((string)($_POST['website'] ?? '')) !== '') redirect('contact.php');

    foreach ($f as $k => $_) $f[$k] = is_string($_POST[$k] ?? '') ? trim($_POST[$k] ?? '') : '';

    if ($f['name'] === '' || mb_strlen($f['name']) > 100) $errors[] = 'Enter your name (up to 100 characters).';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($f['email']) > 150) $errors[] = 'Enter a valid email address.';
    if ($f['subject'] === '' || mb_strlen($f['subject']) > 150) $errors[] = 'Enter a subject (up to 150 characters).';
    if (mb_strlen($f['message']) < 10 || mb_strlen($f['message']) > 2000) $errors[] = 'Write a message between 10 and 2000 characters.';

    if (!$errors && time() - (int)($_SESSION['last_contact'] ?? 0) < 60) $errors[] = 'Please wait a minute before sending another message.';

    //107
    if (!$errors && (int)db()->query('SELECT COUNT(*) FROM contact_messages WHERE created_at > (NOW() - INTERVAL 1 HOUR)')->fetchColumn() >= 30) {
        $errors[] = 'The inbox is receiving many messages right now. Please try again later or call the barangay office.';
    }

    if (!$errors) {
        db()->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)')
            ->execute([$f['name'], $f['email'], $f['subject'], $f['message']]);
        $_SESSION['last_contact'] = time();
        flash('success', 'Thank you. Your message was sent to the barangay office.');
        redirect('contact.php');
    }
}

$info = site_info();
$pageTitle = 'Contact'; $bodyClass = 'public-page';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<section class="page-band">
  <div class="container">
    <span class="eyebrow">Get in touch</span>
    <h1>Contact us</h1>
    <p>Have a question or a request? Send the barangay a message and we will get back to you.</p>
  </div>
</section>
<main class="container page-main">
  <div class="contact-layout">
    <form method="post" class="panel" novalidate>
      <?php if ($errors): ?>
        <div class="alert alert-error" role="alert"><?= icon('alert') ?><div><strong>Please review the following:</strong><ul style="margin:.3rem 0 0;padding-left:1.1rem"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div></div>
      <?php endif; ?>
      <?= csrf_field() ?>
      <div class="hp" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
      <div class="two-col">
        <div><label for="name" style="margin-top:0">Your name</label><input id="name" name="name" required maxlength="100" autocomplete="name" value="<?= e($f['name']) ?>"></div>
        <div><label for="email" style="margin-top:0">Email address</label><input id="email" name="email" type="email" required maxlength="150" autocomplete="email" value="<?= e($f['email']) ?>"></div>
      </div>
      <label for="subject">Subject</label><input id="subject" name="subject" required maxlength="150" value="<?= e($f['subject']) ?>">
      <label for="message">Message</label><textarea id="message" name="message" rows="7" required minlength="10" maxlength="2000"><?= e($f['message']) ?></textarea>
      <button class="btn btn-primary" style="margin-top:1.4rem"><?= icon('mail') ?> Send message</button>
    </form>

    <aside class="panel contact-info">
      <h2>Barangay hall</h2>
      <p><span class="info-icon"><?= icon('pin', 20) ?></span><span><strong>Address</strong><br><?= e($info['address'] ?? '') ?></span></p>
      <p><span class="info-icon"><?= icon('clock', 20) ?></span><span><strong>Office hours</strong><br><?= e($info['office_hours'] ?? '') ?></span></p>
      <p><span class="info-icon"><?= icon('phone', 20) ?></span><span><strong>Phone</strong><br><?= e($info['phone'] ?? '') ?></span></p>
      <p><span class="info-icon"><?= icon('mail', 20) ?></span><span><strong>Email</strong><br><?= e($info['email'] ?? '') ?></span></p>
      <p class="muted" style="margin:0">Need to report a problem with photos? <a href="<?= e(BASE_URL) ?>/report.php">Use the report form</a>.</p>
    </aside>
  </div>
</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
