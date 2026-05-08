<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if (is_client_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $pdo      = medal_pdo();
    if ($pdo) {
        $st = $pdo->prepare('SELECT * FROM clients WHERE email = ?');
        $st->execute([$email]);
        $client = $st->fetch();
        if ($client && password_verify($password, $client['password_hash'])) {
            $otp = (string) rand(100000, 999999);
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            $upd = $pdo->prepare('UPDATE clients SET otp_code = ?, otp_expires_at = ? WHERE id = ?');
            $upd->execute([$otp, $expires, $client['id']]);
            
            // Send OTP Email
            send_otp_email($client['email'], $otp, 'login');

            header('Location: verify.php?email=' . urlencode($client['email']));
            exit;
        } else {
            $error = t('client_err_invalid');
        }
    }
}

$pageTitle = t('client_login_title');
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
        max-width: 460px;
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
        <h1><?= esc(t('client_login_heading')) ?></h1>

        <?php if ($error): ?>
            <div class="auth-error"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="auth-field">
                <label for="email"><?= esc(t('label_email')) ?></label>
                <input type="email" id="email" name="email" required autofocus placeholder="email@example.com">
            </div>
            <div class="auth-field">
                <label for="password"><?= esc(t('label_password')) ?></label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
                <div style="text-align:right; margin-top:0.6rem;">
                    <a href="forgot.php" style="font-size:0.85rem; color:var(--gold-dim); text-decoration:none;">
                        <?= esc(current_lang() === 'ar' ? 'نسيت كلمة المرور؟' : 'Forgot Password?') ?>
                    </a>
                </div>
            </div>
            <button type="submit" class="btn-auth-primary"><?= esc(t('client_login_submit')) ?></button>
        </form>

        <div class="auth-footer">
            <p><?= esc(t('nav_register')) ?>?</p>
            <a href="register.php"><?= esc(t('nav_register')) ?></a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
