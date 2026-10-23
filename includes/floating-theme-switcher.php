<?php
/**
 * Frontend Floating Theme Switcher Widget
 * Allows instant 1-click preview switching between all 3 themes directly on any public page.
 */

$currentTheme = function_exists('get_active_theme') ? get_active_theme() : 'classic';

// Build current URL preserving existing query params except preview_theme
$currentUrlParams = $_GET;
unset($currentUrlParams['preview_theme']);
$baseCurrentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

function build_theme_switch_url($themeId, $currentParams, $basePath) {
    $p = $currentParams;
    $p['preview_theme'] = $themeId;
    return $basePath . '?' . http_build_query($p);
}
?>

<div id="quickThemeSwitcher" class="floating-theme-switcher" aria-label="Theme Preview Switcher">
    <!-- Collapsed Trigger Button -->
    <button type="button" id="themeSwitchToggle" class="theme-switch-toggle" onclick="toggleQuickThemeSwitcher()" title="Change Website Theme" aria-expanded="true">
        <span class="theme-switch-icon">🎨</span>
        <span class="theme-switch-label">Themes</span>
    </button>

    <!-- Expanded Switcher Bar -->
    <div class="theme-switch-panel" id="themeSwitchPanel">
        <div class="theme-switch-title">
            <span>PREVIEW THEMES</span>
            <button type="button" class="theme-switch-close" onclick="toggleQuickThemeSwitcher()" title="Minimize" aria-label="Minimize">&times;</button>
        </div>

        <div class="theme-switch-options">
            <!-- 1. Classic Nexaa -->
            <a href="<?= build_theme_switch_url('classic', $currentUrlParams, $baseCurrentPath) ?>" 
               class="theme-pill <?= ($currentTheme === 'classic') ? 'active' : '' ?>">
                <span class="theme-dot dot-classic"></span>
                <span>Classic</span>
            </a>

            <!-- 2. Tech Magazine -->
            <a href="<?= build_theme_switch_url('magazine', $currentUrlParams, $baseCurrentPath) ?>" 
               class="theme-pill <?= ($currentTheme === 'magazine') ? 'active' : '' ?>">
                <span class="theme-dot dot-magazine"></span>
                <span>Magazine</span>
            </a>

            <!-- 3. Clean Minimalist -->
            <a href="<?= build_theme_switch_url('minimalist', $currentUrlParams, $baseCurrentPath) ?>" 
               class="theme-pill <?= ($currentTheme === 'minimalist') ? 'active' : '' ?>">
                <span class="theme-dot dot-minimalist"></span>
                <span>Minimalist</span>
            </a>
        </div>

        <div class="theme-switch-footer">
            <a href="<?= ADMIN_URL ?>/themes.php" class="theme-admin-link">⚙️ Manage in Admin</a>
            <?php if (!empty($_SESSION['site_preview_theme'])): ?>
                <a href="<?= build_theme_switch_url('reset', $currentUrlParams, $baseCurrentPath) ?>" class="theme-reset-link">Exit Preview</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* ==========================================================================
   Floating Theme Switcher Widget Styling (Universal Glassmorphism)
   ========================================================================== */
.floating-theme-switcher {
    position: fixed;
    bottom: 1.5rem;
    left: 1.5rem;
    z-index: 99999;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.theme-switch-toggle {
    display: none;
    align-items: center;
    gap: 0.4rem;
    background: #0f172a;
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 999px;
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
    transition: all 0.2s ease;
}
.theme-switch-toggle:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.5);
}
.theme-switch-panel {
    background: rgba(15, 23, 42, 0.94);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 14px;
    padding: 0.85rem;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5);
    color: #ffffff;
    width: 290px;
    animation: fadeInSwitcher 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes fadeInSwitcher {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.theme-switch-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    color: #94a3b8;
    margin-bottom: 0.65rem;
    padding-bottom: 0.4rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.theme-switch-close {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.25rem;
    cursor: pointer;
    line-height: 1;
    padding: 0;
}
.theme-switch-close:hover {
    color: #ffffff;
}
.theme-switch-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.4rem;
    margin-bottom: 0.65rem;
}
.theme-pill {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    padding: 0.5rem 0.25rem;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.06);
    color: #cbd5e1;
    text-decoration: none;
    font-size: 0.775rem;
    font-weight: 600;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}
.theme-pill:hover {
    background: rgba(255, 255, 255, 0.14);
    color: #ffffff;
}
.theme-pill.active {
    background: #2563eb;
    color: #ffffff;
    font-weight: 800;
    border-color: #60a5fa;
    box-shadow: 0 0 12px rgba(37, 99, 235, 0.5);
}
.theme-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}
.dot-classic { background: #10b981; }
.dot-magazine { background: #38bdf8; }
.dot-minimalist { background: #ffffff; }

.theme-switch-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.725rem;
    padding-top: 0.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}
.theme-admin-link {
    color: #38bdf8;
    text-decoration: none;
    font-weight: 600;
}
.theme-admin-link:hover {
    text-decoration: underline;
}
.theme-reset-link {
    color: #ef4444;
    text-decoration: none;
    font-weight: 600;
}
.theme-reset-link:hover {
    text-decoration: underline;
}

/* Collapsed state */
.floating-theme-switcher.collapsed .theme-switch-panel {
    display: none;
}
.floating-theme-switcher.collapsed .theme-switch-toggle {
    display: inline-flex;
}
</style>

<script>
function toggleQuickThemeSwitcher() {
    var switcher = document.getElementById('quickThemeSwitcher');
    if (!switcher) return;
    switcher.classList.toggle('collapsed');
    var isCollapsed = switcher.classList.contains('collapsed');
    try {
        localStorage.setItem('site_theme_switcher_collapsed', isCollapsed ? '1' : '0');
    } catch(e) {}
}

// Restore saved collapsed preference
(function() {
    try {
        if (localStorage.getItem('site_theme_switcher_collapsed') === '1') {
            var switcher = document.getElementById('quickThemeSwitcher');
            if (switcher) switcher.classList.add('collapsed');
        }
    } catch(e) {}
})();
</script>
