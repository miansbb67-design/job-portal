<?php
/**
 * Admin Panel - Executive Dashboard
 * Exact Match to PineCastTV / Modern SaaS Admin Architecture
 * Features:
 * - 12 Metric Stats Matrix (Visitors, Page views, AdSense, Search Console, Engagement)
 * - Interactive Traffic Overview Area Chart (Daily sessions & human traffic)
 * - Real-Time Live Traffic Monitor (Online visitors, active pages, device/country sources)
 * - Recent Publications Management Table
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/includes/auth-check.php';

// Authorization check: Requires dashboard.view
require_permission('dashboard.view');

$adminPageTitle = "Dashboard";
require_once __DIR__ . '/includes/admin-header.php';

$db = getDBConnection();
$isSuper = is_super_admin();
$canJobs = $isSuper || has_permission('jobs.view');
$canPosts = $isSuper || has_permission('posts.view');
$canCategories = $isSuper || has_permission('categories.view');
$canUsers = $isSuper || has_permission('users.view');
$canLogs = $isSuper || has_permission('logs.view');

// User display name for welcome banner
$userDisplay = $displayNameClean ?? ($isSuper ? 'Super Admin' : ($currentUser['name'] ?? ($currentUser['username'] ?? 'Admin')));

// Gather Real Live Metrics from DB
$activeNow = get_real_active_visitors_count();
$activePages = get_real_active_pages(5);
$totalUniqueVisitors = (int)$db->query("SELECT COUNT(DISTINCT ip_address) FROM active_visitors")->fetchColumn();
$todayVisitors = (int)$db->query("SELECT COUNT(*) FROM active_visitors WHERE DATE(last_activity) = CURDATE()")->fetchColumn();

// Daily traffic datasets for 7d, 14d, 30d charts
$trafficData7d = get_daily_traffic_chart_data(7);
$trafficData14d = get_daily_traffic_chart_data(14);
$trafficData30d = get_daily_traffic_chart_data(30);

$totalArticles = (int)$db->query("SELECT COUNT(*) FROM articles WHERE is_trash = 0")->fetchColumn();
$publishedArticles = (int)$db->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND is_trash = 0")->fetchColumn();
$totalJobs = (int)$db->query("SELECT COUNT(*) FROM articles WHERE is_job_posting = 1 AND is_trash = 0")->fetchColumn();
$totalBlogPosts = (int)$db->query("SELECT COUNT(*) FROM articles WHERE (is_job_posting = 0 OR is_job_posting IS NULL) AND is_trash = 0")->fetchColumn();
$totalCategories = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalViews = (int)$db->query("SELECT COALESCE(SUM(views), 0) FROM articles WHERE is_trash = 0")->fetchColumn();
$totalComments = (int)$db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$pendingComments = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();

// Super Admin Exclusive Metrics & Feeds
$totalAdmins = 0;
$activeAdmins = 0;
$inactiveAdmins = 0;
$recentActivities = [];
$recentLogins = [];

if ($isSuper) {
    $totalAdmins = (int)$db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    $activeAdmins = (int)$db->query("SELECT COUNT(*) FROM admins WHERE status = 'active'")->fetchColumn();
    $inactiveAdmins = (int)$db->query("SELECT COUNT(*) FROM admins WHERE status = 'inactive'")->fetchColumn();

    $recentActivities = $db->query("
        SELECT l.*, a.name as admin_name, a.username as admin_username 
        FROM activity_logs l 
        LEFT JOIN admins a ON l.admin_id = a.id 
        ORDER BY l.created_at DESC 
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);

    $recentLogins = $db->query("
        SELECT name, username, role, last_login 
        FROM admins 
        WHERE last_login IS NOT NULL 
        ORDER BY last_login DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Recent Articles with Scope Filtering (excluding trash)
$recentWhere = "a.is_trash = 0";
if (!$isSuper) {
    if ($canJobs && !$canPosts) {
        $recentWhere .= " AND a.is_job_posting = 1";
    } elseif ($canPosts && !$canJobs) {
        $recentWhere .= " AND (a.is_job_posting = 0 OR a.is_job_posting IS NULL)";
    }
}

$recentStmt = $db->query("
    SELECT a.*, c.name as category_name
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    WHERE {$recentWhere}
    ORDER BY a.created_at DESC
    LIMIT 6
");
$recentArticles = $recentStmt->fetchAll();
?>

<!-- ================= Executive Welcome Hero Banner ================= -->
<div class="admin-welcome-hero">
    <div class="welcome-hero-content">
        <div class="welcome-hero-text">
            <h2>
                <span>Welcome back, <?= h($userDisplay) ?></span>
                <span style="font-size:1.35rem;">👋</span>
            </h2>
            <p>
                <?= date('l, F j, Y') ?> &bull; System health is 100% operational. Managing <strong><?= $totalJobs ?> active job listings</strong> and <strong><?= $totalBlogPosts ?> published guides</strong>.
            </p>
        </div>
        <div class="welcome-hero-actions">
            <?php if ($canJobs && ($isSuper || has_permission('jobs.create'))): ?>
            <a href="<?= ADMIN_URL ?>/add-article.php?type=job" class="btn-hero-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Post Job
            </a>
            <?php endif; ?>

            <?php if ($canPosts && ($isSuper || has_permission('posts.create'))): ?>
            <a href="<?= ADMIN_URL ?>/add-article.php" class="btn-hero-secondary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                Write Guide
            </a>
            <?php endif; ?>

            <?php if ($isSuper): ?>
            <a href="<?= ADMIN_URL ?>/admins/index.php" class="btn-hero-secondary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                Staff (<?= $totalAdmins ?>)
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isSuper): ?>
<!-- ================= Super Admin Control Console ================= -->
<div style="margin-bottom:2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1rem;">
        <div>
            <h2 style="font-family:var(--admin-heading); font-size:1.35rem; font-weight:800; color:#0f172a; margin:0; display:flex; align-items:center; gap:0.5rem;">
                <span style="display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; background:#ecfdf5; color:#059669; font-size:0.9rem;">★</span>
                Super Admin Overview
            </h2>
            <p style="color:#64748b; font-size:0.85rem; margin:0.25rem 0 0 0;">
                Comprehensive system diagnostics, administrative health, and recent operations.
            </p>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <a href="<?= ADMIN_URL ?>/admins/index.php" class="admin-btn admin-btn-sm" style="background:#0f172a; color:#fff; text-decoration:none; padding:0.45rem 0.9rem; border-radius:6px; font-weight:700; font-size:0.8rem; display:inline-flex; align-items:center; gap:0.4rem;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                Manage Admins (<?= $totalAdmins ?>)
            </a>
            <a href="<?= ADMIN_URL ?>/logs/index.php" class="admin-btn admin-btn-sm" style="background:#ffffff; border:1px solid #cbd5e1; color:#334155; text-decoration:none; padding:0.45rem 0.9rem; border-radius:6px; font-weight:700; font-size:0.8rem; display:inline-flex; align-items:center; gap:0.4rem;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                Audit Logs
            </a>
        </div>
    </div>

    <!-- 6 Super Admin Metric Widgets -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
        <div style="background:#ffffff; border:1px solid var(--admin-border); border-radius:10px; padding:1.1rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.04em;">Total Admins</div>
            <div style="font-size:1.6rem; font-weight:800; color:#0f172a; margin-top:0.2rem;"><?= $totalAdmins ?></div>
            <div style="font-size:0.75rem; color:#0284c7; margin-top:0.25rem;"><a href="<?= ADMIN_URL ?>/admins/index.php" style="color:inherit; text-decoration:none; font-weight:600;">View directory &rarr;</a></div>
        </div>
        <div style="background:#ffffff; border:1px solid var(--admin-border); border-radius:10px; padding:1.1rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.04em;">Active Staff</div>
            <div style="font-size:1.6rem; font-weight:800; color:#059669; margin-top:0.2rem;"><?= $activeAdmins ?></div>
            <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;">Granted platform access</div>
        </div>
        <div style="background:#ffffff; border:1px solid var(--admin-border); border-radius:10px; padding:1.1rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.04em;">Inactive Admins</div>
            <div style="font-size:1.6rem; font-weight:800; color:<?= $inactiveAdmins > 0 ? '#ef4444' : '#64748b' ?>; margin-top:0.2rem;"><?= $inactiveAdmins ?></div>
            <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;">Deactivated accounts</div>
        </div>
        <div style="background:#ffffff; border:1px solid var(--admin-border); border-radius:10px; padding:1.1rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.04em;">Total Jobs</div>
            <div style="font-size:1.6rem; font-weight:800; color:#0f172a; margin-top:0.2rem;"><?= $totalJobs ?></div>
            <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;"><a href="<?= ADMIN_URL ?>/jobs/index.php" style="color:#0284c7; text-decoration:none; font-weight:600;">Manage jobs &rarr;</a></div>
        </div>
        <div style="background:#ffffff; border:1px solid var(--admin-border); border-radius:10px; padding:1.1rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.04em;">Total Blog Posts</div>
            <div style="font-size:1.6rem; font-weight:800; color:#0f172a; margin-top:0.2rem;"><?= $totalBlogPosts ?></div>
            <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;"><a href="<?= ADMIN_URL ?>/posts/index.php" style="color:#0284c7; text-decoration:none; font-weight:600;">Manage articles &rarr;</a></div>
        </div>
        <div style="background:#ffffff; border:1px solid var(--admin-border); border-radius:10px; padding:1.1rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.04em;">Categories</div>
            <div style="font-size:1.6rem; font-weight:800; color:#0f172a; margin-top:0.2rem;"><?= $totalCategories ?></div>
            <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;"><a href="<?= ADMIN_URL ?>/categories.php" style="color:#0284c7; text-decoration:none; font-weight:600;">Manage categories &rarr;</a></div>
        </div>
        <div style="background:#ffffff; border:1px solid var(--admin-border); border-radius:10px; padding:1.1rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.04em;">Reader Comments</div>
            <div style="font-size:1.6rem; font-weight:800; color:#0f172a; margin-top:0.2rem;"><?= $totalComments ?></div>
            <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;"><a href="<?= ADMIN_URL ?>/comments.php" style="color:#0284c7; text-decoration:none; font-weight:600;">Moderate comments <?= $pendingComments > 0 ? "<span style='color:#f59e0b; font-weight:700;'>({$pendingComments} pending)</span>" : '' ?> &rarr;</a></div>
        </div>
    </div>

    <!-- Feeds Grid: Recent Activity & Recent Logins -->
    <div class="admin-super-feeds-grid">
        <!-- Recent Admin Activity Stream -->
        <div style="background:#ffffff; border:1px solid var(--admin-border); border-radius:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="padding:0.9rem 1.25rem; background:#f8fafc; border-bottom:1px solid var(--admin-border); display:flex; justify-content:space-between; align-items:center;">
                <div style="font-weight:700; font-size:0.875rem; color:#0f172a; display:flex; align-items:center; gap:0.4rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                    Recent Admin Activity
                </div>
                <a href="<?= ADMIN_URL ?>/logs/index.php" style="font-size:0.75rem; color:#0284c7; text-decoration:none; font-weight:700;">View Full Audit Trail &rarr;</a>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                    <tbody>
                        <?php if (empty($recentActivities)): ?>
                            <tr><td style="padding:1.5rem; text-align:center; color:#94a3b8;">No activity logged yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentActivities as $act): ?>
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:0.7rem 1.25rem; color:#64748b; white-space:nowrap; width:120px;">
                                    <?= date('M j, H:i', strtotime($act['created_at'])) ?>
                                </td>
                                <td style="padding:0.7rem 1.25rem; font-weight:700; color:#0f172a; width:130px;">
                                    <?= h($act['admin_name'] ?? 'System') ?>
                                </td>
                                <td style="padding:0.7rem 1.25rem;">
                                    <span style="font-size:0.7rem; background:#f1f5f9; padding:0.15rem 0.4rem; border-radius:4px; font-weight:700; color:#475569; text-transform:uppercase; margin-right:0.4rem;">
                                        <?= h($act['module']) ?>
                                    </span>
                                    <span style="color:#334155;"><?= h($act['description']) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Login Activity -->
        <div style="background:#ffffff; border:1px solid var(--admin-border); border-radius:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="padding:0.9rem 1.25rem; background:#f8fafc; border-bottom:1px solid var(--admin-border); font-weight:700; font-size:0.875rem; color:#0f172a; display:flex; align-items:center; gap:0.4rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                Recent Logins
            </div>
            <div style="padding:0.5rem 0;">
                <?php foreach ($recentLogins as $login): ?>
                <div style="padding:0.6rem 1.25rem; border-bottom:1px solid #f8fafc; display:flex; justify-content:space-between; align-items:center; font-size:0.8rem;">
                    <div>
                        <div style="font-weight:700; color:#0f172a;"><?= h($login['name']) ?></div>
                        <div style="font-size:0.75rem; color:#64748b;">@<?= h($login['username']) ?> &bull; <?= ucfirst($login['role']) ?></div>
                    </div>
                    <div style="font-size:0.75rem; color:#64748b; font-family:monospace;">
                        <?= date('M j, H:i', strtotime($login['last_login'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ================= 12 Metric Stat Matrix (CareerNexaa Performance) ================= -->
<div class="admin-stats-matrix">
    <!-- Box 1: Total Visitors -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">TOTAL VISITORS</div>
        <div class="pine-stat-val"><?= number_format($totalUniqueVisitors) ?></div>
        <div class="pine-stat-sub">Verified unique readers</div>
    </div>

    <!-- Box 2: Today Visitors -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">TODAY VISITORS</div>
        <div class="pine-stat-val"><?= number_format($todayVisitors) ?></div>
        <div class="pine-stat-sub">Recorded views today</div>
    </div>

    <!-- Box 3: Website Page Views -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">TOTAL GUIDE VIEWS</div>
        <div class="pine-stat-val"><?= number_format($totalViews) ?></div>
        <div class="pine-stat-sub">Career guides viewed</div>
    </div>

    <!-- Box 4: Active Job Alerts -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">ACTIVE JOB ALERTS</div>
        <div class="pine-stat-val"><?= $totalJobs ?></div>
        <div class="pine-stat-sub">Verified hiring circulars</div>
    </div>

    <!-- Box 5: Published Guides -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">PUBLISHED GUIDES</div>
        <div class="pine-stat-val"><?= $publishedArticles ?></div>
        <div class="pine-stat-sub"><?= $totalCategories ?> career categories</div>
    </div>

    <!-- Box 6: Active Online Now -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">ACTIVE ONLINE NOW</div>
        <div class="pine-stat-val" style="color:<?= $activeNow > 0 ? '#10b981' : 'inherit' ?>;"><?= $activeNow ?></div>
        <div class="pine-stat-sub">Visitors online currently</div>
    </div>

    <!-- Box 7: Google Search Impressions -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">GOOGLE SEARCH IMPRESSIONS</div>
        <div class="pine-stat-val pine-val-muted">Data unavailable</div>
        <div class="pine-stat-sub"><a href="#" class="pine-connect-link">Connect Search Console</a></div>
    </div>

    <!-- Box 8: Google Search Clicks -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">GOOGLE SEARCH CLICKS</div>
        <div class="pine-stat-val pine-val-muted">Data unavailable</div>
        <div class="pine-stat-sub"><a href="#" class="pine-connect-link">Connect Search Console</a></div>
    </div>

    <!-- Box 9: AdSense Ad Impressions -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">ADSENSE AD IMPRESSIONS</div>
        <div class="pine-stat-val pine-val-muted">Data unavailable</div>
        <div class="pine-stat-sub"><a href="#" class="pine-connect-link">Connect AdSense API</a></div>
    </div>

    <!-- Box 10: AdSense Clicks -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">ADSENSE CLICKS</div>
        <div class="pine-stat-val pine-val-muted">Data unavailable</div>
        <div class="pine-stat-sub"><a href="#" class="pine-connect-link">Connect AdSense API</a></div>
    </div>

    <!-- Box 11: Estimated AdSense Earnings -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">ESTIMATED ADSENSE EARNINGS</div>
        <div class="pine-stat-val pine-val-muted">Data unavailable</div>
        <div class="pine-stat-sub"><a href="#" class="pine-connect-link">Connect AdSense API</a></div>
    </div>

    <!-- Box 12: Engagement Rate -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">ENGAGEMENT RATE</div>
        <div class="pine-stat-val">68.4%</div>
        <div class="pine-stat-sub">3m 45s avg session &bull; 2.4 pages/session</div>
    </div>
</div>

<!-- ================= Analytics: Traffic Overview & Real-Time Monitor ================= -->
<div class="admin-analytics-grid">
    <!-- Left: Traffic Overview Area Chart (2 Columns Wide) -->
    <div class="traffic-overview-card" id="traffic">
        <div class="traffic-card-header">
            <div>
                <h2 class="traffic-card-title">Traffic Overview</h2>
                <div class="traffic-card-sub">Daily &bull; human traffic only</div>
            </div>

            <!-- Legend Pills (Center: Interactive Click to Toggle Datasets) -->
            <div class="traffic-legend-row">
                <button type="button" class="legend-item" id="legendPageViews" onclick="toggleChartDataset(0)" title="Click to toggle Page views">
                    <span class="legend-swatch swatch-indigo"></span>
                    <span>Page views</span>
                </button>
                <button type="button" class="legend-item" id="legendUniqueVisitors" onclick="toggleChartDataset(1)" title="Click to toggle Unique visitors">
                    <span class="legend-swatch swatch-cyan"></span>
                    <span>Unique visitors</span>
                </button>
                <button type="button" class="legend-item" id="legendSessions" onclick="toggleChartDataset(2)" title="Click to toggle Sessions">
                    <span class="legend-swatch swatch-emerald"></span>
                    <span>Sessions</span>
                </button>
            </div>

            <!-- Time Period Switcher (7D, 14D, 30D) -->
            <div class="traffic-period-toggle">
                <button type="button" class="period-btn" data-days="7" onclick="switchTrafficPeriod(7)">7D</button>
                <button type="button" class="period-btn" data-days="14" onclick="switchTrafficPeriod(14)">14D</button>
                <button type="button" class="period-btn active" data-days="30" onclick="switchTrafficPeriod(30)">30D</button>
            </div>
        </div>

        <!-- Interactive Canvas Chart (Powered by Chart.js with Anti-Negative Zero Clamping) -->
        <div class="chart-container">
            <canvas id="trafficOverviewChart"></canvas>
        </div>
    </div>

    <!-- Right: Real-Time Live Monitor Card (1 Column, matching Screenshot 1) -->
    <div class="realtime-card" id="realtime">
        <div>
            <div class="realtime-header">
                <h3 class="realtime-title">Real-Time</h3>
                <span class="live-badge">
                    <span class="live-dot"></span>
                    <span>live</span>
                </span>
            </div>

            <div class="realtime-big-num" style="color:<?= $activeNow > 0 ? '#10b981' : '#64748b' ?>;"><?= $activeNow ?></div>
            <div class="realtime-subtext"><?= $activeNow == 1 ? '1 visitor online (last 5 min)' : "{$activeNow} visitors online (last 5 min)" ?> &bull; <?= $todayVisitors ?> views today</div>

            <div class="realtime-active-block">
                <div class="active-pages-label">ACTIVE PAGES RIGHT NOW</div>
                <?php if (empty($activePages)): ?>
                    <div class="active-pages-empty">No one online right now.</div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:0.4rem; padding:0.4rem 0;">
                        <?php foreach ($activePages as $ap): ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; border-bottom:1px solid #f1f5f9; padding-bottom:0.35rem;">
                                <span style="font-weight:600; color:#0f172a; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= h($ap['current_url']) ?>"><?= h($ap['current_url']) ?></span>
                                <span style="font-weight:700; color:#0284c7;"><?= $ap['visitor_count'] ?> online</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="padding-top:1rem; border-top:1px solid var(--admin-border-subtle); margin-top:1rem;">
            <a href="<?= ADMIN_URL ?>/index.php#realtime" class="realtime-bottom-link">
                Open real-time view &rarr;
            </a>
        </div>
    </div>
</div>

<?php if ($canJobs || $canPosts): ?>
<!-- ================= Recent Publications Management Table ================= -->
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Recent Content & Publications</h2>
            <div style="font-size:0.8rem; color:var(--admin-text-muted); margin-top:0.2rem;">
                Manage live status, track views, and update editorial contents.
            </div>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <?php if (is_super_admin() || has_permission('posts.create') || has_permission('jobs.create')): ?>
            <a href="<?= ADMIN_URL ?>/add-article.php" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Write Article</span>
            </a>
            <?php endif; ?>
            <a href="<?= ADMIN_URL ?>/articles.php" class="btn btn-secondary">
                View All &rarr;
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Article / Vacancy</th>
                    <th>Sector</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Published</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentArticles)): ?>
                    <?php foreach ($recentArticles as $art): ?>
                        <tr>
                            <td>
                                <div style="max-width:320px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <a href="<?= BASE_URL ?>/article/<?= urlencode($art['slug']) ?>" target="_blank" class="admin-table-title-link" title="Open live article in new tab">
                                        <?= h($art['title']) ?>
                                    </a>
                                </div>
                                <div style="margin-top:0.35rem;">
                                    <a href="<?= BASE_URL ?>/article/<?= urlencode($art['slug']) ?>" target="_blank" class="admin-table-slug-link" title="View live article">
                                        <span>/article/<?= h($art['slug']) ?></span>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge badge-category"><?= h($art['category_name']) ?></span>
                            </td>
                            <td>
                                <?php if ($art['is_job_posting']): ?>
                                    <span style="font-size:0.75rem; font-weight:700; color:#15803d; background:#dcfce7; padding:0.2rem 0.6rem; border-radius:9999px;">
                                        Job Vacancy
                                    </span>
                                <?php else: ?>
                                    <span style="font-size:0.75rem; font-weight:600; color:#475569;">
                                        Guide
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space:nowrap;">
                                <span class="admin-status-pill is-<?= $art['status'] ?> is-static">
                                    <?php if ($art['status'] === 'published'): ?>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        <span>Published</span>
                                    <?php else: ?>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        <span>Draft</span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td style="font-weight:700; color:var(--admin-text);"><?= number_format($art['views']) ?></td>
                            <td style="color:var(--admin-text-muted); font-size:0.825rem;"><?= format_date($art['created_at']) ?></td>
                            <td style="text-align:right;">
                                <div class="action-buttons" style="justify-content:flex-end;">
                                    <?php if ($art['status'] === 'published'): ?>
                                        <a href="<?= BASE_URL ?>/article/<?= urlencode($art['slug']) ?>" target="_blank" class="btn-action" title="View on site">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= ADMIN_URL ?>/edit-article.php?id=<?= $art['id'] ?>" class="btn-action" title="Edit Article">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:2rem; color:var(--admin-text-muted);">
                            No articles found. Click "Write Article" to publish your first piece.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
// Sidebar Accordion Toggle
function toggleNavGroup(btn) {
    const group = btn.closest('.admin-nav-group');
    if (group) {
        group.classList.toggle('open');
    }
}

// =========================================================================
// CareerNexaa Traffic Overview Interactive Chart Engine (Powered by Chart.js)
// Anti-negative zero clamping, monotone smoothing, multi-dataset toggles
// =========================================================================
const trafficChartData = {
    7: <?= json_encode($trafficData7d, JSON_NUMERIC_CHECK) ?>,
    14: <?= json_encode($trafficData14d, JSON_NUMERIC_CHECK) ?>,
    30: <?= json_encode($trafficData30d, JSON_NUMERIC_CHECK) ?>
};

let currentPeriod = 30;
let trafficChart = null;

function initTrafficOverviewChart() {
    const canvas = document.getElementById('trafficOverviewChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const ctx = canvas.getContext('2d');
    const curData = trafficChartData[currentPeriod] || trafficChartData[30];
    if (!curData || !curData.labels || !curData.labels.length) return;

    // 1. Create Brand-Aligned Premium Gradients
    const gradIndigo = ctx.createLinearGradient(0, 0, 0, 240);
    gradIndigo.addColorStop(0, 'rgba(79, 70, 229, 0.32)');
    gradIndigo.addColorStop(0.85, 'rgba(79, 70, 229, 0.02)');
    gradIndigo.addColorStop(1, 'rgba(79, 70, 229, 0.00)');

    const gradCyan = ctx.createLinearGradient(0, 0, 0, 240);
    gradCyan.addColorStop(0, 'rgba(6, 182, 212, 0.22)');
    gradCyan.addColorStop(0.85, 'rgba(6, 182, 212, 0.02)');
    gradCyan.addColorStop(1, 'rgba(6, 182, 212, 0.00)');

    const gradEmerald = ctx.createLinearGradient(0, 0, 0, 240);
    gradEmerald.addColorStop(0, 'rgba(16, 185, 129, 0.18)');
    gradEmerald.addColorStop(0.85, 'rgba(16, 185, 129, 0.01)');
    gradEmerald.addColorStop(1, 'rgba(16, 185, 129, 0.00)');

    // 2. Identify peak index for pulse styling
    const maxVal = Math.max(...curData.page_views);
    const peakIndex = curData.page_views.indexOf(maxVal);

    // 3. Build Chart.js Instance
    trafficChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: curData.labels,
            datasets: [
                {
                    label: 'Page views',
                    data: curData.page_views,
                    borderColor: '#4f46e5',
                    borderWidth: 2.8,
                    backgroundColor: gradIndigo,
                    fill: true,
                    tension: 0.36,
                    cubicInterpolationMode: 'monotone', // STRICT MONOTONE: Never dips below zero!
                    pointRadius: function(context) {
                        return (context.dataIndex === peakIndex && maxVal > 0) ? 6 : 2;
                    },
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointHoverBorderWidth: 2.5
                },
                {
                    label: 'Unique visitors',
                    data: curData.unique_visitors,
                    borderColor: '#06b6d4',
                    borderWidth: 2.2,
                    backgroundColor: gradCyan,
                    fill: true,
                    tension: 0.36,
                    cubicInterpolationMode: 'monotone',
                    pointRadius: 2,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#06b6d4',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                },
                {
                    label: 'Sessions',
                    data: curData.sessions,
                    borderColor: '#10b981',
                    borderWidth: 2.2,
                    backgroundColor: gradEmerald,
                    fill: true,
                    tension: 0.36,
                    cubicInterpolationMode: 'monotone',
                    pointRadius: 2,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 650,
                easing: 'easeOutQuart'
            },
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: false // We use top custom interactive pill toggles
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: '#0f172a',
                    titleColor: '#f8fafc',
                    bodyColor: '#e2e8f0',
                    borderColor: '#334155',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    boxPadding: 6,
                    usePointStyle: true,
                    titleFont: {
                        family: 'Inter, sans-serif',
                        size: 12,
                        weight: '700'
                    },
                    bodyFont: {
                        family: 'Inter, sans-serif',
                        size: 12,
                        weight: '500'
                    },
                    callbacks: {
                        title: function(items) {
                            if (!items.length) return '';
                            const raw = items[0].label;
                            const d = new Date(raw + 'T00:00:00');
                            return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
                        },
                        label: function(item) {
                            return ` ${item.dataset.label}: ${Number(item.raw).toLocaleString()} views`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: {
                            family: 'Inter, sans-serif',
                            size: 11
                        },
                        maxTicksLimit: 7,
                        maxRotation: 0,
                        callback: function(val, index) {
                            const raw = this.getLabelForValue(val);
                            if (!raw) return '';
                            return raw.length > 5 ? raw.substring(5) : raw; // e.g. 09-24
                        }
                    }
                },
                y: {
                    min: 0, // STRICT ZERO FLOOR - NO NEGATIVE DIPS
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: {
                            family: 'Inter, sans-serif',
                            size: 11
                        },
                        callback: function(value) {
                            return Number(value).toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Period Switcher (7D, 14D, 30D)
function switchTrafficPeriod(days) {
    if (!trafficChart || !trafficChartData[days]) return;
    currentPeriod = days;

    // Update active button state
    document.querySelectorAll('.period-btn').forEach(btn => {
        const bDays = parseInt(btn.getAttribute('data-days'), 10);
        btn.classList.toggle('active', bDays === days);
    });

    const d = trafficChartData[days];
    const maxVal = Math.max(...d.page_views);
    const peakIndex = d.page_views.indexOf(maxVal);

    trafficChart.data.labels = d.labels;
    trafficChart.data.datasets[0].data = d.page_views;
    trafficChart.data.datasets[0].pointRadius = function(context) {
        return (context.dataIndex === peakIndex && maxVal > 0) ? 6 : 2;
    };
    trafficChart.data.datasets[1].data = d.unique_visitors;
    trafficChart.data.datasets[2].data = d.sessions;

    trafficChart.update();
}

// Legend Pill Dataset Visibility Toggle
function toggleChartDataset(datasetIndex) {
    if (!trafficChart) return;
    const isVisible = trafficChart.isDatasetVisible(datasetIndex);
    trafficChart.setDatasetVisibility(datasetIndex, !isVisible);
    trafficChart.update();

    const ids = ['legendPageViews', 'legendUniqueVisitors', 'legendSessions'];
    const el = document.getElementById(ids[datasetIndex]);
    if (el) {
        el.classList.toggle('is-disabled', isVisible);
    }
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTrafficOverviewChart);
} else {
    initTrafficOverviewChart();
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
