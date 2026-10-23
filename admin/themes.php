<?php
/**
 * Admin Panel - Frontend Theme Switcher
 * CareerNexaa Multi-Theme Management System
 * Allows administrators to preview and activate frontend templates:
 * 1. Classic Nexaa (Current Default)
 * 2. Tech Magazine (The Verge / TechCrunch Style)
 * 3. Clean Minimalist (Medium / Substack Style)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/includes/auth-check.php';

// Authorization check: Requires Super Admin or settings permission
if (!is_super_admin() && !has_permission('settings.view')) {
    require_permission('settings.view');
}

$db = getDBConnection();

// Handle Theme Activation POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['activate_theme'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('error', 'Security session expired. Please refresh the page.');
        header('Location: ' . ADMIN_URL . '/themes.php');
        exit;
    }

    $targetTheme = trim($_POST['theme_id'] ?? '');
    $themes = get_available_themes();

    if (isset($themes[$targetTheme])) {
        if (set_active_theme($targetTheme)) {
            log_activity('update', 'themes', "Activated frontend theme: '{$themes[$targetTheme]['name']}'");
            set_flash('success', "Theme activated! <strong>{$themes[$targetTheme]['name']}</strong> is now the live default template for all visitors.");
        } else {
            set_flash('error', "Failed to save theme settings to database. Please check database permissions.");
        }
    } else {
        set_flash('error', "Selected theme '{$targetTheme}' does not exist.");
    }

    header('Location: ' . ADMIN_URL . '/themes.php');
    exit;
}

// Fetch active theme and theme directory metadata
$activeThemeId = get_active_theme();
$availableThemes = get_available_themes();

$adminPageTitle = "Frontend Themes";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-content-inner" style="max-width:1400px; margin:0 auto; padding-bottom:3rem;">
    <!-- Top Header Breadcrumb & Actions -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
        <div>
            <div style="font-size:0.85rem; color:#64748b; margin-bottom:0.35rem; display:flex; align-items:center; gap:0.4rem;">
                <a href="<?= ADMIN_URL ?>/index.php" style="color:#64748b; text-decoration:none;">Dashboard</a>
                <span>&rsaquo;</span>
                <span style="color:#0f172a; font-weight:700;">Frontend Themes</span>
            </div>
            <h1 style="font-size:1.75rem; font-weight:800; color:#0f172a; margin:0; letter-spacing:-0.02em;">
                Frontend Themes
            </h1>
        </div>

        <div style="display:flex; gap:0.75rem; align-items:center;">
            <a href="<?= BASE_URL ?>/" target="_blank" class="admin-btn" style="background:#ffffff; border:1px solid #cbd5e1; color:#334155; padding:0.6rem 1.15rem; border-radius:8px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem; box-shadow:0 1px 2px rgba(0,0,0,0.04);">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                <span>View Live Site</span>
            </a>
        </div>
    </div>

    <!-- Flash message alerts -->
    <?php if (!empty($_SESSION['flash_messages'])): ?>
        <div style="margin-bottom:1.5rem;">
            <?= display_flash_messages() ?>
        </div>
    <?php endif; ?>

    <!-- Info banner explaining Safe Multi-Theme Switcher -->
    <div style="background:linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%); border:1px solid #bfdbfe; border-radius:12px; padding:1.25rem 1.5rem; margin-bottom:2rem; display:flex; gap:1rem; align-items:flex-start;">
        <div style="background:#3b82f6; color:#ffffff; width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1.1rem;">
            🎨
        </div>
        <div>
            <h3 style="font-size:1rem; font-weight:800; color:#1e3a8a; margin:0 0 0.35rem 0;">1-Click Instant Theme Switcher</h3>
            <p style="font-size:0.875rem; color:#334155; margin:0; line-height:1.55;">
                Switch between full frontend layouts without losing any data. All published articles, verified job postings, categories, monetization slots, and SEO metadata work seamlessly across all 3 themes. You can also use <strong>"Live Preview"</strong> to test a theme privately without changing the live experience for regular visitors.
            </p>
        </div>
    </div>

    <!-- Themes Grid (3 Cards) -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(360px, 1fr)); gap:2rem; align-items:stretch;">
        <?php foreach ($availableThemes as $themeId => $tData): ?>
            <?php 
                $isActive = ($themeId === $activeThemeId);
                $previewUrl = BASE_URL . '/?preview_theme=' . urlencode($themeId);
                $screenshotPath = __DIR__ . '/../themes/' . $themeId . '/screenshot.svg';
                $screenshotUrl = file_exists($screenshotPath) 
                    ? BASE_URL . '/themes/' . $themeId . '/screenshot.svg' 
                    : BASE_URL . '/assets/images/default-article.jpg';
            ?>
            <div style="background:#ffffff; border:2px solid <?= $isActive ? '#2563eb' : '#e2e8f0' ?>; border-radius:14px; overflow:hidden; box-shadow:<?= $isActive ? '0 10px 25px -5px rgba(37,99,235,0.15)' : '0 2px 8px rgba(0,0,0,0.04)' ?>; display:flex; flex-direction:column; position:relative; transition:all 0.2s ease;">
                
                <!-- Active Indicator Ribbon -->
                <?php if ($isActive): ?>
                    <div style="position:absolute; top:1rem; right:1rem; z-index:2; background:#2563eb; color:#ffffff; font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; padding:0.35rem 0.85rem; border-radius:999px; box-shadow:0 2px 8px rgba(37,99,235,0.4); display:flex; align-items:center; gap:0.4rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Active Theme</span>
                    </div>
                <?php endif; ?>

                <!-- Theme Visual Thumbnail -->
                <div style="width:100%; aspect-ratio:16/10; background:#0f172a; position:relative; overflow:hidden; border-bottom:1px solid #e2e8f0;">
                    <img src="<?= $screenshotUrl ?>" alt="<?= h($tData['name']) ?>" style="width:100%; height:100%; object-fit:cover; display:block;">
                </div>

                <!-- Card Body -->
                <div style="padding:1.5rem; display:flex; flex-direction:column; flex:1;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                        <h2 style="font-size:1.25rem; font-weight:800; color:#0f172a; margin:0;">
                            <?= h($tData['name']) ?>
                        </h2>
                        <span style="font-size:0.75rem; background:#f1f5f9; color:#64748b; padding:0.2rem 0.5rem; border-radius:6px; font-weight:600;">
                            v<?= h($tData['version'] ?? '1.0.0') ?>
                        </span>
                    </div>

                    <p style="font-size:0.875rem; color:#64748b; line-height:1.55; margin-bottom:1.25rem; flex:1;">
                        <?= h($tData['description']) ?>
                    </p>

                    <!-- Feature Tags -->
                    <div style="display:flex; flex-wrap:wrap; gap:0.4rem; margin-bottom:1.5rem;">
                        <?php if (!empty($tData['features']) && is_array($tData['features'])): ?>
                            <?php foreach ($tData['features'] as $fTag): ?>
                                <span style="font-size:0.725rem; background:#f8fafc; color:#475569; border:1px solid #e2e8f0; padding:0.2rem 0.55rem; border-radius:6px; font-weight:500;">
                                    ✓ <?= h($fTag) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Theme Aesthetic Badges (Color Palette + Typography) -->
                    <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.6rem 0.85rem; margin-bottom:1.25rem;">
                        <div style="display:flex; align-items:center; gap:0.4rem;">
                            <span style="font-size:0.75rem; color:#64748b; font-weight:700;">Palette:</span>
                            <?php if ($themeId === 'classic'): ?>
                                <span style="width:14px; height:14px; border-radius:50%; background:#059669; display:inline-block; border:1px solid rgba(0,0,0,0.1);" title="Emerald #059669"></span>
                                <span style="width:14px; height:14px; border-radius:50%; background:#0f172a; display:inline-block; border:1px solid rgba(0,0,0,0.1);" title="Slate #0f172a"></span>
                                <span style="width:14px; height:14px; border-radius:50%; background:#f8fafc; display:inline-block; border:1px solid rgba(0,0,0,0.1);" title="Light #f8fafc"></span>
                            <?php elseif ($themeId === 'magazine'): ?>
                                <span style="width:14px; height:14px; border-radius:50%; background:#2563eb; display:inline-block; border:1px solid rgba(0,0,0,0.1);" title="Electric Blue #2563eb"></span>
                                <span style="width:14px; height:14px; border-radius:50%; background:#06b6d4; display:inline-block; border:1px solid rgba(0,0,0,0.1);" title="Cyan #06b6d4"></span>
                                <span style="width:14px; height:14px; border-radius:50%; background:#0f172a; display:inline-block; border:1px solid rgba(0,0,0,0.1);" title="Midnight #0f172a"></span>
                            <?php else: ?>
                                <span style="width:14px; height:14px; border-radius:50%; background:#111111; display:inline-block; border:1px solid rgba(0,0,0,0.1);" title="Charcoal #111111"></span>
                                <span style="width:14px; height:14px; border-radius:50%; background:#1a8917; display:inline-block; border:1px solid rgba(0,0,0,0.1);" title="Forest #1a8917"></span>
                                <span style="width:14px; height:14px; border-radius:50%; background:#ffffff; display:inline-block; border:1px solid rgba(0,0,0,0.1);" title="Pure Paper #ffffff"></span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:0.75rem; color:#475569; font-weight:600;">
                            <?= ($themeId === 'minimalist') ? '📖 Serif Editorial' : (($themeId === 'magazine') ? '⚡ Modern Tech' : '💼 Corporate Portal') ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display:flex; flex-direction:column; gap:0.6rem; padding-top:1rem; border-top:1px solid #f1f5f9;">
                        <div style="display:flex; gap:0.75rem; align-items:center;">
                            <?php if ($isActive): ?>
                                <a href="<?= BASE_URL ?>/" target="_blank" style="flex:1; text-align:center; background:#f1f5f9; color:#0f172a; font-weight:700; padding:0.65rem 1rem; border-radius:8px; text-decoration:none; font-size:0.875rem; display:inline-flex; align-items:center; justify-content:center; gap:0.4rem;">
                                    <span>Preview Live</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                </a>
                                <span style="font-size:0.8rem; color:#16a34a; font-weight:700; display:flex; align-items:center; gap:0.3rem;">
                                    ● Live Default
                                </span>
                            <?php else: ?>
                                <form action="" method="POST" style="flex:1; margin:0;">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="activate_theme" value="1">
                                    <input type="hidden" name="theme_id" value="<?= h($themeId) ?>">
                                    <button type="submit" style="width:100%; background:#2563eb; color:#ffffff; font-weight:700; border:none; padding:0.65rem 1rem; border-radius:8px; cursor:pointer; font-size:0.875rem; transition:background 0.15s ease;">
                                        Activate Theme
                                    </button>
                                </form>

                                <a href="<?= $previewUrl ?>" target="_blank" style="background:#ffffff; border:1px solid #cbd5e1; color:#334155; font-weight:700; padding:0.65rem 1rem; border-radius:8px; text-decoration:none; font-size:0.875rem; display:inline-flex; align-items:center; gap:0.4rem; white-space:nowrap;">
                                    <span>Live Preview</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Quick Page Test Links -->
                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:#64748b; padding-top:0.35rem;">
                            <span>Preview Inner Pages:</span>
                            <div style="display:flex; gap:0.5rem;">
                                <a href="<?= BASE_URL ?>/articles?preview_theme=<?= urlencode($themeId) ?>" target="_blank" style="color:#2563eb; text-decoration:none; font-weight:600;">Articles &rarr;</a>
                                <span>&bull;</span>
                                <a href="<?= BASE_URL ?>/categories?preview_theme=<?= urlencode($themeId) ?>" target="_blank" style="color:#2563eb; text-decoration:none; font-weight:600;">Categories &rarr;</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Instructions & Architecture FAQ -->
    <div style="margin-top:3rem; background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:2rem;">
        <h2 style="font-size:1.15rem; font-weight:800; color:#0f172a; margin:0 0 1rem 0;">
            Theme Switcher Notes & Architecture
        </h2>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.75rem; font-size:0.875rem; color:#475569; line-height:1.6;">
            <div>
                <strong style="color:#0f172a; display:block; margin-bottom:0.35rem;">Zero Data Loss Guarantee</strong>
                Activating a new theme does not modify or delete any database rows. Your articles, comments, analytics, categories, and settings remain 100% intact.
            </div>
            <div>
                <strong style="color:#0f172a; display:block; margin-bottom:0.35rem;">Live Preview Mode</strong>
                Clicking "Live Preview" allows you to browse the entire site using that theme in a private test session. Other visitors continue seeing the live active theme.
            </div>
            <div>
                <strong style="color:#0f172a; display:block; margin-bottom:0.35rem;">AdSense & Monetization Ready</strong>
                All 3 templates have built-in hooks for Google AdSense header scripts, in-article banners, and responsive below-content ad units.
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin-footer.php';
