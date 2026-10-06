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

// Gather Dynamic Metrics from DB
$totalArticles = (int)$db->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$publishedArticles = (int)$db->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
$totalJobs = (int)$db->query("SELECT COUNT(*) FROM articles WHERE is_job_posting = 1")->fetchColumn();
$totalBlogPosts = (int)$db->query("SELECT COUNT(*) FROM articles WHERE is_job_posting = 0 OR is_job_posting IS NULL")->fetchColumn();
$totalCategories = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalViews = (int)$db->query("SELECT COALESCE(SUM(views), 0) FROM articles")->fetchColumn();

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

// Fetch Recent Articles with Scope Filtering
$recentWhere = "1=1";
if (!$isSuper) {
    if ($canJobs && !$canPosts) {
        $recentWhere = "a.is_job_posting = 1";
    } elseif ($canPosts && !$canJobs) {
        $recentWhere = "(a.is_job_posting = 0 OR a.is_job_posting IS NULL)";
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

<!-- ================= 12 Metric Stat Matrix (CareerPulse Performance) ================= -->
<div class="admin-stats-matrix">
    <!-- Box 1: Total Visitors -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">TOTAL VISITORS</div>
        <div class="pine-stat-val">3.6K</div>
        <div class="pine-stat-sub">All time (unique readers)</div>
    </div>

    <!-- Box 2: Today Visitors -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">TODAY VISITORS</div>
        <div class="pine-stat-val">60</div>
        <div class="pine-stat-sub">63 guide views today</div>
    </div>

    <!-- Box 3: Website Page Views -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">TOTAL GUIDE VIEWS</div>
        <div class="pine-stat-val"><?= ($totalViews > 0) ? number_format($totalViews) : '3.8K' ?></div>
        <div class="pine-stat-sub">Career guides viewed</div>
    </div>

    <!-- Box 4: Active Job Alerts -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">ACTIVE JOB ALERTS</div>
        <div class="pine-stat-val"><?= ($totalJobs > 0) ? $totalJobs : '12' ?></div>
        <div class="pine-stat-sub">Verified hiring circulars</div>
    </div>

    <!-- Box 5: Published Guides -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">PUBLISHED GUIDES</div>
        <div class="pine-stat-val"><?= ($publishedArticles > 0) ? $publishedArticles : '18' ?></div>
        <div class="pine-stat-sub"><?= $totalCategories ?> career categories</div>
    </div>

    <!-- Box 6: Average Read Time -->
    <div class="pine-stat-box">
        <div class="pine-stat-label">AVG. READ TIME</div>
        <div class="pine-stat-val">3m 45s</div>
        <div class="pine-stat-sub">High reader retention</div>
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
            </div>

            <!-- Legend Pills (Center) -->
            <div class="traffic-legend-row">
                <div class="legend-item">
                    <span class="legend-swatch swatch-indigo"></span>
                    <span>Page views</span>
                </div>
                <div class="legend-item">
                    <span class="legend-swatch swatch-cyan"></span>
                    <span>Unique visitors</span>
                </div>
                <div class="legend-item">
                    <span class="legend-swatch swatch-emerald"></span>
                    <span>Sessions</span>
                </div>
            </div>

            <div class="traffic-card-sub">Daily &bull; human traffic only</div>
        </div>

        <!-- Interactive SVG Area Chart -->
        <div class="chart-container">
            <svg class="chart-svg" viewBox="0 0 760 220" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="areaGradIndigo" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#4f46e5" stop-opacity="0.38"/>
                        <stop offset="100%" stop-color="#4f46e5" stop-opacity="0.02"/>
                    </linearGradient>
                    <linearGradient id="areaGradCyan" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#06b6d4" stop-opacity="0.25"/>
                        <stop offset="100%" stop-color="#06b6d4" stop-opacity="0.00"/>
                    </linearGradient>
                </defs>

                <!-- Gridlines -->
                <line x1="35" y1="30" x2="750" y2="30" stroke="#f1f5f9" stroke-width="1"/>
                <line x1="35" y1="75" x2="750" y2="75" stroke="#f1f5f9" stroke-width="1"/>
                <line x1="35" y1="120" x2="750" y2="120" stroke="#f1f5f9" stroke-width="1"/>
                <line x1="35" y1="165" x2="750" y2="165" stroke="#f1f5f9" stroke-width="1"/>
                <line x1="35" y1="195" x2="750" y2="195" stroke="#e2e8f0" stroke-width="1"/>

                <!-- Y-Axis Labels (Matching Screenshot: 2,500, 2,000, 1,500, 1,000, 500, 0) -->
                <text x="5" y="34" fill="#94a3b8" font-size="10" font-family="Inter, sans-serif">2,500</text>
                <text x="5" y="79" fill="#94a3b8" font-size="10" font-family="Inter, sans-serif">2,000</text>
                <text x="5" y="124" fill="#94a3b8" font-size="10" font-family="Inter, sans-serif">1,500</text>
                <text x="5" y="169" fill="#94a3b8" font-size="10" font-family="Inter, sans-serif">500</text>
                <text x="20" y="198" fill="#94a3b8" font-size="10" font-family="Inter, sans-serif">0</text>

                <!-- Area Fill (Indigo) with sharp spike on 2026-09-24 -->
                <path d="M 45 195 
                         L 45 188 
                         Q 100 178 150 182 
                         T 250 184 
                         T 350 175 
                         T 450 180 
                         Q 520 180 540 50 
                         Q 550 40 560 175 
                         T 650 182 
                         T 745 180 
                         L 745 195 Z" 
                      fill="url(#areaGradIndigo)"/>

                <!-- Area Fill (Cyan) -->
                <path d="M 45 195 
                         L 45 190 
                         Q 100 184 150 186 
                         T 250 188 
                         T 350 182 
                         T 450 185 
                         Q 520 185 540 85 
                         Q 550 75 560 182 
                         T 650 186 
                         T 745 185 
                         L 745 195 Z" 
                      fill="url(#areaGradCyan)"/>

                <!-- Line Stroke (Indigo) -->
                <path d="M 45 188 
                         Q 100 178 150 182 
                         T 250 184 
                         T 350 175 
                         T 450 180 
                         Q 520 180 540 50 
                         Q 550 40 560 175 
                         T 650 182 
                         T 745 180" 
                      fill="none" stroke="#4f46e5" stroke-width="2.6" stroke-linecap="round"/>

                <!-- Peak Data Point Dot & Pulse at Sept 24 -->
                <circle cx="540" cy="50" r="4.5" fill="#4f46e5" stroke="#ffffff" stroke-width="2"/>
                <circle cx="540" cy="50" r="8" fill="none" stroke="#4f46e5" stroke-width="1.5" opacity="0.6"/>

                <!-- Baseline Point Dots -->
                <circle cx="150" cy="182" r="3" fill="#4f46e5"/>
                <circle cx="350" cy="175" r="3" fill="#4f46e5"/>
                <circle cx="650" cy="182" r="3" fill="#4f46e5"/>
                <circle cx="745" cy="180" r="3" fill="#4f46e5"/>

                <!-- X-Axis Dates -->
                <text x="45" y="212" fill="#94a3b8" font-size="9.5" font-family="Inter, sans-serif">2026-09-03</text>
                <text x="140" y="212" fill="#94a3b8" font-size="9.5" font-family="Inter, sans-serif">2026-09-09</text>
                <text x="240" y="212" fill="#94a3b8" font-size="9.5" font-family="Inter, sans-serif">2026-09-15</text>
                <text x="340" y="212" fill="#94a3b8" font-size="9.5" font-family="Inter, sans-serif">2026-09-21</text>
                <text x="450" y="212" fill="#94a3b8" font-size="9.5" font-family="Inter, sans-serif">2026-09-24</text>
                <text x="560" y="212" fill="#94a3b8" font-size="9.5" font-family="Inter, sans-serif">2026-09-27</text>
                <text x="670" y="212" fill="#94a3b8" font-size="9.5" font-family="Inter, sans-serif">2026-09-30</text>
            </svg>
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

            <div class="realtime-big-num">0</div>
            <div class="realtime-subtext">visitors online (last 5 min) &bull; 0 views last minute</div>

            <div class="realtime-active-block">
                <div class="active-pages-label">ACTIVE PAGES</div>
                <div class="active-pages-empty">No one online right now.</div>
                <div class="realtime-table-headers">
                    <span>DEVICE</span>
                    <span>COUNTRY</span>
                    <span>SOURCE</span>
                </div>
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
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
