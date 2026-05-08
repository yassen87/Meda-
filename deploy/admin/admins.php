<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_bootstrap.php';
require_admin('settings'); // Only superadmins or people with settings permission can manage other admins

$pdo = medal_pdo();
$error = '';
$success = '';

// Handle Delete
if (isset($_GET['delete'])) {
    admin_verify_csrf();
    $id = (int)$_GET['delete'];
    if ($id === (int)$_SESSION[ADMIN_SESSION_KEY]) {
        $error = "You cannot delete yourself!";
    } else {
        $st = $pdo->prepare("DELETE FROM admin_users WHERE id = ? AND role != 'superadmin'");
        $st->execute([$id]);
        $success = "Admin deleted successfully.";
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'admin';
    $perms = isset($_POST['perms']) ? implode(',', $_POST['perms']) : '';

    if ($username === '') {
        $error = "Username is required.";
    } else {
        if ($id > 0) {
            // Update
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $st = $pdo->prepare("UPDATE admin_users SET username = ?, password_hash = ?, role = ?, permissions = ? WHERE id = ?");
                $st->execute([$username, $hash, $role, $perms, $id]);
            } else {
                $st = $pdo->prepare("UPDATE admin_users SET username = ?, role = ?, permissions = ? WHERE id = ?");
                $st->execute([$username, $role, $perms, $id]);
            }
            $success = "Admin updated successfully.";
        } else {
            // Create
            if ($password === '') {
                $error = "Password is required for new users.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $st = $pdo->prepare("INSERT INTO admin_users (username, password_hash, role, permissions) VALUES (?, ?, ?, ?)");
                try {
                    $st->execute([$username, $hash, $role, $perms]);
                    $success = "Admin created successfully.";
                } catch (PDOException $e) {
                    $error = "Username already exists.";
                }
            }
        }
    }
}

$admins = $pdo->query("SELECT * FROM admin_users ORDER BY id ASC")->fetchAll();

$pageTitle = t('admin_nav_admins');
include __DIR__ . '/_layout_start.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-title"><?= esc($pageTitle) ?></h1>
    <button type="button" class="admin-btn admin-btn--primary" onclick="openAdminModal()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end:8px;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="16" y1="11" x2="22" y2="11"/></svg>
        إضافة موظف جديد
    </button>
</div>

<?php if ($error): ?>
    <div class="admin-alert admin-alert--error"><?= esc($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="admin-alert admin-alert--success"><?= esc($success) ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>اسم المستخدم</th>
                    <th>الدور</th>
                    <th>الصلاحيات</th>
                    <th>تاريخ الإنشاء</th>
                    <th style="text-align: end;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $a): ?>
                <tr>
                    <td>#<?= (int)$a['id'] ?></td>
                    <td><strong><?= esc($a['username']) ?></strong></td>
                    <td>
                        <span class="admin-badge <?= $a['role'] === 'superadmin' ? 'admin-badge--success' : 'admin-badge--warning' ?>">
                            <?= $a['role'] === 'superadmin' ? 'مدير عام' : 'موظف' ?>
                        </span>
                    </td>
                    <td style="max-width: 200px; font-size: 0.85em; color: #666;">
                        <?= $a['role'] === 'superadmin' ? 'كل الصلاحيات' : esc($a['permissions']) ?>
                    </td>
                    <td><?= esc($a['created_at']) ?></td>
                    <td style="text-align: end;">
                        <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" 
                                onclick='editAdmin(<?= json_encode($a) ?>)'>
                            تعديل
                        </button>
                        <?php if ($a['role'] !== 'superadmin' && $a['id'] != $_SESSION[ADMIN_SESSION_KEY]): ?>
                            <a href="?delete=<?= (int)$a['id'] ?>&csrf=<?= esc(admin_csrf_token()) ?>" 
                               class="admin-btn admin-btn--danger admin-btn--sm"
                               onclick="return confirm('هل أنت متأكد من حذف هذا الموظف؟')">حذف</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Admin Modal -->
<div id="adminModal" class="admin-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="admin-card" style="width:100%; max-width:500px; margin:20px;">
        <h2 id="modalTitle" style="margin-bottom:20px;">إضافة موظف جديد</h2>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
            <input type="hidden" name="id" id="form-id" value="0">
            
            <div class="admin-form-group">
                <label class="admin-label">اسم المستخدم</label>
                <input type="text" name="username" id="form-username" class="admin-input" required>
            </div>
            
            <div class="admin-form-group">
                <label class="admin-label">كلمة المرور (اتركها فارغة للتعديل بدون تغيير)</label>
                <input type="password" name="password" id="form-password" class="admin-input">
            </div>

            <div class="admin-form-group">
                <label class="admin-label">نوع الحساب</label>
                <select name="role" id="form-role" class="admin-input" onchange="togglePerms()">
                    <option value="admin">موظف (صلاحيات محدودة)</option>
                    <option value="superadmin">مدير عام (صلاحيات كاملة)</option>
                </select>
            </div>

            <div id="perms-section">
                <label class="admin-label">الصلاحيات المسموحة:</label>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:10px;">
                    <?php 
                    $available = [
                        'orders' => 'الطلبات',
                        'products' => 'المنتجات',
                        'categories' => 'التصنيفات',
                        'promo_codes' => 'الكوبونات',
                        'clients' => 'العملاء',
                        'messages' => 'الرسائل',
                        'settings' => 'الإعدادات'
                    ];
                    foreach ($available as $key => $label): ?>
                    <label style="display:flex; align-items:center; gap:8px; font-size:0.9em; cursor:pointer;">
                        <input type="checkbox" name="perms[]" value="<?= $key ?>" class="perm-check"> <?= esc($label) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:30px;">
                <button type="button" class="admin-btn admin-btn--secondary" onclick="closeAdminModal()">إلغاء</button>
                <button type="submit" class="admin-btn admin-btn--primary">حفظ البيانات</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdminModal() {
    document.getElementById('form-id').value = '0';
    document.getElementById('form-username').value = '';
    document.getElementById('form-password').value = '';
    document.getElementById('form-role').value = 'admin';
    document.getElementById('modalTitle').innerText = 'إضافة موظف جديد';
    document.querySelectorAll('.perm-check').forEach(c => c.checked = false);
    togglePerms();
    document.getElementById('adminModal').style.display = 'flex';
}

function closeAdminModal() {
    document.getElementById('adminModal').style.display = 'none';
}

function editAdmin(admin) {
    document.getElementById('form-id').value = admin.id;
    document.getElementById('form-username').value = admin.username;
    document.getElementById('form-password').value = '';
    document.getElementById('form-role').value = admin.role;
    document.getElementById('modalTitle').innerText = 'تعديل بيانات الموظف';
    
    const perms = admin.permissions ? admin.permissions.split(',') : [];
    document.querySelectorAll('.perm-check').forEach(c => {
        c.checked = perms.includes(c.value);
    });
    
    togglePerms();
    document.getElementById('adminModal').style.display = 'flex';
}

function togglePerms() {
    const role = document.getElementById('form-role').value;
    document.getElementById('perms-section').style.display = (role === 'superadmin') ? 'none' : 'block';
}
</script>

<?php include __DIR__ . '/_layout_end.php'; ?>
