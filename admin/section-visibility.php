<?php
/**
 * Admin Panel - Home Sections & Menus Control
 * Exact match to Screenshots 1, 2, and 3:
 * - Home page sections (HERO, LATEST, CATEGORIES, POPULAR, ARTICLES, RECOMMENDED, NEWSLETTER)
 * - Header menu manager (Reorder, toggle, label edit, remove, add new)
 * - Footer menu manager (Reorder, toggle, label edit, remove, add new)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/includes/auth-check.php';

// Authorization check: Requires settings or Super Admin
if (!is_super_admin() && !has_permission('settings.view')) {
    require_permission('settings.view');
}

$db = getDBConnection();

// Load categories for dropdowns
$categories = $db->query("SELECT id, name, slug FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle POST submissions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_POST['form_type']) && $_POST['form_type'] === 'toggle_single_section')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Security token expired. Please refresh the page.']);
            exit;
        }
        set_flash('error', 'Security token expired. Please refresh the page and try again.');
        header('Location: ' . ADMIN_URL . '/section-visibility.php');
        exit;
    }

    $formType = $_POST['form_type'] ?? '';

    // AJAX quick toggle for any section
    if ($formType === 'toggle_single_section') {
        header('Content-Type: application/json');
        $secKey = trim($_POST['section_key'] ?? '');
        $newState = !empty($_POST['is_enabled']) ? 1 : 0;
        if (!empty($secKey)) {
            $tStmt = $db->prepare("UPDATE section_visibility SET is_enabled = ?, updated_at = NOW() WHERE section_key = ?");
            $tStmt->execute([$newState, $secKey]);
            log_activity('update', 'settings', "Toggled section '{$secKey}' to " . ($newState ? 'Enabled' : 'Disabled'));
            echo json_encode(['success' => true, 'section_key' => $secKey, 'is_enabled' => $newState]);
            exit;
        }
        echo json_encode(['success' => false, 'error' => 'Invalid section key']);
        exit;
    }

    // 1. Save Home Page Sections
    if ($formType === 'save_sections') {
        $sectionsInput = $_POST['sections'] ?? [];
        if (is_array($sectionsInput)) {
            foreach ($sectionsInput as $key => $secData) {
                $isEnabled = (!empty($secData['enabled']) && (string)$secData['enabled'] === '1') ? 1 : 0;
                $title = isset($secData['title']) ? trim($secData['title']) : '';
                $subtitle = isset($secData['subtitle']) ? trim($secData['subtitle']) : '';
                $titleVal = ($title === '') ? null : $title;
                $subVal = ($subtitle === '') ? null : $subtitle;
                $limit = isset($secData['limit']) && is_numeric($secData['limit']) ? (int)$secData['limit'] : 6;
                $catId = !empty($secData['category_id']) && is_numeric($secData['category_id']) ? (int)$secData['category_id'] : null;

                $upStmt = $db->prepare("
                    UPDATE section_visibility 
                    SET is_enabled = ?, title = ?, subtitle = ?, item_limit = ?, category_id = ?, updated_at = NOW()
                    WHERE section_key = ?
                ");
                $upStmt->execute([$isEnabled, $titleVal, $subVal, $limit, $catId, $key]);
            }
            log_activity('update', 'settings', "Updated Home Sections configuration");
            set_flash('success', 'Home page sections saved successfully!');
        }
    }

    // 2. Save Header Menu
    elseif ($formType === 'save_header_menu') {
        $itemsInput = $_POST['header_menu'] ?? [];
        $menuToSave = [];
        if (is_array($itemsInput)) {
            foreach ($itemsInput as $idx => $mItem) {
                $label = trim($mItem['label'] ?? '');
                if ($label === '') continue;
                $menuToSave[] = [
                    'id' => (string)($idx + 1),
                    'label' => $label,
                    'url' => trim($mItem['url'] ?? '/'),
                    'enabled' => isset($mItem['enabled']) ? true : false
                ];
            }
        }
        $setStmt = $db->prepare("
            INSERT INTO settings (setting_key, setting_value, setting_group)
            VALUES ('header_menu_items', ?, 'navigation')
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $setStmt->execute([json_encode($menuToSave, JSON_PRETTY_PRINT)]);
        log_activity('update', 'settings', "Updated Header Navigation Menu");
        set_flash('success', 'Header menu saved successfully!');
    }

    // 3. Save Footer Menu
    elseif ($formType === 'save_footer_menu') {
        $itemsInput = $_POST['footer_menu'] ?? [];
        $menuToSave = [];
        if (is_array($itemsInput)) {
            foreach ($itemsInput as $idx => $mItem) {
                $label = trim($mItem['label'] ?? '');
                if ($label === '') continue;
                $menuToSave[] = [
                    'id' => (string)($idx + 1),
                    'label' => $label,
                    'url' => trim($mItem['url'] ?? '/'),
                    'enabled' => isset($mItem['enabled']) ? true : false
                ];
            }
        }
        $setStmt = $db->prepare("
            INSERT INTO settings (setting_key, setting_value, setting_group)
            VALUES ('footer_menu_items', ?, 'navigation')
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $setStmt->execute([json_encode($menuToSave, JSON_PRETTY_PRINT)]);
        log_activity('update', 'settings', "Updated Footer Navigation Menu");
        set_flash('success', 'Footer menu saved successfully!');
    }

    // 4. Add New Header Menu Item
    elseif ($formType === 'add_header_item') {
        $newLabel = trim($_POST['new_label'] ?? '');
        $newUrl = trim($_POST['new_url'] ?? '');
        if ($newLabel !== '') {
            $currentMenu = get_header_menu();
            $currentMenu[] = [
                'id' => (string)(count($currentMenu) + 1),
                'label' => $newLabel,
                'url' => $newUrl ?: '/',
                'enabled' => true
            ];
            $setStmt = $db->prepare("
                INSERT INTO settings (setting_key, setting_value, setting_group)
                VALUES ('header_menu_items', ?, 'navigation')
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $setStmt->execute([json_encode($currentMenu, JSON_PRETTY_PRINT)]);
            set_flash('success', "Item '{$newLabel}' added to Header menu!");
        }
    }

    // 5. Add New Footer Menu Item
    elseif ($formType === 'add_footer_item') {
        $newLabel = trim($_POST['new_label'] ?? '');
        $newUrl = trim($_POST['new_url'] ?? '');
        if ($newLabel !== '') {
            $currentMenu = get_footer_menu();
            $currentMenu[] = [
                'id' => (string)(count($currentMenu) + 1),
                'label' => $newLabel,
                'url' => $newUrl ?: '/',
                'enabled' => true
            ];
            $setStmt = $db->prepare("
                INSERT INTO settings (setting_key, setting_value, setting_group)
                VALUES ('footer_menu_items', ?, 'navigation')
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $setStmt->execute([json_encode($currentMenu, JSON_PRETTY_PRINT)]);
            set_flash('success', "Item '{$newLabel}' added to Footer menu!");
        }
    }

    header('Location: ' . ADMIN_URL . '/section-visibility.php');
    exit;
}

// Fetch all section visibility rows from database
$allDbSections = $db->query("SELECT * FROM section_visibility ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Map by section_key for easy lookup
$sectionsByKey = [];
foreach ($allDbSections as $sec) {
    $sectionsByKey[$sec['section_key']] = $sec;
}

// 7 Homepage Sections Definition
$homepageSections = [
    'hero_featured' => [
        'name'          => 'Editorial Spotlight Guide',
        'badge'         => 'HERO (LEFT)',
        'location'      => 'Homepage Hero (Left 58%)',
        'description'   => 'Large featured editorial guide card with high-res cover thumbnail, author avatar chip, views count, and "Read Guide" button.',
        'default_title' => 'Featured Guide',
        'has_inputs'    => false
    ],
    'live_job_radar' => [
        'name'          => 'Live Job Radar & Hiring Alerts',
        'badge'         => 'HERO (RIGHT)',
        'location'      => 'Homepage Hero (Right 42%)',
        'description'   => 'Real-time hiring radar with live blinking pulse dot, featured hiring alert card, and smooth recent guides ticker feed.',
        'default_title' => 'Live Job Radar',
        'has_inputs'    => false
    ],
    'top_categories' => [
        'name'          => 'Browse Top Categories',
        'badge'         => 'SECTION 1',
        'location'      => 'Sector Tracks Grid (Cards)',
        'description'   => 'Interactive career category track cards with custom category SVG icons and live article counts.',
        'default_title' => 'Browse Top Categories',
        'default_sub'   => 'Find the right career path, exam preparation guides, and job opportunities.',
        'has_inputs'    => true
    ],
    'trending_guides' => [
        'name'          => 'Trending Career Guides',
        'badge'         => 'SECTION 2',
        'location'      => '3-Column Card Grid',
        'description'   => 'Trending career roadmaps, exam preparation strategies, and top guidance articles ranked by views.',
        'default_title' => 'Trending Career Guides',
        'default_sub'   => 'Top interview tips, ATS resume frameworks, and career growth roadmaps.',
        'has_inputs'    => true
    ],
    'jobs_section' => [
        'name'          => 'Latest Job Openings',
        'badge'         => 'SECTION 3',
        'location'      => 'Hiring Vacancy Cards',
        'description'   => 'Dedicated job opening cards with company name, location tags, salary badge, and direct view details buttons.',
        'default_title' => 'Latest Job Openings',
        'default_sub'   => 'Verified government, corporate, and tech jobs with direct application details.',
        'has_inputs'    => true
    ],
    'career_toolkit' => [
        'name'          => 'Career Success Toolkit',
        'badge'         => 'SECTION 4',
        'location'      => 'Promotional Green Banner',
        'description'   => 'High-converting dark green toolkit banner featuring ATS Blueprint, STAR method, and Remote Job Guide.',
        'default_title' => 'Career Success Toolkit',
        'default_sub'   => 'Free roadmaps, templates, and frameworks to accelerate your job search.',
        'has_inputs'    => true
    ],
    'latest_articles' => [
        'name'          => 'Latest Articles & News',
        'badge'         => 'SECTION 5',
        'location'      => '3-Column Publications Grid',
        'description'   => 'Latest published articles, recruitment notices, and career publications ordered chronologically.',
        'default_title' => 'Latest Articles & News',
        'default_sub'   => 'Recent career advice, exam schedules, and recruitment updates.',
        'has_inputs'    => true
    ]
];

// Global Website Sections Definition
$globalSections = [
    'header' => [
        'name'        => 'Header & Navigation Bar',
        'badge'       => 'GLOBAL HEADER',
        'location'    => 'Entire Website (Top)',
        'description' => 'Top site header with brand logo, main navigation menu, search bar, and interactive notification bell.'
    ],
    'footer' => [
        'name'        => 'Footer & Trust Badges',
        'badge'       => 'GLOBAL FOOTER',
        'location'    => 'Entire Website (Bottom)',
        'description' => 'Bottom footer with quick links, top categories, legal policy links, trust badges, and copyright notice.'
    ],
    'article_comments' => [
        'name'        => 'Article Comments & Discussion',
        'badge'       => 'ARTICLE PAGES',
        'location'    => 'Article Detail View',
        'description' => 'Public user comments feed and interactive comment submission form on individual article pages.'
    ]
];

// Fetch current menus
$headerMenu = get_header_menu();
$footerMenu = get_footer_menu();

$adminPageTitle = "Home Sections & Menus";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-content-inner" style="max-width:1400px; margin:0 auto; padding-bottom:3rem;">
    <!-- Top Header Breadcrumb & Actions -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
        <div>
            <div style="font-size:0.85rem; color:#64748b; margin-bottom:0.35rem; display:flex; align-items:center; gap:0.4rem;">
                <a href="<?= ADMIN_URL ?>/index.php" style="color:#64748b; text-decoration:none;">Dashboard</a>
                <span>&rsaquo;</span>
                <a href="<?= ADMIN_URL ?>/pages/index.php" style="color:#64748b; text-decoration:none;">Pages</a>
                <span>&rsaquo;</span>
                <span style="color:#0f172a; font-weight:700;">Home Sections &amp; Menus</span>
            </div>
            <h1 style="font-size:1.75rem; font-weight:800; color:#0f172a; margin:0; letter-spacing:-0.02em;">
                Home Sections &amp; Menus
            </h1>
        </div>

        <div>
            <a href="<?= BASE_URL ?>/" target="_blank" class="admin-btn" style="background:#ffffff; border:1px solid #cbd5e1; color:#334155; padding:0.6rem 1.15rem; border-radius:8px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem; box-shadow:0 1px 2px rgba(0,0,0,0.04);">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                <span>View Live Site</span>
            </a>
        </div>
    </div>

    <!-- Main Two-Column Layout (Matching Screenshots 1, 2, 3) -->
    <div style="display:grid; grid-template-columns:minmax(0, 1.45fr) minmax(340px, 1fr); gap:1.75rem; align-items:start;">

        <!-- ================= LEFT COLUMN: Home page sections ================= -->
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:1.75rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <form action="" method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="form_type" value="save_sections">

                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; border-bottom:1px solid #f1f5f9; padding-bottom:1rem;">
                    <div>
                        <h2 style="font-size:1.3rem; font-weight:800; color:#0f172a; margin:0 0 0.35rem 0; display:flex; align-items:center; gap:0.5rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                            Home Page Sections Control
                        </h2>
                        <p style="font-size:0.875rem; color:#64748b; margin:0;">
                            Enable or disable any section of the homepage with instant live toggle or customize section titles.
                        </p>
                    </div>

                    <button type="submit" class="admin-btn" style="background:#2563eb; color:#ffffff; font-weight:700; padding:0.6rem 1.35rem; border-radius:8px; border:none; cursor:pointer; font-size:0.9rem; flex-shrink:0; display:inline-flex; align-items:center; gap:0.4rem; box-shadow:0 2px 6px rgba(37,99,235,0.25);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Save All Sections
                    </button>
                </div>

                <!-- Section Cards Stack -->
                <div style="display:flex; flex-direction:column; gap:1.15rem;">

                    <?php 
                    foreach ($homepageSections as $secKey => $def):
                        $sec = $sectionsByKey[$secKey] ?? [
                            'section_key' => $secKey,
                            'title' => '',
                            'subtitle' => '',
                            'is_enabled' => 1
                        ];
                        $isEnabled = ((int)($sec['is_enabled'] ?? 1) === 1);
                    ?>
                    <!-- Single Section Card -->
                    <div class="admin-section-card" id="card_<?= $secKey ?>" style="background:#ffffff; border:1px solid <?= $isEnabled ? '#cbd5e1' : '#e2e8f0' ?>; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.02); transition:all 0.2s ease;">
                        
                        <!-- Header with Name, Badge and Toggle Switch -->
                        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem; margin-bottom:0.65rem;">
                            <div style="display:flex; align-items:center; gap:0.65rem;">
                                <span style="font-size:0.72rem; font-weight:800; background:#f1f5f9; color:#475569; padding:0.2rem 0.55rem; border-radius:6px; letter-spacing:0.04em;">
                                    <?= $def['badge'] ?>
                                </span>
                                <h3 style="font-size:1rem; font-weight:700; color:#0f172a; margin:0;">
                                    <?= h($def['name']) ?>
                                </h3>
                            </div>

                            <!-- Modern iOS-style Toggle Switch with Live Status Badge -->
                            <div style="display:flex; align-items:center; gap:0.65rem;">
                                <span class="sec-status-badge <?= $isEnabled ? 'status-on' : 'status-off' ?>" id="badge_<?= $secKey ?>">
                                    <?= $isEnabled ? '● Visible / On' : '○ Hidden / Off' ?>
                                </span>
                                <label class="switch-toggle" title="Click to toggle <?= h($def['name']) ?>">
                                    <input type="hidden" name="sections[<?= $secKey ?>][enabled]" value="0">
                                    <input type="checkbox" 
                                           name="sections[<?= $secKey ?>][enabled]" 
                                           value="1" 
                                           id="toggle_<?= $secKey ?>"
                                           <?= $isEnabled ? 'checked' : '' ?> 
                                           data-key="<?= $secKey ?>"
                                           onchange="handleQuickToggle(this)">
                                    <span class="switch-slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Description & Location Details -->
                        <p style="font-size:0.83rem; color:#64748b; margin:0 0 0.85rem 0; line-height:1.45;">
                            <?= h($def['description']) ?>
                        </p>

                        <!-- Optional Title / Subtitle Inputs -->
                        <?php if ($def['has_inputs']): ?>
                        <div style="display:grid; grid-template-columns:1fr 1.3fr; gap:0.75rem; background:#f8fafc; border:1px solid #f1f5f9; border-radius:8px; padding:0.75rem;">
                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:700; color:#475569; margin-bottom:0.25rem;">
                                    Custom Title (Default: <?= h($def['default_title']) ?>)
                                </label>
                                <input type="text" 
                                       name="sections[<?= $secKey ?>][title]" 
                                       value="<?= h($sec['title'] ?? '') ?>" 
                                       placeholder="<?= h($def['default_title']) ?>"
                                       style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.875rem; font-weight:600; color:#0f172a; background:#fff; box-sizing:border-box;">
                            </div>

                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:700; color:#475569; margin-bottom:0.25rem;">
                                    Custom Subtitle / Description
                                </label>
                                <input type="text" 
                                       name="sections[<?= $secKey ?>][subtitle]" 
                                       value="<?= h($sec['subtitle'] ?? '') ?>" 
                                       placeholder="<?= h($def['default_sub'] ?? '') ?>"
                                       style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.875rem; color:#475569; background:#fff; box-sizing:border-box;">
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                    <?php endforeach; ?>

                </div>

                <!-- Global Features (Header, Footer, Comments) Section -->
                <div style="margin-top:2rem; padding-top:1.5rem; border-top:1px solid #e2e8f0;">
                    <h3 style="font-size:1.1rem; font-weight:800; color:#0f172a; margin:0 0 0.35rem 0; display:flex; align-items:center; gap:0.5rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        Global Website Components
                    </h3>
                    <p style="font-size:0.85rem; color:#64748b; margin:0 0 1rem 0;">
                        Control site-wide elements across all pages.
                    </p>

                    <div style="display:flex; flex-direction:column; gap:0.85rem;">
                        <?php foreach ($globalSections as $gKey => $gDef): 
                            $gSec = $sectionsByKey[$gKey] ?? ['is_enabled' => 1];
                            $gIsEnabled = ((int)($gSec['is_enabled'] ?? 1) === 1);
                        ?>
                        <div class="admin-section-card" id="card_<?= $gKey ?>" style="background:#ffffff; border:1px solid <?= $gIsEnabled ? '#cbd5e1' : '#e2e8f0' ?>; border-radius:10px; padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem;">
                            <div>
                                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.2rem;">
                                    <span style="font-size:0.7rem; font-weight:800; background:#f1f5f9; color:#475569; padding:0.15rem 0.45rem; border-radius:4px;">
                                        <?= $gDef['badge'] ?>
                                    </span>
                                    <span style="font-size:0.92rem; font-weight:700; color:#0f172a;">
                                        <?= h($gDef['name']) ?>
                                    </span>
                                </div>
                                <div style="font-size:0.8rem; color:#64748b;">
                                    <?= h($gDef['description']) ?>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap:0.65rem;">
                                <span class="sec-status-badge <?= $gIsEnabled ? 'status-on' : 'status-off' ?>" id="badge_<?= $gKey ?>">
                                    <?= $gIsEnabled ? '● Enabled' : '○ Disabled' ?>
                                </span>
                                <label class="switch-toggle" title="Toggle <?= h($gDef['name']) ?>">
                                    <input type="hidden" name="sections[<?= $gKey ?>][enabled]" value="0">
                                    <input type="checkbox" 
                                           name="sections[<?= $gKey ?>][enabled]" 
                                           value="1" 
                                           id="toggle_<?= $gKey ?>"
                                           <?= $gIsEnabled ? 'checked' : '' ?> 
                                           data-key="<?= $gKey ?>"
                                           onchange="handleQuickToggle(this)">
                                    <span class="switch-slider"></span>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="margin-top:1.5rem; text-align:right;">
                    <button type="submit" class="admin-btn" style="background:#2563eb; color:#ffffff; font-weight:700; padding:0.7rem 1.6rem; border-radius:8px; border:none; cursor:pointer; font-size:0.95rem; box-shadow:0 2px 6px rgba(37,99,235,0.25);">
                        Save All Sections
                    </button>
                </div>
            </form>
        </div>

        <style>
        .switch-toggle {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }
        .switch-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .switch-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .25s ease;
            border-radius: 24px;
        }
        .switch-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .25s ease;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .switch-toggle input:checked + .switch-slider {
            background-color: #10b981;
        }
        .switch-toggle input:checked + .switch-slider:before {
            transform: translateX(20px);
        }
        .sec-status-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            transition: all 0.2s ease;
        }
        .sec-status-badge.status-on {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .sec-status-badge.status-off {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        .quick-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #0f172a;
            color: #ffffff;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 9999;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
        }
        .quick-toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        </style>

        <div id="quickToast" class="quick-toast">Saved!</div>

        <script>
        function handleQuickToggle(input) {
            const key = input.getAttribute('data-key');
            const isChecked = input.checked ? 1 : 0;
            const badge = document.getElementById('badge_' + key);
            const card = document.getElementById('card_' + key);

            // Update badge UI immediately
            if (badge) {
                if (isChecked) {
                    badge.textContent = '● Visible / On';
                    badge.className = 'sec-status-badge status-on';
                    if (card) card.style.borderColor = '#cbd5e1';
                } else {
                    badge.textContent = '○ Hidden / Off';
                    badge.className = 'sec-status-badge status-off';
                    if (card) card.style.borderColor = '#e2e8f0';
                }
            }

            // Send background AJAX to persist instantly
            const formData = new FormData();
            formData.append('csrf_token', '<?= generate_csrf_token() ?>');
            formData.append('form_type', 'toggle_single_section');
            formData.append('section_key', key);
            formData.append('is_enabled', isChecked);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                showQuickToast(isChecked ? `Section '${key}' enabled!` : `Section '${key}' disabled!`);
            })
            .catch(err => {
                console.error(err);
            });
        }

        function showQuickToast(msg) {
            const toast = document.getElementById('quickToast');
            if (toast) {
                toast.textContent = msg;
                toast.classList.add('show');
                clearTimeout(window.__toastTimeout);
                window.__toastTimeout = setTimeout(() => {
                    toast.classList.remove('show');
                }, 2400);
            }
        }
        </script>

        <!-- ================= RIGHT COLUMN: Header & Footer Menus ================= -->
        <div style="display:flex; flex-direction:column; gap:1.75rem;">

            <!-- 1. Header Menu Box (Screenshot 1) -->
            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:1.75rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                <form action="" method="POST" id="formHeaderMenu">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="form_type" value="save_header_menu">

                    <h2 style="font-size:1.15rem; font-weight:800; color:#0f172a; margin:0 0 1rem 0;">
                        Header menu
                    </h2>

                    <!-- Items List -->
                    <div id="headerMenuItemsList" style="display:flex; flex-direction:column; gap:0.75rem; margin-bottom:1rem;">
                        <?php foreach ($headerMenu as $idx => $item): ?>
                        <div class="menu-item-row" style="display:flex; align-items:center; gap:0.5rem;">
                            <!-- Checkbox -->
                            <input type="checkbox" 
                                   name="header_menu[<?= $idx ?>][enabled]" 
                                   value="1" 
                                   <?= !empty($item['enabled']) ? 'checked' : '' ?>
                                   style="width:17px; height:17px; accent-color:#2563eb; cursor:pointer; flex-shrink:0;">

                            <!-- Label Input -->
                            <input type="text" 
                                   name="header_menu[<?= $idx ?>][label]" 
                                   value="<?= h($item['label']) ?>" 
                                   style="flex:1; padding:0.55rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; font-weight:600; color:#0f172a;">

                            <input type="hidden" name="header_menu[<?= $idx ?>][url]" value="<?= h($item['url'] ?? '/') ?>">

                            <!-- Drag Handle Placeholder -->
                            <div style="width:26px; height:34px; border:1px solid #e2e8f0; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#cbd5e1; cursor:grab; flex-shrink:0;">
                                ::
                            </div>

                            <!-- Remove Red Cross Button -->
                            <button type="button" 
                                    onclick="this.closest('.menu-item-row').remove()" 
                                    style="background:none; border:none; color:#ef4444; font-size:1.25rem; line-height:1; cursor:pointer; padding:0 0.25rem; font-weight:700;"
                                    title="Remove item">
                                &times;
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Save Header Menu Button -->
                    <button type="submit" class="admin-btn" style="background:#ffffff; border:1px solid #cbd5e1; color:#0f172a; font-weight:700; padding:0.5rem 1.15rem; border-radius:8px; cursor:pointer; font-size:0.875rem; margin-bottom:1.25rem;">
                        Save header menu
                    </button>
                </form>

                <!-- Add New Item Row -->
                <form action="" method="POST" style="display:flex; align-items:center; gap:0.5rem; border-top:1px solid #f1f5f9; padding-top:1rem;">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="form_type" value="add_header_item">

                    <input type="text" 
                           name="new_label" 
                           placeholder="Label" 
                           required
                           style="flex:1; padding:0.55rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; color:#0f172a;">

                    <input type="text" 
                           name="new_url" 
                           placeholder="/url" 
                           value="/"
                           style="width:90px; padding:0.55rem 0.65rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.85rem; color:#64748b;">

                    <div style="width:26px; height:34px; border:1px solid #e2e8f0; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#cbd5e1; flex-shrink:0;">
                        ::
                    </div>

                    <button type="submit" class="admin-btn" style="background:#4f46e5; color:#ffffff; font-weight:700; padding:0.55rem 1rem; border-radius:8px; border:none; cursor:pointer; font-size:0.875rem;">
                        Add
                    </button>
                </form>
            </div>

            <!-- 2. Footer Menu Box (Screenshot 1) -->
            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:1.75rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                <form action="" method="POST" id="formFooterMenu">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="form_type" value="save_footer_menu">

                    <h2 style="font-size:1.15rem; font-weight:800; color:#0f172a; margin:0 0 1rem 0;">
                        Footer menu
                    </h2>

                    <!-- Items List -->
                    <div id="footerMenuItemsList" style="display:flex; flex-direction:column; gap:0.75rem; margin-bottom:1rem;">
                        <?php foreach ($footerMenu as $fidx => $fitem): ?>
                        <div class="menu-item-row" style="display:flex; align-items:center; gap:0.5rem;">
                            <!-- Checkbox -->
                            <input type="checkbox" 
                                   name="footer_menu[<?= $fidx ?>][enabled]" 
                                   value="1" 
                                   <?= !empty($fitem['enabled']) ? 'checked' : '' ?>
                                   style="width:17px; height:17px; accent-color:#2563eb; cursor:pointer; flex-shrink:0;">

                            <!-- Label Input -->
                            <input type="text" 
                                   name="footer_menu[<?= $fidx ?>][label]" 
                                   value="<?= h($fitem['label']) ?>" 
                                   style="flex:1; padding:0.55rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; font-weight:600; color:#0f172a;">

                            <input type="hidden" name="footer_menu[<?= $fidx ?>][url]" value="<?= h($fitem['url'] ?? '/') ?>">

                            <!-- Drag Handle Placeholder -->
                            <div style="width:26px; height:34px; border:1px solid #e2e8f0; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#cbd5e1; cursor:grab; flex-shrink:0;">
                                ::
                            </div>

                            <!-- Remove Red Cross Button -->
                            <button type="button" 
                                    onclick="this.closest('.menu-item-row').remove()" 
                                    style="background:none; border:none; color:#ef4444; font-size:1.25rem; line-height:1; cursor:pointer; padding:0 0.25rem; font-weight:700;"
                                    title="Remove item">
                                &times;
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Save Footer Menu Button -->
                    <button type="submit" class="admin-btn" style="background:#ffffff; border:1px solid #cbd5e1; color:#0f172a; font-weight:700; padding:0.5rem 1.15rem; border-radius:8px; cursor:pointer; font-size:0.875rem; margin-bottom:1.25rem;">
                        Save footer menu
                    </button>
                </form>

                <!-- Add New Item Row -->
                <form action="" method="POST" style="display:flex; align-items:center; gap:0.5rem; border-top:1px solid #f1f5f9; padding-top:1rem;">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="form_type" value="add_footer_item">

                    <input type="text" 
                           name="new_label" 
                           placeholder="Label" 
                           required
                           style="flex:1; padding:0.55rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; color:#0f172a;">

                    <input type="text" 
                           name="new_url" 
                           placeholder="/url" 
                           value="/"
                           style="width:90px; padding:0.55rem 0.65rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.85rem; color:#64748b;">

                    <button type="submit" class="admin-btn" style="background:#4f46e5; color:#ffffff; font-weight:700; padding:0.55rem 1rem; border-radius:8px; border:none; cursor:pointer; font-size:0.875rem;">
                        Add
                    </button>
                </form>
            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
