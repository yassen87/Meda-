<?php
declare(strict_types=1);

require_once __DIR__ . '/smtp_mailer.php';

/**
 * Sends an OTP email using the built-in mail() via XAMPP sendmail (Gmail SMTP).
 */
function send_otp_email(string $toEmail, string $otp, string $type = 'register'): bool
{
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Zain Perfumes';
    $isAr = (function_exists('current_lang') && current_lang() === 'ar');

    if ($type === 'register') {
        $subjectRaw = $isAr ? "رمز التحقق الخاص بك - $siteName" : "Your Verification Code - $siteName";
        $heading    = $isAr ? "رمز التحقق" : "Verification Code";
        $body       = $isAr
            ? "شكراً لتسجيلك! رمز التحقق الخاص بك هو:"
            : "Thank you for registering! Your verification code is:";
    } elseif ($type === 'reset') {
        $subjectRaw = $isAr ? "إعادة تعيين كلمة المرور - $siteName" : "Password Reset Code - $siteName";
        $heading    = $isAr ? "رمز إعادة التعيين" : "Password Reset Code";
        $body       = $isAr
            ? "لقد طلبت إعادة تعيين كلمة مرورك. استخدم الرمز التالي:"
            : "You requested a password reset. Use the following code:";
    } else {
        $subjectRaw = $isAr ? "رمز الدخول الخاص بك - $siteName" : "Your Login Code - $siteName";
        $heading    = $isAr ? "رمز الدخول" : "Login Code";
        $body       = $isAr
            ? "رمز الدخول الخاص بك هو:"
            : "Your login code is:";
    }

    // Properly encode subject for UTF-8 / Arabic support
    $subject = '=?UTF-8?B?' . base64_encode($subjectRaw) . '?=';

    $dir = $isAr ? 'rtl' : 'ltr';
    $noteText = $isAr ? "هذا الرمز صالح لمدة 15 دقيقة فقط." : "This code is valid for 15 minutes only.";

    // Convert logo to base64 for local testing support
    $logoBase64 = '';
    $logoPath = __DIR__ . '/../assets/img/logo.png';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
        $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
    }
    
    // Fallback to public URL if local file is missing or for production
    $logoSrc = !empty($logoBase64) ? $logoBase64 : 'https://zeinperfumes.com/assets/img/logo.png';

    $htmlMessage = <<<HTML
<!DOCTYPE html>
<html dir="{$dir}" lang="{$dir}">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background:#f5f5f5; margin:0; padding:20px; }
  .card { max-width:480px; margin:0 auto; background:#fff; border-radius:12px;
          padding:40px; box-shadow:0 4px 20px rgba(0,0,0,.1); text-align:center; }
  .brand { font-size:26px; font-weight:800; color:#d4af37; letter-spacing:.1em; margin-bottom:8px; }
  h2 { color:#333; margin:0 0 16px; }
  p { color:#555; margin:0 0 24px; }
  .otp { font-size:42px; font-weight:900; letter-spacing:.4em; color:#d4af37;
         background:#fdfaf0; border:2px dashed #d4af37; border-radius:8px;
         padding:16px 24px; display:inline-block; margin:16px 0; }
  .note { font-size:13px; color:#999; margin-top:16px; }
</style>
</head>
<body>
  <div class="card">
    <div class="brand">
        <img src="{$logoSrc}" alt="Zein Perfumes" style="height: 60px; width: auto; margin-bottom: 20px;">
    </div>
    <h2>{$heading}</h2>
    <p>{$body}</p>
    <div class="otp">{$otp}</div>
    <p class="note">{$noteText}</p>
  </div>
</body>
</html>
HTML;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$siteName} <yassen74mostafa@gmail.com>\r\n";
    $headers .= "Reply-To: yassen74mostafa@gmail.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    return smtp_send($toEmail, $subjectRaw, $htmlMessage);
}

/**
 * Sends an order confirmation email.
 */
function send_order_confirmation_email(string $toEmail, string $orderNumber, float $total, string $customerName): bool
{
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Zain Perfumes';
    $isAr = (function_exists('current_lang') && current_lang() === 'ar');
    
    $subjectRaw = $isAr ? "تأكيد طلبك #$orderNumber - $siteName" : "Order Confirmation #$orderNumber - $siteName";
    $heading    = $isAr ? "شكراً لطلبك!" : "Thank you for your order!";
    $body       = $isAr 
        ? "مرحباً {$customerName}، لقد استلمنا طلبك بنجاح. رقم الطلب هو:" 
        : "Hello {$customerName}, we have successfully received your order. Your order number is:";
    
    $totalLabel = $isAr ? "إجمالي الطلب:" : "Order Total:";
    $formattedTotal = number_format($total, 2) . ' ' . ($isAr ? 'ج.م' : 'LE');

    $subject = '=?UTF-8?B?' . base64_encode($subjectRaw) . '?=';
    $dir = $isAr ? 'rtl' : 'ltr';

    $logoSrc = 'https://zeinperfumes.com/assets/img/logo.png';

    $htmlMessage = <<<HTML
<!DOCTYPE html>
<html dir="{$dir}" lang="{$dir}">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background:#f5f5f5; margin:0; padding:20px; }
  .card { max-width:550px; margin:0 auto; background:#fff; border-radius:12px;
          padding:40px; box-shadow:0 4px 20px rgba(0,0,0,.1); text-align:center; }
  .brand { margin-bottom: 20px; }
  h2 { color:#333; margin:0 0 16px; }
  p { color:#555; margin:0 0 24px; line-height: 1.6; }
  .order-number { font-size:24px; font-weight:700; color:#d4af37;
                  background:#fdfaf0; border:1px solid #d4af37; border-radius:8px;
                  padding:12px 20px; display:inline-block; margin:10px 0; }
  .total-box { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; font-size: 18px; font-weight: 600; }
  .footer-note { font-size:13px; color:#999; margin-top:30px; }
</style>
</head>
<body>
  <div class="card">
    <div class="brand">
        <img src="{$logoSrc}" alt="{$siteName}" style="height: 60px; width: auto;">
    </div>
    <h2>{$heading}</h2>
    <p>{$body}</p>
    <div class="order-number">#{$orderNumber}</div>
    <div class="total-box">
        {$totalLabel} <span style="color: #d4af37;">{$formattedTotal}</span>
    </div>
    <p class="footer-note">
        {$siteName} - Luxury Perfumes
    </p>
  </div>
</body>
</html>
HTML;

    return smtp_send($toEmail, $subjectRaw, $htmlMessage);
}
