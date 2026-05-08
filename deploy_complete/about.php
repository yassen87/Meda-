<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';

$pageTitle = t('page_story');

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--compact">
    <div class="container">
        <h1><?= esc(t('page_story')) ?></h1>
        <p class="page-lead"><?= esc(t('about_lead')) ?></p>
    </div>
</section>

<section class="section prose-section">
    <div class="container narrow">
        <p class="lead-paragraph"><?= t('about_p1') ?></p>
        <p><?= t('about_p2') ?></p>
        <p><?= esc(t('about_p3')) ?></p>
        <p><a class="btn btn-primary" href="<?= esc(url('contact.php')) ?>"><?= esc(t('about_cta')) ?></a></p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
