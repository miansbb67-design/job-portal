<?php
/**
 * Admin Panel - Forgot Password Recovery
 * Generates high-trust 6-digit security verification codes and dispatches emails via Gmail SMTP.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/mail_service.php';

// Redirect if already logged in
if (is_admin_logged_in()) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

$error = '';
$success = '';
$infoNote = '';

/**
 * Masks an email address for privacy (e.g. m*****8@gmail.com)
 */
function mask_email_display(string $email): string {
    $parts = explode('@', $email);
    if (count($parts) !== 2) return $email;
    $user = $parts[0];
    $domain = $parts[1];
    $len = strlen($user);
    if ($len <= 2) {
        return substr($user, 0, 1) . '*@' . $domain;
    }
    return substr($user, 0, 1) . str_repeat('*', min(5, $len - 2)) . substr($user, -1) . '@' . $domain;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } else {
        $usernameOrEmail = trim($_POST['username_or_email'] ?? '');

        if (empty($usernameOrEmail)) {
            $error = 'Please enter your registered username or email address.';
        } else {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && ($admin['status'] ?? 'active') === 'active') {
                // Generate secure 6-digit verification PIN (e.g. 849201)
                $resetCode = sprintf('%06d', random_int(100000, 999999));

                // Save code into database with 1-hour expiration
                $upStmt = $db->prepare("UPDATE admins SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
                $upStmt->execute([$resetCode, $admin['id']]);

                // Construct direct action URL (fallback to localhost if no live domain)
                $baseAppUrl = getenv('APP_URL');
                if (!empty($baseAppUrl) && strpos($baseAppUrl, 'http') === 0 && strpos($baseAppUrl, 'localhost') === false) {
                    $resetUrl = rtrim($baseAppUrl, '/') . '/admin/reset-password.php?code=' . urlencode($resetCode);
                } else {
                    $resetUrl = ADMIN_URL . '/reset-password.php?code=' . urlencode($resetCode);
                }

                $recipientName = !empty($admin['name']) ? $admin['name'] : $admin['username'];
                $recipientEmail = $admin['email'];

                // HIGH DELIVERABILITY TRANSACTIONAL SUBJECT:
                // Using "[SITE]: [CODE] is your verification code" classifies the email as OTP/Security Verification,
                // routing directly into Primary Inbox and preventing Gmail from grouping into old spam threads.
                $emailSubject = SITE_NAME . ": {$resetCode} is your verification code";

                $emailHtml = '
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>' . htmlspecialchars(SITE_NAME) . ' Verification</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b; -webkit-text-size-adjust: 100%; }
                        .email-wrap { max-width: 540px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
                        .email-head { background: #0f172a; padding: 22px 28px; text-align: left; }
                        .email-head-title { color: #ffffff; font-size: 19px; font-weight: 700; margin: 0; }
                        .email-head-title span { color: #10b981; }
                        .email-main { padding: 30px 28px; line-height: 1.6; }
                        .code-box { background: #ecfdf5; border: 1.5px dashed #059669; border-radius: 10px; padding: 20px; text-align: center; margin: 24px 0; }
                        .code-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #065f46; font-weight: 700; margin-bottom: 8px; }
                        .code-digits { font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #059669; font-family: Consolas, "Courier New", monospace; line-height: 1.2; }
                        .code-exp { font-size: 12px; color: #047857; margin-top: 8px; }
                        .btn-box { text-align: center; margin: 22px 0 10px 0; }
                        .btn-reset { display: inline-block; background: #059669; color: #ffffff !important; padding: 13px 30px; font-size: 14px; font-weight: 700; text-decoration: none; border-radius: 8px; }
                        .email-foot { font-size: 12px; color: #64748b; margin-top: 26px; padding-top: 18px; border-top: 1px solid #f1f5f9; line-height: 1.5; }
                    </style>
                </head>
                <body>
                    <!-- Preheader text (appears in inbox preview) -->
                    <div style="display:none;font-size:1px;color:#fff;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">
                        Your ' . htmlspecialchars(SITE_NAME) . ' verification code is ' . $resetCode . '. Valid for 60 minutes.
                    </div>
                    <div class="email-wrap">
                        <div class="email-head">
                            <div class="email-head-title">' . htmlspecialchars(SITE_NAME) . ' <span>Security</span></div>
                        </div>
                        <div class="email-main">
                            <p style="font-size: 15px; margin-top: 0;">Hello <strong>' . htmlspecialchars($recipientName) . '</strong>,</p>
                            <p style="color: #334155; font-size: 14px;">We received a request to verify your administrator account (<code>@' . htmlspecialchars($admin['username']) . '</code>) and reset your password.</p>
                            
                            <div class="code-box">
                                <div class="code-label">Security Verification Code</div>
                                <div class="code-digits">' . $resetCode . '</div>
                                <div class="code-exp">Valid for 60 minutes &bull; Do not share this code</div>
                            </div>

                            <p style="font-size: 13px; color: #475569; text-align: center;">Enter this 6-digit code on the reset screen, or click the button below to proceed directly:</p>

                            <div class="btn-box">
                                <a href="' . $resetUrl . '" class="btn-reset">Confirm &amp; Reset Password &rarr;</a>
                            </div>

                            <div class="email-foot">
                                <strong>Security Notice:</strong> If you did not initiate this request, your account is completely safe and no changes were made. You can safely disregard this email.<br><br>
                                &copy; ' . date('Y') . ' ' . htmlspecialchars(SITE_NAME) . ' Platform. Automated Security Notice.
                            </div>
                        </div>
                    </div>
                </body>
                </html>';

                $emailText = "Hello {$recipientName},\n\n"
                    . "Your " . SITE_NAME . " verification code is: {$resetCode}\n\n"
                    . "Enter this 6-digit code on the reset screen to choose a new password.\n"
                    . "This code is valid for 60 minutes.\n\n"
                    . "Direct Reset Link:\n"
                    . "{$resetUrl}\n\n"
                    . "Security Notice: If you did not make this request, you can safely ignore this email.\n\n"
                    . "-- " . SITE_NAME . " Security Team";

                // Dispatch via PHPMailer Gmail SMTP Service
                $sendResult = send_smtp_email([
                    'to_email'   => $recipientEmail,
                    'to_name'    => $recipientName,
                    'subject'    => $emailSubject,
                    'body_html'  => $emailHtml,
                    'body_text'  => $emailText
                ]);

                log_activity('PASSWORD_RESET_REQUEST', 'Authentication', "Initiated password reset for admin user: {$admin['username']} ({$recipientEmail})", $admin['id']);

                // Store email hint in session for display
                $_SESSION['reset_target_email'] = $recipientEmail;
                $_SESSION['reset_target_username'] = $admin['username'];

                // Redirect directly to the reset password page where user enters the 6-digit code
                set_flash('success', "A 6-digit verification code has been sent to " . mask_email_display($recipientEmail) . ". Enter the code below to choose a new password.");
                header('Location: ' . ADMIN_URL . '/reset-password.php');
                exit;
            } else {
                // Security best practice: Uniform response for non-existent users
                $success = "If an account matches your details, a 6-digit verification code has been sent to your email address.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | <?= SITE_NAME ?> Admin</title>
    <meta name="robots" content="noindex, nofollow">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05), 0 8px 10px -6px rgba(15, 23, 42, 0.02);
            width: 100%;
            max-width: 440px;
            padding: 2.25rem 2rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }
        .login-brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: #ecfdf5;
            color: #059669;
            margin-bottom: 1rem;
        }
        .login-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.35rem;
        }
        .login-subtitle {
            font-size: 0.85rem;
            color: #64748b;
            line-height: 1.5;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            font-size: 0.825rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 0.4rem;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            color: #0f172a;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.2);
        }
        .btn-submit {
            width: 100%;
            background: #059669;
            color: #ffffff;
            border: none;
            padding: 0.85rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease;
            margin-top: 0.5rem;
        }
        .btn-submit:hover {
            background: #047857;
        }
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 0.85rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }
        .back-link-wrap {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .back-link {
            font-size: 0.85rem;
            color: #059669;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <div class="login-brand-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h1 class="login-title">Reset Password</h1>
            <p class="login-subtitle">Enter your account username or email to receive a secure 6-digit verification code.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error" role="alert">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert-success" role="alert">
                <?= $success ?>
                <div style="margin-top: 0.75rem;">
                    <a href="<?= ADMIN_URL ?>/reset-password.php" style="color: #047857; font-weight: 700; text-decoration: underline;">
                        Already have your code? Enter it here &rarr;
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?= ADMIN_URL ?>/forgot-password.php" method="POST">
            <?= csrf_field(); ?>

            <div class="form-group">
                <label for="username_or_email" class="form-label">Username or Email Address</label>
                <input type="text" id="username_or_email" name="username_or_email" class="form-control" required autofocus placeholder="e.g. admin or faizan@example.com" value="<?= isset($_POST['username_or_email']) ? h($_POST['username_or_email']) : '' ?>">
            </div>

            <button type="submit" class="btn-submit">
                Send Verification Code &rarr;
            </button>
        </form>

        <div class="back-link-wrap">
            <a href="<?= ADMIN_URL ?>/login.php" class="back-link">
                &larr; Back to Login
            </a>
            <a href="<?= ADMIN_URL ?>/reset-password.php" class="back-link">
                Have a code? &rarr;
            </a>
        </div>
    </div>

</body>
</html>
