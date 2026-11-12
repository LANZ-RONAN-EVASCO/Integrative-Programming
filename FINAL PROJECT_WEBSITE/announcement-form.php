<?php
//113
//114

require_once __DIR__ . '/auth.php';

$user = require_permission($id ? 'announcements.update' : 'announcements.create');

$cats = db()->query(
    'SELECT id, name FROM announcement_categories ORDER BY id'
)->fetchAll();

$a = [
    'title' => '',
    'summary' => '',
    'content' => '',
    'category_id' => '',
    'status' => 'draft',
    'is_urgent' => 0,
    'published_at' => '',
    'image_path' => null,
    'attachment_path' => null
];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM announcements WHERE id = ?');
    $stmt->execute([$id]);

    $a = $stmt->fetch();

    if (!$a) {
        http_response_code(404);
        exit('Announcement not found.');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verify();

    $oldImage = $a['image_path'];
    $oldFile = $a['attachment_path'];

    $a = array_merge($a, [
        'title' => trim($_POST['title'] ?? ''),
        'summary' => trim($_POST['summary'] ?? ''),
        'content' => trim($_POST['content'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'status' => $_POST['status'] ?? 'draft',
        'is_urgent' => isset($_POST['is_urgent']) ? 1 : 0,
        'published_at' => trim($_POST['published_at'] ?? '')
    ]);

    //115

    if ($a['title'] === '' || mb_strlen($a['title']) > 200) {
        $errors[] = 'Enter a title of up to 200 characters.';
    }

    if ($a['summary'] === '' || mb_strlen($a['summary']) > 300) {
        $errors[] = 'Enter a short description of up to 300 characters.';
    }

    if ($a['content'] === '') {
        $errors[] = 'Enter the full announcement text.';
    }

    $categoryIds = array_map('intval', array_column($cats, 'id'));

    if (!in_array((int)$a['category_id'], $categoryIds, true)) {
        $errors[] = 'Choose a valid announcement category.';
    }

    if (!in_array($a['status'], ['draft', 'published', 'archived'], true)) {
        $errors[] = 'Choose a valid announcement status.';
    }

    $when = $a['published_at'] !== ''
        ? strtotime($a['published_at'])
        : false;

    if ($a['published_at'] !== '' && $when === false) {
        $errors[] = 'The publication date is not valid.';
    }

    //116
    //117

    $newImage = null;
    $newFile = null;

    if (!$errors) {
        try {
            $newImage = save_upload(
                upload_field('image'),
                'announcements',
                ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp']
            );

            $newFile = save_upload(
                upload_field('attachment'),
                'announcements',
                ['application/pdf' => 'pdf'],
                5242880
            );

        } catch (RuntimeException $ex) {
            delete_upload($newImage);
            $errors[] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $a['image_path'] = $newImage ?? (isset($_POST['remove_image']) ? null : $oldImage);
        $a['attachment_path'] = $newFile ?? (isset($_POST['remove_attachment']) ? null : $oldFile);
    }

    //118

    if (!$errors) {

        $date = $when !== false
            ? date('Y-m-d H:i:s', $when)
            : ($a['status'] === 'published' ? date('Y-m-d H:i:s') : null);

        $vals = [
            $a['title'],
            $a['summary'],
            $a['content'],
            $a['category_id'],
            $a['status'],
            $a['is_urgent'],
            $date,
            $a['image_path'],
            $a['attachment_path']
        ];

        if ($id) {

            db()->prepare(
                'UPDATE announcements
                 SET title=?, summary=?, content=?, category_id=?, status=?,
                     is_urgent=?, published_at=?, image_path=?, attachment_path=?
                 WHERE id=?'
            )->execute([...$vals, $id]);

        } else {

            db()->prepare(
                'INSERT INTO announcements
                 (title, summary, content, category_id, status, is_urgent,
                  published_at, image_path, attachment_path, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            )->execute([...$vals, $user['id']]);
        }

        //119
        if ($oldImage && $oldImage !== $a['image_path']) delete_upload($oldImage);
        if ($oldFile && $oldFile !== $a['attachment_path']) delete_upload($oldFile);

        log_activity(
            $user['id'],
            ($id ? 'Updated' : 'Created') . ' announcement: ' . $a['title']
        );

        flash(
            'success',
            $id ? 'Announcement saved.' : 'Announcement created.'
        );

        redirect('admin/announcements/index.php');
    }
}

$isEdit = (int)$id > 0;

$pageTitle = $isEdit ? 'Edit announcement' : 'New announcement';
$bodyClass = 'admin-page';

require __DIR__ . '/header.php';
require __DIR__ . '/admin-sidebar.php';

$dateValue = !empty($a['published_at'])
    ? date('Y-m-d\TH:i', strtotime($a['published_at']))
    : '';
?>

<main class="admin-main announcement-editor-page">

    <!-- PAGE HEADER -->
    <header class="page-head" style="display:block">

        <div>
            <a
                class="editor-back-link"
                href="<?= e(BASE_URL) ?>/admin/announcements/index.php"
            >
                <?= icon('back') ?> Back to announcements
            </a>

            <span class="kicker">Content management</span>

            <h1>
                <?= $isEdit ? 'Edit announcement' : 'Create an announcement' ?>
            </h1>

            <p>
                <?= $isEdit
                    ? 'Update the details and publishing settings of this announcement.'
                    : 'Share important information, updates, and notices with your community.'
                ?>
            </p>
        </div>


    </header>

    <!-- ERROR MESSAGES -->
    <?php if ($errors): ?>

        <div class="editor-error-box" role="alert">

            <?= icon('alert', 22) ?>

            <div>
                <strong>Please review the following:</strong>

                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </div>

    <?php endif; ?>

    <!-- ANNOUNCEMENT FORM -->
    <form
        method="post"
        enctype="multipart/form-data"
        class="announcement-editor-form"
    >

        <?= csrf_field() ?>

        <div class="editor-main-column">

            <!-- BASIC INFORMATION -->
            <section class="editor-card">

                <div class="editor-card-heading">
                    <div class="editor-card-number">01</div>

                    <div>
                        <h2>Announcement details</h2>
                        <p>Provide the main information residents will see.</p>
                    </div>
                </div>

                <div class="editor-fields">

                    <div class="editor-field">
                        <label for="title">
                            Announcement title
                            <span class="required-mark">*</span>
                        </label>

                        <input
                            id="title"
                            name="title"
                            type="text"
                            maxlength="200"
                            required
                            placeholder="Enter a clear announcement title"
                            value="<?= e($a['title']) ?>"
                        >

                        <small>Maximum of 200 characters.</small>
                    </div>

                    <div class="editor-field">
                        <label for="summary">
                            Short description
                            <span class="required-mark">*</span>
                        </label>

                        <textarea
                            id="summary"
                            name="summary"
                            rows="3"
                            maxlength="300"
                            required
                            placeholder="Write a brief description for the announcement card."
                        ><?= e($a['summary']) ?></textarea>

                        <small>
                            This description will appear on announcement cards.
                            Maximum of 300 characters.
                        </small>
                    </div>

                    <div class="editor-field">
                        <label for="content">
                            Full announcement
                            <span class="required-mark">*</span>
                        </label>

                        <textarea
                            id="content"
                            name="content"
                            rows="12"
                            required
                            placeholder="Write the complete announcement here..."
                        ><?= e(plain_text($a['content'])) ?></textarea>

                        <small>
                            Include important dates, locations, instructions,
                            and other relevant information.
                        </small>
                    </div>

                </div>

            </section>

            <!-- CATEGORY -->
            <section class="editor-card">

                <div class="editor-card-heading">
                    <div class="editor-card-number">02</div>

                    <div>
                        <h2>Classification</h2>
                        <p>Organize your announcement for easier browsing.</p>
                    </div>
                </div>

                <div class="editor-field">
                    <label for="category_id">
                        Announcement category
                        <span class="required-mark">*</span>
                    </label>

                    <select id="category_id" name="category_id" required>
                        <option value="">Choose a category</option>

                        <?php foreach ($cats as $c): ?>
                            <option
                                value="<?= (int)$c['id'] ?>"
                                <?= (int)$a['category_id'] === (int)$c['id']
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <small>
                        Select the category that best describes this announcement.
                    </small>
                </div>

            </section>

            <!-- ATTACHMENTS -->
            <section class="editor-card">

                <div class="editor-card-heading">
                    <div class="editor-card-number">03</div>

                    <div>
                        <h2>Media and attachments</h2>
                        <p>Attach an image or supporting PDF document.</p>
                    </div>
                </div>

                <div class="editor-upload-grid">

                    <div class="editor-upload-box">
                        <div class="upload-box-icon"><?= icon('image', 26) ?></div>

                        <label for="image">Announcement image</label>

                        <p>JPG, PNG, or WebP. Maximum 2 MB.</p>

                        <input
                            id="image"
                            name="image"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <?php if ($a['image_path']): ?>
                            <img class="current-image" src="<?= e(BASE_URL . '/' . $a['image_path']) ?>" alt="Current image">
                            <label class="check-row"><input type="checkbox" name="remove_image" value="1"> Remove the current image</label>
                        <?php endif; ?>
                    </div>

                    <div class="editor-upload-box">
                        <div class="upload-box-icon"><?= icon('file', 26) ?></div>

                        <label for="attachment">PDF attachment</label>

                        <p>PDF documents only. Maximum 5 MB.</p>

                        <input
                            id="attachment"
                            name="attachment"
                            type="file"
                            accept="application/pdf"
                        >

                        <?php if ($a['attachment_path']): ?>
                            <div class="existing-file">
                                <?= icon('check') ?>
                                A PDF is attached.
                                <a href="<?= e(BASE_URL . '/' . $a['attachment_path']) ?>" target="_blank" rel="noopener">Open it</a>
                            </div>
                            <label class="check-row"><input type="checkbox" name="remove_attachment" value="1"> Remove the current PDF</label>
                        <?php endif; ?>
                    </div>

                </div>

            </section>

        </div>

        <!-- PUBLISHING SIDEBAR -->
        <aside class="editor-side-column">

            <section class="editor-card publishing-card">

                <div class="editor-card-heading">
                    <div>
                        <h2>Publishing settings</h2>
                        <p>Control how this announcement is handled.</p>
                    </div>
                </div>

                <div class="editor-field">
                    <label for="status">Announcement status</label>

                    <select id="status" name="status" required>
                        <?php foreach (
                            [
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'archived' => 'Archived'
                            ] as $v => $l
                        ): ?>
                            <option
                                value="<?= e($v) ?>"
                                <?= $a['status'] === $v ? 'selected' : '' ?>
                            >
                                <?= e($l) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <small>
                        Drafts are not published. Published announcements
                        are made available to residents according to your
                        public-page settings.
                    </small>
                </div>

                <div class="editor-field">
                    <label for="published_at">Publication date</label>

                    <input
                        id="published_at"
                        name="published_at"
                        type="datetime-local"
                        value="<?= e($dateValue) ?>"
                    >

                    <small>
                        Leave empty to use the current date and time when publishing. Choose a future date to schedule it.
                    </small>
                </div>

                <div class="urgent-option">

                    <label class="urgent-option-label" for="is_urgent">

                        <input
                            type="checkbox"
                            id="is_urgent"
                            name="is_urgent"
                            value="1"
                            <?= $a['is_urgent'] ? 'checked' : '' ?>
                        >

                                                <span class="urgent-option-text">
                            <strong>Mark as urgent</strong>
                            <small>Highlight this as an important notice.</small>
                        </span>

                    </label>

                </div>

                <div class="alert" style="background:var(--brand-50);color:var(--brand-600);margin-bottom:0">
                    <?= icon('info') ?>
                    <p>
                        Review the title, description, and announcement
                        details before publishing.
                    </p>
                </div>

                <div class="editor-action-buttons">

                    <button type="submit" class="btn btn-primary">
                        <?= icon('check') ?>
                        <?= $isEdit ? 'Save changes' : 'Create announcement' ?>
                    </button>

                    <a
                        href="<?= e(BASE_URL) ?>/admin/announcements/index.php"
                        class="btn btn-ghost"
                    >
                        Cancel
                    </a>

                </div>

            </section>

        </aside>

    </form>

</main>

<?php require __DIR__ . '/footer.php'; ?>
