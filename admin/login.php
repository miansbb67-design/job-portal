<?php
/**
 * Admin Panel - Authentication Login
 * Production-ready clean authentication with bcrypt password verification,
 * CSRF token defense, and session fixation protection.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already authenticated
if (is_admin_logged_in()) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

$error = '';
$usernameOrEmail = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $usernameOrEmail = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($usernameOrEmail) || empty($password)) {
            $error = 'Please enter both username/email and password.';
        } else {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Check if account is active
                if (($admin['status'] ?? 'active') !== 'active') {
                    log_activity('FAILED_LOGIN_INACTIVE', 'Authentication', "Login attempt for disabled account: {$admin['username']}", $admin['id']);
                    $error = 'This administrator account is currently inactive. Please contact the Super Administrator.';
                } else {
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);

                    // Update last login timestamp
                    $upStmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                    $upStmt->execute([$admin['id']]);

                    // Set admin session state
                    $_SESSION['admin_id'] = (int)$admin['id'];
                    $_SESSION['admin_name'] = $admin['name'] ?? $admin['username'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'] ?? 'admin@careerpulse.com';
                    $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
                    $_SESSION['admin_status'] = 'active';

                    // Cache permissions
                    get_admin_permission_keys($admin['id'], true);

                    // Log successful login
                    log_activity('LOGIN', 'Authentication', "Logged in successfully as [{$admin['role']}]", $admin['id']);

                    set_flash('success', "Welcome back, " . h($admin['name'] ?? $admin['username']) . "! You have logged in successfully.");
                    header('Location: ' . ADMIN_URL . '/index.php');
                    exit;
                }
            } else {
                log_activity('FAILED_LOGIN', 'Authentication', "Failed login attempt for username/email: " . substr($usernameOrEmail, 0, 50));
                $error = 'Invalid credentials. Please verify your username and password.';
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
    <title>Admin Login | <?= SITE_NAME ?></title>
    <meta name="robots" content="noindex, nofollow">

    <!-- Modern Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body.login-page-body {
            font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #090e1a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #0f172a;
            position: relative;
            overflow-x: hidden;
        }
        /* Subtle background radial glow */
        body.login-page-body::before {
            content: '';
            position: absolute;
            top: 20%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(5, 150, 105, 0.15) 0%, rgba(9, 14, 26, 0) 70%);
            pointer-events: none;
            z-index: 0;
        }
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 410px;
            padding: 2.5rem 2.25rem;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-brand-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: 12px;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
        }
        .login-title {
            font-size: 1.45rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin-bottom: 0.35rem;
        }
        .login-subtitle {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 500;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            font-size: 0.825rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 0.45rem;
        }
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 9px;
            font-size: 0.95rem;
            font-family: inherit;
            color: #0f172a;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.18);
        }
        .form-control::placeholder {
            color: #94a3b8;
        }
        .password-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }
        .password-input-wrap .form-control {
            padding-right: 2.75rem;
        }
        .btn-toggle-password {
            position: absolute;
            right: 0.65rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            padding: 0.35rem;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: color 0.15s ease, background 0.15s ease;
        }
        .btn-toggle-password:hover {
            color: #059669;
            background: #f1f5f9;
        }
        .btn-submit {
            width: 100%;
            background: #059669;
            color: #ffffff;
            border: none;
            padding: 0.85rem 1rem;
            border-radius: 9px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .btn-submit:hover {
            background: #047857;
            box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35);
            transform: translateY(-1px);
        }
        .btn-submit:active {
            transform: translateY(0);
        }
        /* Flash & Validation Alerts */
        .flash-messages-container {
            margin-bottom: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 9px;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            line-height: 1.45;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: opacity 0.25s ease, transform 0.25s ease;
        }
        .alert-content {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex: 1;
        }
        .alert-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .alert-text {
            flex: 1;
        }
        .alert-close {
            background: transparent !important;
            border: none !important;
            outline: none !important;
            cursor: pointer;
            padding: 0.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            color: currentColor;
            opacity: 0.65;
            transition: opacity 0.15s ease, background-color 0.15s ease;
            flex-shrink: 0;
        }
        .alert-close:hover {
            opacity: 1;
            background-color: rgba(0, 0, 0, 0.08) !important;
        }
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 0.8rem 1rem;
            border-radius: 9px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }
        .login-back-link {
            margin-top: 1.75rem;
            text-align: center;
        }
        .login-back-link a {
            font-size: 0.825rem;
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .login-back-link a:hover {
            color: #059669;
        }
    </style>
</head>
<body class="login-page-body">

    <div class="login-card">
        <!-- Brand Header -->
        <div class="login-header">
            <div class="login-brand-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
            </div>
            <h1 class="login-title"><?= SITE_NAME ?> Admin</h1>
            <p class="login-subtitle">Sign in to access your administrative dashboard</p>
        </div>

        <!-- Flash Notifications -->
        <?= display_flash_messages(); ?>

        <!-- Form Error Banner -->
        <?php if (!empty($error)): ?>
            <div class="alert-error" role="alert">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                <span><?= h($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Clean Authentication Form -->
        <form action="<?= ADMIN_URL ?>/login.php" method="POST" autocomplete="on">
            <?= csrf_field(); ?>

            <div class="form-group">
                <label for="username" class="form-label">Username or Email</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control" 
                    required 
                    autofocus 
                    value="<?= !empty($usernameOrEmail) ? h($usernameOrEmail) : '' ?>" 
                    placeholder="Enter your username or email"
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem;">
                    <label for="password" class="form-label" style="margin-bottom: 0;">Password</label>
                    <a href="<?= ADMIN_URL ?>/forgot-password.php" style="font-size: 0.8rem; color: #059669; text-decoration: none; font-weight: 700; transition: color 0.15s ease;">Forgot Password?</a>
                </div>
                <div class="password-input-wrap">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        required 
                        placeholder="Enter your password"
                        autocomplete="current-password"
                    >
                    <button type="button" id="togglePasswordBtn" class="btn-toggle-password" title="Show password" aria-label="Show password">
                        <svg id="eyeIcon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg id="eyeOffIcon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>Sign In to Dashboard</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
        </form>

        <!-- Back to Website -->
        <div class="login-back-link">
            <a href="<?= BASE_URL ?>/">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Return to Public Website</span>
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password Visibility Toggle
            const toggleBtn = document.getElementById('togglePasswordBtn');
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeOffIcon = document.getElementById('eyeOffIcon');

            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    eyeIcon.style.display = isPassword ? 'none' : 'block';
                    eyeOffIcon.style.display = isPassword ? 'block' : 'none';
                    toggleBtn.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
                    toggleBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                    passwordInput.focus();
                });
            }
        });
    </script>
</body>
</html>
