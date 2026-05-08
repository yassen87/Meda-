<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_messages');

$pdo = medal_pdo();

if ($pdo !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $mid = (int) ($_POST['message_id'] ?? 0);
    if ($mid > 0 && ($_POST['action'] ?? '') === 'mark_read') {
        $pdo->prepare('UPDATE contact_messages SET read_at = NOW() WHERE id = ? AND read_at IS NULL')->execute([$mid]);
    }
    header('Location: ' . admin_url('messages.php'));
    exit;
}

$rows = [];
if ($pdo !== null) {
    try {
        $rows = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 200')->fetchAll();
    } catch (Throwable) {
        $rows = [];
    }
}

require __DIR__ . '/_layout_start.php';
?>

<h1><?= esc(t('admin_messages')) ?></h1>
<p class="admin-lead"><?= esc(t('admin_messages_lead')) ?></p>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php elseif ($rows === []): ?>
    <p class="admin-muted"><?= esc(t('admin_no_messages')) ?></p>
<?php else: ?>
    <?php foreach ($rows as $m): ?>
        <div class="admin-card" style="<?= empty($m['read_at']) ? 'border-color:rgba(212,175,55,0.45)' : '' ?>">
            <p style="margin:0 0 0.5rem">
                <strong><?= esc((string) $m['name']) ?></strong>
                &lt;<a href="mailto:<?= esc((string) $m['email']) ?>"><?= esc((string) $m['email']) ?></a>&gt;
                <span class="admin-muted"><?= esc((string) $m['created_at']) ?></span>
                <?php if (!empty($m['read_at'])): ?>
                    <span class="admin-muted">· <?= esc(t('admin_read_at')) ?> <?= esc((string) $m['read_at']) ?></span>
                <?php endif; ?>
            </p>
            <p style="white-space:pre-wrap"><?= esc((string) $m['message']) ?></p>
            <?php if (empty($m['read_at'])): ?>
                <form method="post" action="<?= esc(admin_url('messages.php')) ?>" style="margin-top:0.75rem">
                    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                    <input type="hidden" name="action" value="mark_read">
                    <input type="hidden" name="message_id" value="<?= (int) $m['id'] ?>">
                    <button type="submit" class="btn-admin"><?= esc(t('admin_mark_read')) ?></button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
