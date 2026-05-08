<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = t('page_contact');
$errors = [];
$sent = false;
$name = '';
$email = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));

    if ($name === '') {
        $errors[] = t('err_name');
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('err_email');
    }
    if ($message === '' || strlen($message) < 10) {
        $errors[] = t('err_message');
    }

    if ($errors === []) {
        $pdo = medal_pdo();
        if ($pdo !== null) {
            try {
                $st = $pdo->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?,?,?)');
                $st->execute([$name, $email, $message]);
            } catch (Throwable) {
            }
        }
        $sent = true;
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--compact">
    <div class="container">
        <h1><?= esc(t('page_contact')) ?></h1>
        <p class="page-lead"><?= esc(t('contact_lead')) ?></p>
    </div>
</section>

<section class="section">
    <div class="container contact-grid">
        <div class="contact-aside">
            <p><strong><?= esc(t('contact_email_label')) ?></strong><br><a href="mailto:bonjour@lumiere-parfums.example">bonjour@lumiere-parfums.example</a></p>
            <p><strong><?= esc(t('contact_boutique')) ?></strong><br>128 Rue des Fleurs, 75008 Paris</p>
            <p><strong><?= esc(t('contact_hours')) ?></strong><br><?= esc(t('contact_hours_val')) ?></p>
        </div>
        <div class="contact-form-wrap">
            <?php if ($sent): ?>
                <div class="alert alert-success" role="status">
                    <?= esc(t('contact_success', ['name' => $name])) ?>
                </div>
            <?php else: ?>
                <?php if ($errors !== []): ?>
                    <div class="alert alert-error" role="alert">
                        <ul>
                            <?php foreach ($errors as $e): ?>
                                <li><?= esc($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form class="contact-form" method="post" action="<?= esc(url('contact.php')) ?>">
                    <input type="hidden" name="lang" value="<?= esc(current_lang()) ?>">
                    <label for="name"><?= esc(t('label_name')) ?></label>
                    <input type="text" id="name" name="name" required value="<?= esc($name) ?>" autocomplete="name">

                    <label for="email"><?= esc(t('label_email')) ?></label>
                    <input type="email" id="email" name="email" required value="<?= esc($email) ?>" autocomplete="email">

                    <label for="message"><?= esc(t('label_message')) ?></label>
                    <textarea id="message" name="message" rows="6" required><?= esc($message) ?></textarea>

                    <button type="submit" class="btn btn-primary"><?= esc(t('send_message')) ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
