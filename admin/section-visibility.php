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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('error', 'Security token expired. Please refresh the page and try again.');
        header('Location: ' . ADMIN_URL . '/section-visibility.php');
        exit;
    }

    $formType = $_POST['form_type'] ?? '';

    // 1. Save Home Page Sections
    if ($formType === 'save_sections') {
        $sectionsInput = $_POST['sections'] ?? [];
        if (is_array($sectionsInput)) {
            foreach ($sectionsInput as $key => $secData) {
                $isEnabled = isset($secData['enabled']) ? 1 : 0;
                $title = trim($secData['title'] ?? '');
                $subtitle = trim($secData['subtitle'] ?? '');
                $limit = isset($secData['limit']) && is_numeric($secData['limit']) ? (int)$secData['limit'] : 6;
                $catId = !empty($secData['category_id']) && is_numeric($secData['category_id']) ? (int)$secData['category_id'] : null;

                $upStmt = $db->prepare("
                    UPDATE section_visibility 
                    SET is_enabled = ?, title = ?, subtitle = ?, item_limit = ?, category_id = ?, updated_at = NOW()
                    WHERE section_key = ?
                ");
                $upStmt->execute([$isEnabled, $title, $subtitle, $limit, $catId, $key]);
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

// Fetch home sections
$homeSections = $db->query("
    SELECT * FROM section_visibility 
    WHERE section_key IN ('hero', 'latest', 'categories', 'popular', 'articles', 'recommended', 'notification', 'newsletter')
    ORDER BY sort_order ASC, id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fallback index by key for easy lookup
$sectionsByKey = [];
foreach ($homeSections as $sec) {
    $sectionsByKey[$sec['section_key']] = $sec;
}

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

                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.25rem;">
                    <div>
                        <h2 style="font-size:1.25rem; font-weight:800; color:#0f172a; margin:0 0 0.35rem 0;">
                            Home page sections
                        </h2>
                        <p style="font-size:0.875rem; color:#64748b; margin:0;">
                            Drag to reorder. Each section can be enabled/disabled and limited; "Latest" and "Popular" can be pinned to a category.
                        </p>
                    </div>

                    <button type="submit" class="admin-btn" style="background:#4f46e5; color:#ffffff; font-weight:700; padding:0.6rem 1.35rem; border-radius:8px; border:none; cursor:pointer; font-size:0.9rem; flex-shrink:0;">
                        Save sections
                    </button>
                </div>

                <!-- Section Cards Stack -->
                <div style="display:flex; flex-direction:column; gap:1.25rem;">

                    <?php 
                    $sectionOrderKeys = ['hero', 'latest', 'categories', 'popular', 'articles', 'recommended', 'notification'];
                    foreach ($sectionOrderKeys as $secKey):
                        $sec = $sectionsByKey[$secKey] ?? ($secKey === 'notification' ? ($sectionsByKey['newsletter'] ?? null) : null) ?? [
                            'section_key' => $secKey,
                            'section_name' => strtoupper($secKey),
                            'title' => ($secKey === 'notification') ? 'Instant Job & Career Notifications' : ucfirst($secKey),
                            'subtitle' => ($secKey === 'notification') ? 'Turn on alerts to receive verified hiring, career guides & breaking exam updates directly on your device.' : '',
                            'item_limit' => 6,
                            'category_id' => null,
                            'is_enabled' => 1
                        ];
                        $isEnabled = ((int)($sec['is_enabled'] ?? 1) === 1);
                    ?>
                    <!-- Single Section Card -->
                    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:1.25rem; box-shadow:0 1px 2px rgba(0,0,0,0.02);">
                        <!-- Header with Checkbox and Section Tag -->
                        <div style="display:flex; align-items:center; gap:0.65rem; margin-bottom:1rem;">
                            <!-- Drag Handle -->
                            <span style="color:#cbd5e1; cursor:grab; font-size:1.1rem; line-height:1; user-select:none;">:::</span>

                            <label style="display:inline-flex; align-items:center; gap:0.45rem; cursor:pointer; font-size:0.9rem; font-weight:700; color:#0f172a;">
                                <input type="checkbox" name="sections[<?= $secKey ?>][enabled]" value="1" <?= $isEnabled ? 'checked' : '' ?> style="width:17px; height:17px; accent-color:#2563eb; cursor:pointer;">
                                <span>On</span>
                            </label>

                            <span style="font-size:0.8rem; font-weight:800; color:#64748b; letter-spacing:0.04em;">
                                <?= strtoupper($sec['section_name'] ?? $secKey) ?>
                            </span>
                        </div>

                        <!-- Card Inputs -->
                        <div style="display:flex; flex-direction:column; gap:0.65rem;">
                            <!-- Title Input -->
                            <input type="text" 
                                   name="sections[<?= $secKey ?>][title]" 
                                   value="<?= h($sec['title'] ?? '') ?>" 
                                   placeholder="Section Title"
                                   style="width:100%; padding:0.6rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.95rem; font-weight:600; color:#0f172a; box-sizing:border-box;">

                            <!-- Subtitle Input -->
                            <input type="text" 
                                   name="sections[<?= $secKey ?>][subtitle]" 
                                   value="<?= h($sec['subtitle'] ?? '') ?>" 
                                   placeholder="Section Subtitle / Description"
                                   style="width:100%; padding:0.6rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; color:#475569; box-sizing:border-box;">

                            <?php if (!in_array($secKey, ['newsletter', 'notification'])): ?>
                            <!-- Limit Input -->
                            <input type="number" 
                                   name="sections[<?= $secKey ?>][limit]" 
                                   value="<?= (int)($sec['item_limit'] ?? 6) ?>" 
                                   min="1" max="50"
                                   placeholder="Item Limit"
                                   style="width:100%; padding:0.6rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; color:#0f172a; box-sizing:border-box;">
                            <?php endif; ?>

                            <?php if (in_array($secKey, ['latest', 'popular'])): ?>
                            <!-- Category Dropdown for Latest & Popular -->
                            <select name="sections[<?= $secKey ?>][category_id]" 
                                    style="width:100%; padding:0.6rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; color:#334155; background:#fff; box-sizing:border-box;">
                                <option value="">Any category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ((int)($sec['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                                        <?= h($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>

                <div style="margin-top:1.5rem; text-align:right;">
                    <button type="submit" class="admin-btn" style="background:#4f46e5; color:#ffffff; font-weight:700; padding:0.65rem 1.5rem; border-radius:8px; border:none; cursor:pointer; font-size:0.95rem;">
                        Save sections
                    </button>
                </div>
            </form>
        </div>

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
