<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if (is_client_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name             = trim($_POST['name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error = t('err_passwords_mismatch') ?: 'Passwords do not match.';
    } elseif ($name === '' || $email === '' || $password === '') {
        $error = t('admin_err_names_required');
    } else {
        $pdo = medal_pdo();
        if ($pdo) {
            $chk = $pdo->prepare('SELECT id FROM clients WHERE email = ?');
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = t('err_email_exists');
            } else {
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $otp = (string) rand(100000, 999999);
                    $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    
                    $st = $pdo->prepare('INSERT INTO clients (name, email, phone, password_hash, otp_code, otp_expires_at, is_verified) VALUES (?, ?, ?, ?, ?, ?, 0)');
                    $st->execute([$name, $email, $phone, $hash, $otp, $expires]);
                    
                    // Send OTP Email
                    send_otp_email($email, $otp, 'register');

                    header('Location: verify.php?email=' . urlencode($email));
                    exit;
                } catch (PDOException) {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

$pageTitle = t('register_title');
require __DIR__ . '/../includes/header.php';
?>
<style>
    .auth-page-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 6rem 1.5rem;
        background: radial-gradient(circle at center, var(--bg-warm), var(--bg));
        min-height: calc(100vh - var(--header-h));
    }

    .auth-card {
        width: 100%;
        max-width: 560px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 24px;
        padding: clamp(2rem, 5vw, 3.5rem);
        box-shadow: var(--shadow-md);
        position: relative;
        overflow: hidden;
    }

    .auth-card::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(90deg, var(--gold-dim), var(--gold-bright), var(--gold-dim));
    }

    .auth-card h1 {
        text-align: center;
        font-family: var(--font-serif);
        font-size: 1.8rem;
        color: var(--ink);
        margin-bottom: 2.5rem;
    }

    .auth-field { margin-bottom: 1.5rem; }

    .auth-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
    }
    @media (max-width: 540px) {
        .auth-row { grid-template-columns: 1fr; gap: 0; }
    }

    .auth-field label {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--gold);
        margin-bottom: 0.6rem;
        letter-spacing: 0.05em;
    }

    .auth-field input {
        width: 100%;
        background: var(--bg-elevated);
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 0.9rem 1.1rem;
        font-family: inherit;
        font-size: 1rem;
        color: var(--ink);
        transition: all 0.3s ease;
    }

    .auth-field input:focus {
        border-color: var(--gold);
        box-shadow: 0 0 0 4px var(--gold-glow);
        outline: none;
    }

    .btn-auth-primary {
        display: block;
        width: 100%;
        margin-top: 2rem;
        padding: 1rem;
        background: linear-gradient(135deg, var(--gold-bright), var(--gold));
        color: #1a1508;
        font-family: inherit;
        font-weight: 700;
        text-transform: uppercase;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-auth-primary:hover {
        filter: brightness(1.1);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px var(--gold-glow);
    }

    .auth-error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #fca5a5;
        border-radius: 12px;
        padding: 1rem;
        font-size: 0.9rem;
        margin-bottom: 2rem;
        text-align: center;
    }

    .auth-footer {
        margin-top: 2.5rem;
        padding-top: 2rem;
        border-top: 1px solid var(--border-subtle);
        text-align: center;
    }

    .auth-footer p {
        font-size: 0.95rem;
        color: var(--ink-muted);
        margin-bottom: 1rem;
    }

    .auth-footer a {
        color: var(--gold-bright);
        text-decoration: none;
        font-weight: 600;
    }

    .auth-footer a:hover {
        text-decoration: underline;
    }
</style>

<div class="auth-page-wrapper">
    <div class="auth-card">
        <h1><?= esc($pageTitle) ?></h1>

        <?php if ($error): ?>
            <div class="auth-error"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="auth-field">
                <label for="name"><?= esc(t('label_name')) ?></label>
                <input type="text" id="name" name="name" required autofocus placeholder="<?= esc(t('label_name')) ?>">
            </div>

            <div class="auth-row">
                <div class="auth-field">
                    <label for="email"><?= esc(t('label_email')) ?></label>
                    <input type="email" id="email" name="email" required placeholder="email@example.com">
                </div>
                <div class="auth-field">
                    <label for="phone"><?= esc(t('admin_th_phone')) ?></label>
                    <input type="text" id="phone" name="phone" placeholder="05xxxxxxxx">
                </div>
            </div>

            <div class="auth-row">
                <div class="auth-field">
                    <label for="password"><?= esc(t('label_password')) ?></label>
                    <input type="password" id="password" name="password" required minlength="6" placeholder="••••••••">
                </div>
                <div class="auth-field">
                    <label for="confirm_password"><?= esc(t('label_confirm_password')) ?></label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="btn-auth-primary"><?= esc(t('register_submit')) ?></button>
        </form>

        <div class="auth-footer">
            <p><?= esc(t('nav_login')) ?>?</p>
            <a href="login.php"><?= esc(t('nav_login')) ?></a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
