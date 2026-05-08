<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_settings');

$pdo = medal_pdo();
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        admin_verify_csrf();
        
        $en = $_POST['announce_en'] ?? '';
        $ar = $_POST['announce_ar'] ?? '';

        if ($pdo !== null) {
            $st = $pdo->prepare("UPDATE settings SET setting_value_en = ?, setting_value_ar = ? WHERE setting_key = 'announce_shipping'");
            $st->execute([$en, $ar]);
            $success = true;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$row = ['setting_value_en' => '', 'setting_value_ar' => ''];
if ($pdo !== null) {
    try {
        $st = $pdo->query("SELECT setting_value_en, setting_value_ar FROM settings WHERE setting_key = 'announce_shipping'");
        $r = $st->fetch();
        if ($r) $row = $r;
    } catch (Throwable) {}
}

require __DIR__ . '/_layout_start.php';
?>

<h1><?= esc(t('admin_settings')) ?></h1>
<p class="admin-lead"><?= esc(t('admin_settings_lead')) ?></p>

<?php if ($success): ?>
    <div class="admin-alert is-success" style="background:#dcfce7; color:#166534; padding:1rem; border-radius:4px; margin-bottom:1rem; border: 1px solid #bbf7d0;">
        Settings saved successfully!
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="admin-alert is-error" style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:4px; margin-bottom:1rem; border: 1px solid #fecaca;">
        <?= esc($error) ?>
    </div>
<?php endif; ?>

<form class="admin-form" method="post">
    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">

    <div class="admin-card">
        <label for="announce_en"><?= esc(t('admin_label_announce_en')) ?></label>
        <input type="text" id="announce_en" name="announce_en" required value="<?= esc($row['setting_value_en']) ?>" style="width:100%; margin-bottom:1rem; padding:0.5rem; border:1px solid #ccc; border-radius:4px;">
        
        <label for="announce_ar"><?= esc(t('admin_label_announce_ar')) ?></label>
        <input type="text" id="announce_ar" name="announce_ar" required value="<?= esc($row['setting_value_ar']) ?>" dir="rtl" style="width:100%; margin-bottom:1rem; padding:0.5rem; border:1px solid #ccc; border-radius:4px;">
    </div>

    <p style="margin-top:1.5rem;"><button type="submit" class="btn-admin"><?= esc(t('admin_save')) ?></button></p>
</form>

<?php require __DIR__ . '/_layout_end.php'; ?>
