<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pdo = medal_pdo();
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$client = null;
$students = [];

if ($clientId > 0 && $pdo !== null) {
    try {
        $st = $pdo->prepare('SELECT * FROM clients WHERE id = ?');
        $st->execute([$clientId]);
        $client = $st->fetch();

        if ($client) {
            $st = $pdo->prepare('SELECT * FROM students WHERE client_id = ? ORDER BY created_at DESC');
            $st->execute([$clientId]);
            $students = $st->fetchAll();
        }
    } catch (Throwable $e) {}
}

if (!$client) {
    die(t('admin_client_not_found'));
}

$pageTitle = t('admin_students_manage') . ' - ' . $client['name'];
require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <h1><?= esc($pageTitle) ?></h1>
    <a href="clients.php" class="admin-btn admin-badge"><?= esc(t('admin_back_orders')) ?></a>
</div>

<div class="admin-card" style="margin-bottom: 2rem;">
    <h2><?= esc(t('admin_new_student')) ?></h2>
    <form action="student_save.php" method="POST" class="admin-form admin-form--inline">
        <input type="hidden" name="client_id" value="<?= $clientId ?>">
        <div class="admin-form-group">
            <input type="text" name="name" class="admin-input" placeholder="<?= esc(t('admin_student_name')) ?>" required>
        </div>
        <div class="admin-form-group">
            <input type="text" name="level" class="admin-input" placeholder="<?= esc(t('admin_student_level')) ?>">
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn--primary"><?= esc(t('admin_save')) ?></button>
        </div>
    </form>
</div>

<?php if ($students === []): ?>
    <p class="admin-muted"><?= esc(t('client_no_students')) ?></p>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?= esc(t('admin_student_name')) ?></th>
                    <th><?= esc(t('admin_student_level')) ?></th>
                    <th><?= esc(t('admin_student_status')) ?></th>
                    <th><?= esc(t('admin_student_notes')) ?></th>
                    <th><?= esc(t('admin_th_actions')) ?? 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <form action="student_save.php" method="POST">
                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                            <input type="hidden" name="client_id" value="<?= $clientId ?>">
                            <td><input type="text" name="name" class="admin-input admin-input--sm" value="<?= esc((string)$s['name']) ?>" required></td>
                            <td><input type="text" name="level" class="admin-input admin-input--sm" value="<?= esc((string)$s['level']) ?>"></td>
                            <td>
                                <select name="status" class="admin-input admin-input--sm">
                                    <option value="active" <?= $s['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $s['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="graduated" <?= $s['status'] === 'graduated' ? 'selected' : '' ?>>Graduated</option>
                                </select>
                            </td>
                            <td>
                                <textarea name="notes" class="admin-input admin-input--sm" rows="2"><?= esc((string)$s['notes']) ?></textarea>
                            </td>
                            <td>
                                <div class="admin-actions">
                                    <button type="submit" class="admin-btn admin-btn--sm"><?= esc(t('admin_save')) ?></button>
                                    <a href="student_delete.php?id=<?= (int)$s['id'] ?>&client_id=<?= $clientId ?>" class="admin-btn admin-btn--sm admin-btn--danger" onclick="return confirm('<?= esc(t('admin_confirm_delete')) ?? 'Delete?' ?>')"><?= esc(t('admin_delete')) ?></a>
                                </div>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
