<?php
/**
 * Admin Panel - Section Visibility Control
 * Simple Enable/Disable toggles to show or hide major frontend website sections.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/includes/auth-check.php';

// Authorization: Requires settings permission or Super Admin
if (!is_super_admin() && !has_permission('settings.view')) {
    require_permission('settings.view');
}

$db = getDBConnection();

// Handle Toggle Action (via AJAX JSON or Traditional Form POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    
    // Check if JSON request
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
              (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
    
    if ($isAjax && empty($_POST)) {
        $raw = file_get_contents('php://input');
        $jsonData = json_decode($raw, true) ?: [];
        $_POST = $jsonData;
        $token = $_POST['csrf_token'] ?? '';
    }

    if (!verify_csrf_token($token)) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh the page.']);
            exit;
        }
        set_flash('error', 'Security token expired. Please refresh the page and try again.');
        header('Location: ' . ADMIN_URL . '/section-visibility.php');
        exit;
    }

    $sectionKey = trim((string)($_POST['section_key'] ?? ''));
    $action = $_POST['action'] ?? 'toggle';

    if (!empty($sectionKey)) {
        $stmt = $db->prepare("SELECT id, section_key, section_name, is_enabled FROM section_visibility WHERE section_key = ? LIMIT 1");
        $stmt->execute([$sectionKey]);
        $sec = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sec) {
            $newStatus = 0;
            if ($action === 'enable') {
                $newStatus = 1;
            } elseif ($action === 'disable') {
                $newStatus = 0;
            } else {
                // Toggle
                $newStatus = ((int)$sec['is_enabled'] === 1) ? 0 : 1;
            }

            $updateStmt = $db->prepare("UPDATE section_visibility SET is_enabled = ? WHERE section_key = ?");
            $updateStmt->execute([$newStatus, $sectionKey]);

            $statusLabel = ($newStatus === 1) ? 'ENABLED' : 'DISABLED';
            $msg = "Section '{$sec['section_name']}' is now {$statusLabel}.";

            log_activity('update', 'settings', "Updated visibility of '{$sec['section_name']}' to {$statusLabel}");

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'section_key' => $sectionKey,
                    'is_enabled' => $newStatus,
                    'status_label' => $statusLabel,
                    'message' => $msg
                ]);
                exit;
            }

            set_flash('success', $msg);
        } else {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Section not found.']);
                exit;
            }
            set_flash('error', 'Section not found.');
        }
    }

    header('Location: ' . ADMIN_URL . '/section-visibility.php');
    exit;
}

// Fetch all sections ordered by sort_order
$sections = $db->query("SELECT * FROM section_visibility ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

$adminPageTitle = "Section Visibility";
include __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-content-inner">
    <!-- Breadcrumb -->
    <div style="font-size:0.85rem; color:#64748b; margin-bottom:0.75rem; display:flex; align-items:center; gap:0.4rem;">
        <a href="<?= ADMIN_URL ?>/index.php" style="color:#64748b; text-decoration:none;">Dashboard</a>
        <span>&rsaquo;</span>
        <span style="color:#0f172a; font-weight:600;">Section Visibility</span>
    </div>

    <!-- Header Section -->
    <div class="admin-page-header" style="margin-bottom:1.75rem; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 style="font-size:1.65rem; font-weight:800; color:#0f172a; margin:0 0 0.35rem;">Section Visibility</h1>
            <p style="font-size:0.9rem; color:#64748b; margin:0;">
                Control which major sections appear on the website. Use the toggles below to turn sections ON or OFF.
            </p>
        </div>

        <div>
            <a href="<?= BASE_URL ?>/" target="_blank" class="admin-btn-xs" style="background:#0f172a; color:#ffffff; padding:0.6rem 1.15rem; border-radius:8px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:0.45rem;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                <span>View Live Site &rarr;</span>
            </a>
        </div>
    </div>

    <!-- AJAX Toast Feedback -->
    <div id="visibilityToast" style="display:none; margin-bottom:1.25rem; background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; padding:0.85rem 1.25rem; border-radius:8px; font-size:0.9rem; font-weight:600; animation:fadeIn 0.3s ease;">
    </div>

    <!-- Section Control Table / Card -->
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden;">
        <div style="padding:1.25rem 1.5rem; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <strong style="color:#0f172a; font-size:1rem;">Frontend Sections Control</strong>
                <span style="display:block; font-size:0.8rem; color:#64748b; margin-top:0.15rem;">
                    Enabled sections are visible to all visitors. Disabled sections are completely hidden without leaving empty space.
                </span>
            </div>
            <span style="font-size:0.8rem; font-weight:700; color:#059669; background:#ecfdf5; border:1px solid #a7f3d0; padding:0.25rem 0.65rem; border-radius:9999px;">
                Total: <?= count($sections) ?> Sections
            </span>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.9rem; text-align:left;">
                <thead>
                    <tr style="border-bottom:1px solid #e2e8f0; color:#475569; font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.04em;">
                        <th style="padding:1rem 1.5rem; width:45%;">Section</th>
                        <th style="padding:1rem 1.5rem; width:25%;">Location</th>
                        <th style="padding:1rem 1.5rem; width:15%;">Status</th>
                        <th style="padding:1rem 1.5rem; width:15%; text-align:right;">Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $sec): ?>
                        <?php 
                        $isEnabled = ((int)$sec['is_enabled'] === 1);
                        ?>
                        <tr style="border-bottom:1px solid #f1f5f9; transition:background 0.15s ease;" id="row-<?= h($sec['section_key']) ?>">
                            <!-- Section Name & Description -->
                            <td style="padding:1.15rem 1.5rem;">
                                <div style="display:flex; align-items:center; gap:0.75rem;">
                                    <div style="width:36px; height:36px; border-radius:8px; background:<?= $isEnabled ? '#ecfdf5' : '#f1f5f9' ?>; color:<?= $isEnabled ? '#059669' : '#94a3b8' ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1.1rem;" id="icon-<?= h($sec['section_key']) ?>">
                                        <?php if ($sec['section_key'] === 'header'): ?>
                                            🌐
                                        <?php elseif ($sec['section_key'] === 'hero_featured'): ?>
                                            ⭐
                                        <?php elseif ($sec['section_key'] === 'live_job_radar'): ?>
                                            📡
                                        <?php elseif ($sec['section_key'] === 'top_categories'): ?>
                                            📂
                                        <?php elseif ($sec['section_key'] === 'trending_guides'): ?>
                                            🔥
                                        <?php elseif ($sec['section_key'] === 'jobs_section'): ?>
                                            💼
                                        <?php elseif ($sec['section_key'] === 'career_toolkit'): ?>
                                            🧰
                                        <?php elseif ($sec['section_key'] === 'latest_articles'): ?>
                                            📰
                                        <?php elseif ($sec['section_key'] === 'footer'): ?>
                                            🔻
                                        <?php else: ?>
                                            📄
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong style="color:#0f172a; font-size:0.95rem; display:block;">
                                            <?= h($sec['section_name']) ?>
                                        </strong>
                                        <span style="font-size:0.775rem; color:#64748b; font-family:monospace;">
                                            Key: <?= h($sec['section_key']) ?>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Location -->
                            <td style="padding:1.15rem 1.5rem; color:#475569; font-size:0.85rem;">
                                <?= h($sec['section_location']) ?>
                            </td>

                            <!-- Status Badge -->
                            <td style="padding:1.15rem 1.5rem;">
                                <span id="badge-<?= h($sec['section_key']) ?>" style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.25rem 0.65rem; border-radius:9999px; font-size:0.75rem; font-weight:700; <?= $isEnabled ? 'background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;' : 'background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1;' ?>">
                                    <span style="width:7px; height:7px; border-radius:50%; background:<?= $isEnabled ? '#10b981' : '#94a3b8' ?>; display:inline-block;" id="dot-<?= h($sec['section_key']) ?>"></span>
                                    <span id="text-<?= h($sec['section_key']) ?>"><?= $isEnabled ? 'ENABLED' : 'DISABLED' ?></span>
                                </span>
                            </td>

                            <!-- Toggle Button Form -->
                            <td style="padding:1.15rem 1.5rem; text-align:right;">
                                <form method="POST" action="<?= ADMIN_URL ?>/section-visibility.php" class="section-toggle-form" data-key="<?= h($sec['section_key']) ?>" style="display:inline-block;">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <input type="hidden" name="section_key" value="<?= h($sec['section_key']) ?>">
                                    <input type="hidden" name="action" value="toggle">

                                    <button type="submit" 
                                            class="toggle-switch-btn" 
                                            id="btn-<?= h($sec['section_key']) ?>"
                                            data-key="<?= h($sec['section_key']) ?>"
                                            title="Click to <?= $isEnabled ? 'Disable' : 'Enable' ?>"
                                            style="cursor:pointer; display:inline-flex; align-items:center; gap:0.5rem; padding:0.45rem 0.95rem; border-radius:8px; font-weight:700; font-size:0.825rem; border:1px solid; transition:all 0.2s ease; <?= $isEnabled ? 'background:#ecfdf5; color:#059669; border-color:#a7f3d0;' : 'background:#f8fafc; color:#64748b; border-color:#cbd5e1;' ?>">
                                        <span class="toggle-state-label" id="btnlabel-<?= h($sec['section_key']) ?>">
                                            <?= $isEnabled ? '[ ON ]' : '[ OFF ]' ?>
                                        </span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// AJAX Fast Toggle with Fallback to Standard Form POST
document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('.section-toggle-form');
    const toast = document.getElementById('visibilityToast');

    forms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const secKey = form.getAttribute('data-key');
            const csrf = form.querySelector('input[name="csrf_token"]').value;
            const btn = document.getElementById('btn-' + secKey);
            const btnLabel = document.getElementById('btnlabel-' + secKey);
            const badge = document.getElementById('badge-' + secKey);
            const dot = document.getElementById('dot-' + secKey);
            const text = document.getElementById('text-' + secKey);
            const icon = document.getElementById('icon-' + secKey);

            if (btn) btn.disabled = true;

            try {
                const response = await fetch('<?= ADMIN_URL ?>/section-visibility.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        section_key: secKey,
                        action: 'toggle',
                        csrf_token: csrf
                    })
                });

                const data = await response.json();

                if (data && data.success) {
                    const isNowEnabled = (data.is_enabled === 1);

                    // Update button styling
                    if (btn) {
                        btn.style.background = isNowEnabled ? '#ecfdf5' : '#f8fafc';
                        btn.style.color = isNowEnabled ? '#059669' : '#64748b';
                        btn.style.borderColor = isNowEnabled ? '#a7f3d0' : '#cbd5e1';
                        btn.title = 'Click to ' + (isNowEnabled ? 'Disable' : 'Enable');
                    }
                    if (btnLabel) {
                        btnLabel.textContent = isNowEnabled ? '[ ON ]' : '[ OFF ]';
                    }

                    // Update badge styling
                    if (badge) {
                        badge.style.background = isNowEnabled ? '#ecfdf5' : '#f1f5f9';
                        badge.style.color = isNowEnabled ? '#059669' : '#64748b';
                        badge.style.borderColor = isNowEnabled ? '#a7f3d0' : '#cbd5e1';
                    }
                    if (dot) {
                        dot.style.background = isNowEnabled ? '#10b981' : '#94a3b8';
                    }
                    if (text) {
                        text.textContent = isNowEnabled ? 'ENABLED' : 'DISABLED';
                    }
                    if (icon) {
                        icon.style.background = isNowEnabled ? '#ecfdf5' : '#f1f5f9';
                        icon.style.color = isNowEnabled ? '#059669' : '#94a3b8';
                    }

                    // Show success toast
                    if (toast) {
                        toast.textContent = data.message;
                        toast.style.display = 'block';
                        toast.style.background = isNowEnabled ? '#ecfdf5' : '#fef2f2';
                        toast.style.color = isNowEnabled ? '#065f46' : '#991b1b';
                        toast.style.borderColor = isNowEnabled ? '#a7f3d0' : '#fecaca';

                        setTimeout(() => {
                            toast.style.display = 'none';
                        }, 3500);
                    }
                } else {
                    alert(data.message || 'Failed to update section visibility.');
                }
            } catch (err) {
                console.error(err);
                // Fallback to normal form submit on network issue
                form.submit();
            } finally {
                if (btn) btn.disabled = false;
            }
        });
    });
});
</script>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
