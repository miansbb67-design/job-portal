<?php
/**
 * Admin Panel - 403 Forbidden / Access Denied
 * Displays clean authorization failure view when backend permission checks fail.
 */

http_response_code(403);
$adminPageTitle = "Access Denied";
require_once __DIR__ . '/includes/admin-header.php';

$requiredPermission = $_GET['required'] ?? 'unspecified';
?>

<div style="max-width: 680px; margin: 3rem auto; text-align: center;">
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 3.5rem 2.5rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);">
        <div style="width: 72px; height: 72px; margin: 0 auto 1.5rem; background: #fee2e2; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>

        <span style="display: inline-block; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; font-size: 0.775rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.25rem 0.85rem; border-radius: 9999px; margin-bottom: 1rem;">
            HTTP 403 &bull; Authorization Required
        </span>

        <h2 style="font-family: var(--admin-heading); font-size: 1.85rem; color: #0f172a; margin-bottom: 0.75rem; font-weight: 800;">
            Access Denied
        </h2>

        <p style="color: #64748b; font-size: 1rem; line-height: 1.6; margin-bottom: 1.75rem;">
            You do not have permission to access this module or perform this action. Your administrator role (<strong style="color:#0f172a;"><?= h($currentUser['role'] ?? 'admin') ?></strong>) has not been granted the <code style="background:#f1f5f9; color:#0f172a; padding:0.2rem 0.5rem; border-radius:4px; font-weight:600;"><?= h($requiredPermission) ?></code> permission key.
        </p>

        <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 1rem 1.25rem; margin-bottom: 2rem; text-align: left; font-size: 0.875rem; color: #475569;">
            <div style="font-weight: 700; margin-bottom: 0.25rem; color: #0f172a;">Need access to this feature?</div>
            Please contact the <strong>Super Administrator</strong> to update your assigned role permissions in the Admin Management console.
        </div>

        <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?= ADMIN_URL ?>/index.php" class="admin-btn admin-btn-primary" style="padding: 0.65rem 1.5rem; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; border-radius: 8px; font-weight: 700;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Return to Dashboard
            </a>
            <a href="javascript:history.back()" class="admin-btn admin-btn-secondary" style="padding: 0.65rem 1.5rem; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; border-radius: 8px; font-weight: 600; background: #ffffff; border: 1px solid #cbd5e1; color: #334155;">
                Go Back
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
