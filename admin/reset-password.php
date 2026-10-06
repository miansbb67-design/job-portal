<?php
/**
 * Admin Panel - Set New Password / Verify Code
 * Validates 6-digit verification codes or tokens and securely updates administrator password with bcrypt.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Redirect if already logged in
if (is_admin_logged_in()) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

$db = getDBConnection();
$error = '';
$codeVerified = false;
$targetAdmin = null;

// Read code or token from GET, POST, or session
$inputCode = trim($_GET['code'] ?? ($_GET['token'] ?? ($_POST['code'] ?? ($_POST['token'] ?? ''))));

// Check pre-filled code from URL
if (!empty($inputCode) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $db->prepare("SELECT * FROM admins WHERE reset_token = ? AND reset_token_expires > NOW() LIMIT 1");
    $stmt->execute([$inputCode]);
    $targetAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($targetAdmin) {
        $codeVerified = true;
    } else {
        $error = 'The verification code in your link is invalid or has expired. You can enter a valid code manually below.';
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } else {
        $code = trim($_POST['code'] ?? ($_POST['token'] ?? ''));
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($code)) {
            $error = 'Please enter the 6-digit verification code sent to your email.';
        } else {
            // Verify against database
            $stmt = $db->prepare("SELECT * FROM admins WHERE reset_token = ? AND reset_token_expires > NOW() LIMIT 1");
            $stmt->execute([$code]);
            $targetAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$targetAdmin) {
                $error = 'Invalid or expired verification code. Please check your email or request a new code.';
            } elseif (empty($newPassword) || empty($confirmPassword)) {
                $error = 'Please fill in both password fields.';
                $codeVerified = true;
            } elseif (strlen($newPassword) < 6) {
                $error = 'New password must be at least 6 characters long.';
                $codeVerified = true;
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Passwords do not match. Please verify and retype.';
                $codeVerified = true;
            } else {
                // Securely hash password with bcrypt
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

                // Update password and invalidate code
                $upStmt = $db->prepare("
                    UPDATE admins 
                    SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL, updated_at = NOW() 
                    WHERE id = ?
                ");
                $upStmt->execute([$newHash, $targetAdmin['id']]);

                // Cleanup session reset hints
                unset($_SESSION['reset_target_email'], $_SESSION['reset_target_username']);

                log_activity('RESET_PASSWORD_COMPLETED', 'Authentication', "Password reset successfully completed for admin: {$targetAdmin['username']}", $targetAdmin['id']);

                set_flash('success', "Your password has been successfully updated! You can now log in with your new password.");
                header('Location: ' . ADMIN_URL . '/login.php');
                exit;
            }
        }
    }
}

$flashSuccess = get_flash('success');
$flashInfo = get_flash('info');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | <?= SITE_NAME ?> Admin</title>
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
        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-top: 0.5rem;
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
        .code-input {
            font-family: Consolas, 'Courier New', monospace;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 6px;
            text-align: center;
            color: #059669;
            background: #f8fafc;
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
            line-height: 1.5;
        }
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
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
                    <path d="M21 2l-2 2m-2-2l2 2"></path>
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h1 class="login-title">Set New Password</h1>
            <?php if ($codeVerified && $targetAdmin): ?>
                <div class="verified-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Code verified for @<?= h($targetAdmin['username']) ?>
                </div>
            <?php else: ?>
                <p class="login-subtitle">Enter your 6-digit verification code and new password.</p>
            <?php endif; ?>
        </div>

        <?php if (!empty($flashSuccess)): ?>
            <div class="alert-success" role="alert">
                <?= $flashSuccess ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($flashInfo)): ?>
            <div class="alert-success" role="alert">
                <?= $flashInfo ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error" role="alert">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= ADMIN_URL ?>/reset-password.php" method="POST">
            <?= csrf_field(); ?>

            <?php if ($codeVerified && $targetAdmin): ?>
                <input type="hidden" name="code" value="<?= h($inputCode) ?>">
            <?php else: ?>
                <div class="form-group">
                    <label for="code" class="form-label">6-Digit Verification Code</label>
                    <input 
                        type="text" 
                        id="code" 
                        name="code" 
                        class="form-control code-input" 
                        required 
                        autofocus 
                        placeholder="&bull; &bull; &bull; &bull; &bull; &bull;" 
                        maxlength="64"
                        value="<?= h($inputCode) ?>"
                    >
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="password" class="form-label">New Password (minimum 6 characters)</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Enter your new password">
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Retype your new password">
            </div>

            <button type="submit" class="btn-submit">
                Update Password &rarr;
            </button>
        </form>

        <div class="back-link-wrap">
            <a href="<?= ADMIN_URL ?>/login.php" class="back-link">
                &larr; Back to Login
            </a>
            <a href="<?= ADMIN_URL ?>/forgot-password.php" class="back-link">
                Request New Code &rarr;
            </a>
        </div>
    </div>

</body>
</html>
