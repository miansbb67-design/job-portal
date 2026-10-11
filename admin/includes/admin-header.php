<?php
/**
 * Modern SaaS Admin Header & Navigation
 * Pixel-perfect implementation matching PineCastTV executive admin layout
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/auth-check.php';

// Enforce noindex for all admin views
if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow');
}

$currentAdminScript = basename($_SERVER['PHP_SELF']);
$adminSiteName = get_site_name();
$adminSiteLogoUrl = get_site_logo_url();
$adminSiteFaviconUrl = get_site_favicon_url();
$adminTitle = isset($adminPageTitle) ? h($adminPageTitle) . ' | Admin - ' . $adminSiteName : 'Admin Dashboard | ' . $adminSiteName;

// User details & initials
$isSuperAdmin = is_super_admin();
if ($isSuperAdmin) {
    $displayNameClean = 'Super Admin';
    $userInitials = 'SA';
} else {
    $rawName = $currentUser['name'] ?? ($currentUser['username'] ?? 'Admin');
    $parts = explode(' ', trim($rawName));
    $displayNameClean = $parts[0];
    $userInitials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    if (empty($userInitials)) $userInitials = 'AD';
}

$currentAdminDir = basename(dirname($_SERVER['PHP_SELF']));

// Granular permission checks for navigation visibility
$canDashboard  = $isSuperAdmin || has_permission('dashboard.view');
$canJobs       = $isSuperAdmin || has_permission('jobs.view');
$canPosts      = $isSuperAdmin || has_permission('posts.view');
$canCategories = $isSuperAdmin || has_permission('categories.view');
$canPages      = $isSuperAdmin || has_permission('pages.view');
$canMedia      = $isSuperAdmin || has_permission('media.view');
$canUsers      = $isSuperAdmin || has_permission('users.view');
$canSettings   = $isSuperAdmin || has_permission('settings.view');
$canLogs       = $isSuperAdmin || has_permission('logs.view');

// Dynamic counts
$navDb = getDBConnection();
$badgeJobs = (int)$navDb->query("SELECT COUNT(*) FROM articles WHERE is_job_posting = 1 AND is_trash = 0")->fetchColumn();
$badgePosts = (int)$navDb->query("SELECT COUNT(*) FROM articles WHERE (is_job_posting = 0 OR is_job_posting IS NULL) AND is_trash = 0")->fetchColumn();
$badgeAdmins = (int)$navDb->query("SELECT COUNT(*) FROM admins")->fetchColumn();
$badgeComments = (int)$navDb->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();

// Notification System Data
$notificationsList = [];
$unreadNotifCount = 0;
$hasReadState = isset($_SESSION['admin_last_read_notif_time']);
$lastReadNotifTime = (int)($_SESSION['admin_last_read_notif_time'] ?? 0);

try {
    $notifStmt = $navDb->query("
        SELECT id, admin_id, admin_name, action, module, description, created_at 
        FROM activity_logs 
        ORDER BY id DESC 
        LIMIT 8
    ");
    $notificationsList = $notifStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$hasReadState) {
        $unreadNotifCount = min(count($notificationsList), 3);
    } else {
        foreach ($notificationsList as $n) {
            if (strtotime($n['created_at']) > $lastReadNotifTime) {
                $unreadNotifCount++;
            }
        }
    }
} catch (Exception $e) {
    $notificationsList = [];
}

// Is Content, Analytics, and SEO menus active?
$isContentActive = in_array($currentAdminScript, ['articles.php', 'add-article.php', 'edit-article.php', 'categories.php', 'tags.php', 'comments.php']) || in_array($currentAdminDir, ['posts', 'jobs', 'media', 'categories']);
$isAnalyticsActive = ($currentAdminScript === 'analytics.php' || $currentAdminDir === 'analytics');
$isSeoActive = ($currentAdminScript === 'seo.php' || $currentAdminDir === 'seo');
$currentAdminTab = $_GET['tab'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $adminTitle ?></title>
    <meta name="robots" content="noindex, nofollow">

    <!-- Admin Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/favicon.svg">
    <link rel="alternate icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.ico">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/favicon.svg">

    <!-- Modern Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Admin Stylesheet (with auto cache-buster) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css?v=<?= filemtime(__DIR__ . '/../../assets/css/admin.css') ?>">

    <!-- Dropify File Uploader Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/dropify/dropify.min.css">

    <!-- Summernote WYSIWYG Editor Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/summernote/summernote-lite.min.css">
    <script>window.ADMIN_URL = '<?= ADMIN_URL ?>';</script>
</head>
<body class="admin-body">

    <!-- Mobile Drawer Backdrop Overlay -->
    <div class="admin-sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebarDrawer()"></div>

    <!-- Admin Sidebar (Screenshot 2: Dark Midnight Navy #090e1a) -->
    <aside class="admin-sidebar" id="adminSidebar">
        <!-- Sidebar Brand / Logo Header -->
        <div class="admin-sidebar-brand">
            <a href="<?= ADMIN_URL ?>/index.php" class="admin-brand-link" style="display:flex; align-items:center; gap:0.75rem; text-decoration:none; overflow:hidden;">
                <?php if (!empty($adminSiteLogoUrl)): ?>
                    <img src="<?= h($adminSiteLogoUrl) ?>" alt="<?= h($adminSiteName) ?>" style="max-height:36px; max-width:140px; object-fit:contain; display:block;">
                <?php else: ?>
                    <div class="admin-brand-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                    </div>
                    <div class="admin-brand-text">
                        <span class="admin-brand-title"><?= h($adminSiteName) ?></span>
                    </div>
                <?php endif; ?>
            </a>
            <!-- Mobile Close Button -->
            <button type="button" class="admin-sidebar-close" onclick="toggleSidebarDrawer()" aria-label="Close sidebar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <!-- Sidebar Navigation (Exact Screenshot 2 structure) -->
        <nav class="admin-nav">
            <ul class="admin-nav-list">
                <!-- 1. Dashboard -->
                <li>
                    <a href="<?= ADMIN_URL ?>/index.php" class="admin-nav-item <?= ($currentAdminScript === 'index.php' && $currentAdminDir === 'admin') ? 'active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </li>

                <!-- 2. Content (Accordion with dropdown) -->
                <li class="admin-nav-group <?= $isContentActive ? 'open' : '' ?>" id="groupContent">
                    <button type="button" class="admin-nav-item group-toggle" onclick="toggleNavGroup(this)">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                        <span class="nav-label">Content</span>
                        <svg class="chevron-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <ul class="admin-submenu">
                        <li>
                            <a href="<?= ADMIN_URL ?>/articles.php?video=1" class="admin-submenu-item <?= (isset($_GET['video']) && $_GET['video'] == '1') ? 'active' : '' ?>">
                                Vlogs
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/articles.php" class="admin-submenu-item <?= (($currentAdminScript === 'articles.php' && !isset($_GET['video'])) || in_array($currentAdminScript, ['add-article.php', 'edit-article.php'])) ? 'active' : '' ?>">
                                Articles
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/categories.php" class="admin-submenu-item <?= ($currentAdminScript === 'categories.php') ? 'active' : '' ?>">
                                Categories
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/tags.php" class="admin-submenu-item <?= ($currentAdminScript === 'tags.php') ? 'active' : '' ?>">
                                Tags
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/media/index.php" class="admin-submenu-item <?= ($currentAdminDir === 'media') ? 'active' : '' ?>">
                                Media Library
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/comments.php" class="admin-submenu-item <?= ($currentAdminScript === 'comments.php') ? 'active' : '' ?>" style="display:flex; justify-content:space-between; align-items:center;">
                                <span>Comments</span>
                                <?php if ($badgeComments > 0): ?>
                                    <span style="background:#f59e0b; color:#fff; font-size:0.7rem; font-weight:700; padding:0.1rem 0.45rem; border-radius:10px;"><?= $badgeComments ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php if ($canJobs): ?>
                        <li>
                            <a href="<?= ADMIN_URL ?>/jobs/index.php" class="admin-submenu-item <?= ($currentAdminDir === 'jobs' || (isset($_GET['type']) && $_GET['type'] === 'job')) ? 'active' : '' ?>">
                                Jobs & Vacancies
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- 3. Analytics (Accordion matching Screenshot 2) -->
                <li class="admin-nav-group <?= $isAnalyticsActive ? 'open' : '' ?>" id="groupAnalytics">
                    <button type="button" class="admin-nav-item group-toggle" onclick="toggleNavGroup(this)">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                        <span class="nav-label">Analytics</span>
                        <svg class="chevron-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <ul class="admin-submenu">
                        <li>
                            <a href="<?= ADMIN_URL ?>/analytics.php?tab=overview" class="admin-submenu-item <?= ($isAnalyticsActive && ($currentAdminTab === 'overview' || empty($currentAdminTab))) ? 'active' : '' ?>">
                                Overview
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/analytics.php?tab=realtime" class="admin-submenu-item <?= ($isAnalyticsActive && $currentAdminTab === 'realtime') ? 'active' : '' ?>">
                                Real-Time
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/analytics.php?tab=traffic" class="admin-submenu-item <?= ($isAnalyticsActive && $currentAdminTab === 'traffic') ? 'active' : '' ?>">
                                Traffic
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/analytics.php?tab=content" class="admin-submenu-item <?= ($isAnalyticsActive && $currentAdminTab === 'content') ? 'active' : '' ?>">
                                Content
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/analytics.php?tab=videos" class="admin-submenu-item <?= ($isAnalyticsActive && $currentAdminTab === 'videos') ? 'active' : '' ?>">
                                Video Analytics
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/analytics.php?tab=audience" class="admin-submenu-item <?= ($isAnalyticsActive && $currentAdminTab === 'audience') ? 'active' : '' ?>">
                                Audience
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/analytics.php?tab=sources" class="admin-submenu-item <?= ($isAnalyticsActive && $currentAdminTab === 'sources') ? 'active' : '' ?>">
                                Sources
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/analytics.php?tab=search" class="admin-submenu-item <?= ($isAnalyticsActive && $currentAdminTab === 'search') ? 'active' : '' ?>">
                                Site Search
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/analytics.php?tab=reports" class="admin-submenu-item <?= ($isAnalyticsActive && $currentAdminTab === 'reports') ? 'active' : '' ?>">
                                Reports &amp; Export
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 4. SEO (Accordion matching Screenshot 1) -->
                <li class="admin-nav-group <?= $isSeoActive ? 'open' : '' ?>" id="groupSeo">
                    <button type="button" class="admin-nav-item group-toggle" onclick="toggleNavGroup(this)">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <span class="nav-label">SEO</span>
                        <svg class="chevron-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <ul class="admin-submenu">
                        <li>
                            <a href="<?= ADMIN_URL ?>/seo.php?tab=overview" class="admin-submenu-item <?= ($isSeoActive && ($currentAdminTab === 'overview' || empty($currentAdminTab))) ? 'active' : '' ?>">
                                SEO Overview
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/seo.php?tab=search-console" class="admin-submenu-item <?= ($isSeoActive && $currentAdminTab === 'search-console') ? 'active' : '' ?>">
                                Search Console
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/seo.php?tab=sitemap" class="admin-submenu-item <?= ($isSeoActive && $currentAdminTab === 'sitemap') ? 'active' : '' ?>">
                                Sitemap
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/seo.php?tab=redirects" class="admin-submenu-item <?= ($isSeoActive && $currentAdminTab === 'redirects') ? 'active' : '' ?>">
                                Redirects
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/seo.php?tab=broken-links" class="admin-submenu-item <?= ($isSeoActive && $currentAdminTab === 'broken-links') ? 'active' : '' ?>">
                                Broken Links
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 5. Pages & Menus -->
                <?php if ($canPages): ?>
                <li class="admin-nav-group <?= ($currentAdminDir === 'pages' || $currentAdminScript === 'section-visibility.php') ? 'open' : '' ?>" id="groupPages">
                    <button type="button" class="admin-nav-item group-toggle" onclick="toggleNavGroup(this)">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                        <span class="nav-label">Pages</span>
                        <svg class="chevron-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <ul class="admin-submenu">
                        <li>
                            <a href="<?= ADMIN_URL ?>/pages/index.php" class="admin-submenu-item <?= ($currentAdminDir === 'pages') ? 'active' : '' ?>">
                                All Pages
                            </a>
                        </li>
                        <li>
                            <a href="<?= ADMIN_URL ?>/section-visibility.php" class="admin-submenu-item <?= ($currentAdminScript === 'section-visibility.php') ? 'active' : '' ?>">
                                Home Sections &amp; Menus
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- Administration items (if superadmin) -->
                <?php if ($isSuperAdmin): ?>
                <li>
                    <a href="<?= ADMIN_URL ?>/admins/index.php" class="admin-nav-item <?= ($currentAdminDir === 'admins') ? 'active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span class="nav-label">Users / Admins</span>
                    </a>
                </li>
                <li>
                    <a href="<?= ADMIN_URL ?>/settings/index.php" class="admin-nav-item <?= ($currentAdminDir === 'settings') ? 'active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        <span class="nav-label">Settings</span>
                    </a>
                </li>
                <li>
                    <a href="<?= ADMIN_URL ?>/section-visibility.php" class="admin-nav-item <?= ($currentAdminScript === 'section-visibility.php') ? 'active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                        <span class="nav-label">Home Sections &amp; Menus</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Divider & View Site Link (Screenshot 2) -->
            <div class="sidebar-divider"></div>
            <div class="sidebar-viewsite-wrap">
                <a href="<?= BASE_URL ?>/" target="_blank" class="admin-viewsite-btn">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                    <span>View site</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Top Bar (Clean Minimal White Header matching Screenshots 1, 3, 5) -->
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <button class="admin-toggle-btn" id="adminToggleBtn" onclick="toggleSidebarDrawer()" aria-label="Toggle sidebar menu">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <h1 class="admin-topbar-title"><?= isset($adminPageTitle) ? h($adminPageTitle) : 'Articles' ?></h1>
            </div>

            <div class="admin-topbar-right">
                <?php if ($currentAdminScript === 'articles.php'): ?>
                    <!-- Screenshot 1 Header Actions -->
                    <a href="<?= ADMIN_URL ?>/add-article.php" class="btn-topbar-primary">
                        + New Article
                    </a>
                    <a href="<?= ADMIN_URL ?>/articles.php?status=trash" class="btn-topbar-white">
                        Trash
                    </a>
                <?php elseif (in_array($currentAdminScript, ['add-article.php', 'edit-article.php'])): ?>
                    <!-- Screenshots 3, 4, 5 Header Actions -->
                    <button type="button" class="btn-topbar-primary" id="btnTopbarSave" onclick="submitArticleForm()">
                        Save
                    </button>
                <?php endif; ?>

                <!-- Notification Bell & Dropdown Center -->
                <div class="admin-bell-wrap" id="adminBellWrap">
                    <button type="button" class="admin-bell-btn" id="adminBellBtn" onclick="toggleNotificationDropdown(event)" aria-label="Notifications" aria-expanded="false" title="Notifications">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                        <?php if ($unreadNotifCount > 0): ?>
                            <span class="bell-dot" id="bellDot"></span>
                        <?php endif; ?>
                    </button>

                    <!-- Interactive Notification Dropdown Panel -->
                    <div class="admin-notifications-dropdown" id="adminNotificationsDropdown" onclick="event.stopPropagation()">
                        <div class="notif-header">
                            <div class="notif-header-left">
                                <h3 class="notif-title">Notifications</h3>
                                <span class="notif-badge <?= $unreadNotifCount === 0 ? 'zero' : '' ?>" id="notifBadge">
                                    <?= $unreadNotifCount > 0 ? "{$unreadNotifCount} new" : "0 new" ?>
                                </span>
                            </div>
                            <button type="button" class="notif-mark-all" id="notifMarkAllBtn" onclick="markAllNotificationsRead(event)" title="Mark all as read">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <span>Mark all read</span>
                            </button>
                        </div>

                        <div class="notif-body" id="notifBody">
                            <?php if (empty($notificationsList)): ?>
                                <div class="notif-empty-state">
                                    <div class="notif-empty-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                    </div>
                                    <p class="notif-empty-text">No recent notifications</p>
                                    <span class="notif-empty-sub">Your activity log is all caught up</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notificationsList as $idx => $notif): 
                                    $ts = strtotime($notif['created_at']);
                                    $isUnread = (!$hasReadState) ? ($idx < $unreadNotifCount) : ($ts > $lastReadNotifTime);
                                    
                                    $actionUpper = strtoupper($notif['action'] ?? '');
                                    $moduleLower = strtolower($notif['module'] ?? '');
                                    
                                    $iconClass = 'type-info';
                                    $itemTitle = 'System Update';
                                    $itemLink = ($canLogs) ? ADMIN_URL . '/logs/index.php' : '#';
                                    
                                    if (strpos($actionUpper, 'FAIL') !== false || strpos($actionUpper, 'UNAUTH') !== false || strpos($actionUpper, 'DENIED') !== false) {
                                        $iconClass = 'type-danger';
                                        $itemTitle = ($actionUpper === 'FAILED_LOGIN') ? 'Failed Login' : 'Security Alert';
                                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
                                    } elseif (strpos($actionUpper, 'LOGIN') !== false) {
                                        $iconClass = 'type-success';
                                        $itemTitle = 'Admin Signed In';
                                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>';
                                    } elseif (strpos($actionUpper, 'DELETE') !== false || strpos($actionUpper, 'TRASH') !== false) {
                                        $iconClass = 'type-warning';
                                        $itemTitle = 'Item Deleted';
                                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>';
                                    } elseif (strpos($moduleLower, 'article') !== false || strpos($moduleLower, 'post') !== false || strpos($moduleLower, 'content') !== false) {
                                        $iconClass = 'type-primary';
                                        $itemTitle = 'Content Updated';
                                        $itemLink = ADMIN_URL . '/articles.php';
                                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>';
                                    } elseif (strpos($moduleLower, 'user') !== false || strpos($moduleLower, 'admin') !== false) {
                                        $iconClass = 'type-purple';
                                        $itemTitle = 'User Management';
                                        $itemLink = ($isSuperAdmin) ? ADMIN_URL . '/admins/index.php' : '#';
                                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
                                    } else {
                                        $iconClass = 'type-info';
                                        $itemTitle = ucwords(str_replace('_', ' ', $notif['action']));
                                        $iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
                                    }

                                    $timeDiff = time() - $ts;
                                    if ($timeDiff < 60) {
                                        $timeAgo = 'Just now';
                                    } elseif ($timeDiff < 3600) {
                                        $timeAgo = floor($timeDiff / 60) . 'm ago';
                                    } elseif ($timeDiff < 86400) {
                                        $timeAgo = floor($timeDiff / 3600) . 'h ago';
                                    } elseif ($timeDiff < 604800) {
                                        $timeAgo = floor($timeDiff / 86400) . 'd ago';
                                    } else {
                                        $timeAgo = date('M j', $ts);
                                    }
                                ?>
                                    <a href="<?= htmlspecialchars($itemLink) ?>" class="notif-item <?= $isUnread ? 'unread' : 'read' ?>" data-id="<?= $notif['id'] ?>" data-time="<?= $ts ?>">
                                        <div class="notif-item-icon <?= $iconClass ?>">
                                            <?= $iconSvg ?>
                                        </div>
                                        <div class="notif-item-content">
                                            <div class="notif-item-header">
                                                <span class="notif-item-title"><?= h($itemTitle) ?></span>
                                                <span class="notif-item-time"><?= $timeAgo ?></span>
                                            </div>
                                            <p class="notif-item-desc" title="<?= h($notif['description']) ?>">
                                                <?= h($notif['description']) ?>
                                            </p>
                                            <?php if (!empty($notif['admin_name'])): ?>
                                                <span class="notif-item-actor"><?= h($notif['admin_name']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($isUnread): ?>
                                            <span class="notif-unread-dot"></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="notif-footer">
                            <?php if ($canLogs): ?>
                                <a href="<?= ADMIN_URL ?>/logs/index.php" class="notif-footer-link">
                                    <span>View All Activity Logs</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </a>
                            <?php else: ?>
                                <a href="<?= ADMIN_URL ?>/index.php" class="notif-footer-link">
                                    <span>Dashboard Overview</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- User Profile Chip (FA Faizan) -->
                <div class="admin-profile-chip-clean" title="Signed in as <?= h($displayNameClean) ?>">
                    <span class="avatar-circle-dark"><?= $userInitials ?></span>
                    <span class="avatar-name"><?= h($displayNameClean) ?></span>
                    <a href="<?= ADMIN_URL ?>/logout.php" class="logout-link-quick" title="Logout" onclick="return confirm('Sign out of admin session?');">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    </a>
                </div>
            </div>
        </header>

        <!-- Flash Messages in Admin -->
        <div class="admin-flash-wrap">
            <?= display_flash_messages(); ?>
        </div>

        <!-- Content Container Begins -->
        <div class="admin-content">
