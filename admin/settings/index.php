<?php
/**
 * Platform Settings & Visual Branding Console
 * Full control over website name, logo upload, favicon, SEO, and operations.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';

// Authorization Check: Requires settings.view
require_permission('settings.view');

$db = getDBConnection();

$errors = [];
$successMsg = '';

// Handle Settings & Branding Update
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // Backend Check: Requires settings.edit
    if (!is_super_admin() && !has_permission('settings.edit')) {
        require_permission('settings.edit');
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = "Security session expired. Please refresh the page and try again.";
    } else {
        // 1. Process standard text configuration parameters
        $postSettings = $_POST['settings'] ?? [];
        if (is_array($postSettings)) {
            $updStmt = $db->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            foreach ($postSettings as $key => $val) {
                $updStmt->execute([trim($val), $key]);
            }
        }

        // 2. Handle Website Logo File Upload
        if (isset($_FILES['site_logo_file']) && $_FILES['site_logo_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['site_logo_file'];
            $allowedExts = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml', 'image/svg'];
            if (in_array($ext, $allowedExts) && in_array($mime, $allowedMimes)) {
                if ($file['size'] <= 5 * 1024 * 1024) {
                    $destDir = __DIR__ . '/../../uploads/branding/';
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    $filename = 'logo_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                    $destPath = $destDir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $relLogo = 'uploads/branding/' . $filename;
                        $updLogo = $db->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'site_logo'");
                        $updLogo->execute([$relLogo]);

                        // Automatically sync logo asset to secondary portal folder if present
                        $oldDestDir = 'c:/xampp/htdocs/job-portal-old/uploads/branding/';
                        if (is_dir($oldDestDir)) {
                            @copy($destPath, $oldDestDir . $filename);
                        }
                    } else {
                        $errors[] = "Failed to save uploaded logo file to destination storage.";
                    }
                } else {
                    $errors[] = "Logo file exceeds maximum size limit (5MB). Please upload a smaller image.";
                }
            } else {
                $errors[] = "Invalid logo file format. Allowed types: PNG, JPG, WEBP, SVG.";
            }
        } elseif (!empty($_POST['remove_logo'])) {
            $updLogo = $db->prepare("UPDATE settings SET setting_value = '', updated_at = NOW() WHERE setting_key = 'site_logo'");
            $updLogo->execute();
        }

        // 3. Handle Website Favicon File Upload
        if (isset($_FILES['site_favicon_file']) && $_FILES['site_favicon_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['site_favicon_file'];
            $allowedExts = ['ico', 'png', 'svg', 'webp'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/svg+xml', 'image/svg', 'image/webp'];
            if (in_array($ext, $allowedExts) && in_array($mime, $allowedMimes)) {
                if ($file['size'] <= 2 * 1024 * 1024) {
                    $destDir = __DIR__ . '/../../uploads/branding/';
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    $filename = 'favicon_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                    $destPath = $destDir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $relFav = 'uploads/branding/' . $filename;
                        $updFav = $db->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'site_favicon'");
                        $updFav->execute([$relFav]);

                        // Automatically sync favicon asset to secondary portal folder if present
                        $oldDestDir = 'c:/xampp/htdocs/job-portal-old/uploads/branding/';
                        if (is_dir($oldDestDir)) {
                            @copy($destPath, $oldDestDir . $filename);
                        }
                    } else {
                        $errors[] = "Failed to save uploaded favicon file.";
                    }
                } else {
                    $errors[] = "Favicon file exceeds maximum size limit (2MB).";
                }
            } else {
                $errors[] = "Invalid favicon file format. Allowed types: ICO, PNG, SVG.";
            }
        } elseif (!empty($_POST['remove_favicon'])) {
            $updFav = $db->prepare("UPDATE settings SET setting_value = '', updated_at = NOW() WHERE setting_key = 'site_favicon'");
            $updFav->execute();
        }

        if (empty($errors)) {
            // Force refresh cached settings in memory
            get_setting('site_name', '', true);

            log_activity('settings_change', 'settings', "Updated website visual identity, branding, and platform configuration", null);
            $successMsg = "Website branding, name, logo, and configuration settings saved successfully!";
        }
    }
}

$adminPageTitle = "Platform & Branding Settings";
require_once __DIR__ . '/../includes/admin-header.php';

// Reload all settings cleanly from database
$settingsRows = $db->query("SELECT setting_key, setting_value, setting_group FROM settings")->fetchAll(PDO::FETCH_ASSOC);
$st = [];
foreach ($settingsRows as $row) {
    $st[$row['setting_key']] = $row['setting_value'];
}

$currentSiteName     = $st['site_name'] ?? 'CareerNexaa';
$currentTagline      = $st['site_tagline'] ?? '';
$currentLogo         = $st['site_logo'] ?? '';
$currentFavicon      = $st['site_favicon'] ?? '';
$currentFooterText   = $st['site_footer_text'] ?? '';
$currentCopyright    = $st['site_copyright'] ?? '';
$currentEmail        = $st['contact_email'] ?? '';
$currentPerPage      = $st['items_per_page'] ?? '6';
$currentEnableAds    = $st['enable_ads'] ?? '0';
$currentMaintMode    = $st['maintenance_mode'] ?? '0';
$currentGSC          = $st['google_search_console_code'] ?? '';
$currentBing         = $st['bing_webmaster_code'] ?? '';
$currentGA4          = $st['ga4_measurement_id'] ?? '';
$currentTitleTpl     = $st['default_meta_title_template'] ?? '{title} | CareerNexaa';
$currentMetaDesc     = $st['default_meta_description'] ?? '';

// Build logo & favicon absolute or public URLs for preview
$currentLogoUrl = '';
if (!empty($currentLogo)) {
    $currentLogoUrl = (str_starts_with($currentLogo, 'http') || str_starts_with($currentLogo, '/')) ? $currentLogo : BASE_URL . '/' . ltrim($currentLogo, '/');
}

$currentFaviconUrl = '';
if (!empty($currentFavicon)) {
    $currentFaviconUrl = (str_starts_with($currentFavicon, 'http') || str_starts_with($currentFavicon, '/')) ? $currentFavicon : BASE_URL . '/' . ltrim($currentFavicon, '/');
} else {
    $currentFaviconUrl = BASE_URL . '/favicon.ico';
}
?>

<div class="admin-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <h2 style="font-family:var(--admin-heading); font-size:1.6rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem; display:flex; align-items:center; gap:0.6rem;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="var(--admin-primary)" stroke-width="2.2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
            Website Branding & Platform Settings
        </h2>
        <p style="color:#64748b; font-size:0.92rem;">
            Full administrative access to change website brand name, upload/replace logo, customize favicon, and manage site rules.
        </p>
    </div>
    <div style="display:flex; gap:0.5rem; align-items:center;">
        <a href="<?= BASE_URL ?>/" target="_blank" class="admin-btn admin-btn-secondary" style="padding:0.6rem 1.1rem; font-size:0.88rem; display:inline-flex; align-items:center; gap:0.4rem; text-decoration:none; background:#fff; border:1px solid #cbd5e1; border-radius:8px; color:#334155; font-weight:600;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
            View Live Website
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; padding:1.1rem 1.4rem; border-radius:12px; margin-bottom:1.5rem; font-size:0.92rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
    <strong style="display:block; margin-bottom:0.4rem; font-weight:700;">Please correct the following:</strong>
    <ul style="margin:0; padding-left:1.5rem;">
        <?php foreach ($errors as $err): ?>
            <li><?= h($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if (!empty($successMsg)): ?>
<div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#047857; padding:1.1rem 1.4rem; border-radius:12px; margin-bottom:1.5rem; font-size:0.95rem; font-weight:600; display:flex; align-items:center; gap:0.6rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
    <?= h($successMsg) ?>
</div>
<?php endif; ?>

<form action="" method="POST" enctype="multipart/form-data" id="platformSettingsForm">
    <?= csrf_field() ?>

    <div style="display:flex; flex-direction:column; gap:2rem; margin-bottom:2.5rem;">

        <!-- ================================================================= -->
        <!-- CARD 1: WEBSITE IDENTITY & VISUAL BRANDING (NAME, LOGO, FAVICON) -->
        <!-- ================================================================= -->
        <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:14px; padding:2rem; box-shadow:var(--shadow-sm); position:relative; overflow:hidden;">
            <div style="position:absolute; top:0; left:0; width:5px; height:100%; background:linear-gradient(to bottom, #059669, #10b981);"></div>
            
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom:1.75rem; border-bottom:1px solid #f1f5f9; padding-bottom:1rem;">
                <div>
                    <h3 style="font-size:1.25rem; font-weight:800; color:#0f172a; margin:0 0 0.35rem 0; display:flex; align-items:center; gap:0.6rem;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                        Website Identity & Branding (Name, Logo & Favicon)
                    </h3>
                    <p style="font-size:0.88rem; color:#64748b; margin:0;">
                        Manage your website's public brand name, upload an image logo or fallback to stylized text, and customize browser favicon.
                    </p>
                </div>
                <div>
                    <span style="display:inline-flex; align-items:center; gap:0.4rem; background:#ecfdf5; color:#059669; font-size:0.8rem; font-weight:700; padding:0.35rem 0.75rem; border-radius:999px; border:1px solid #a7f3d0;">
                        <span style="width:7px; height:7px; background:#059669; border-radius:50%; display:inline-block;"></span>
                        Live Dynamic Sync Active
                    </span>
                </div>
            </div>

            <!-- Row 1: Site Name & Tagline -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
                <div>
                    <label for="inputSiteName" style="display:block; font-weight:700; font-size:0.9rem; color:#1e293b; margin-bottom:0.4rem;">
                        Website Brand Name <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="text" id="inputSiteName" name="settings[site_name]" value="<?= h($currentSiteName) ?>" required class="admin-form-control" style="width:100%; padding:0.75rem 1rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.95rem; font-weight:600; color:#0f172a;" placeholder="e.g. CareerNexaa" <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                    <span style="font-size:0.8rem; color:#64748b; margin-top:0.35rem; display:block;">
                        Appears across the website header, footer, SEO meta titles, admin header, and transactional notifications.
                    </span>
                    <div style="margin-top:0.5rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:0.4rem 0.75rem; font-size:0.8rem; display:inline-flex; align-items:center; gap:0.5rem;">
                        <span style="color:#64748b;">Text Brand Preview:</span>
                        <strong id="liveTextBrandPreview" style="font-size:0.9rem; font-family:var(--admin-heading);"><?= format_brand_name($currentSiteName) ?></strong>
                    </div>
                </div>

                <div>
                    <label for="inputSiteTagline" style="display:block; font-weight:700; font-size:0.9rem; color:#1e293b; margin-bottom:0.4rem;">
                        Website Tagline / Slogan
                    </label>
                    <input type="text" id="inputSiteTagline" name="settings[site_tagline]" value="<?= h($currentTagline) ?>" class="admin-form-control" style="width:100%; padding:0.75rem 1rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.95rem; color:#0f172a;" placeholder="e.g. Jobs & Career Guidance Hub" <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                    <span style="font-size:0.8rem; color:#64748b; margin-top:0.35rem; display:block;">
                        Appended to homepage title tag and featured in social media sharing previews.
                    </span>
                </div>
            </div>

            <!-- Row 2: Website Logo & Favicon Dropify Upload Modules -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:2rem; margin-bottom:2rem; background:#f8fafc; padding:1.75rem; border-radius:12px; border:1px solid #e2e8f0;">

                <!-- MODULE A: WEBSITE LOGO -->
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                        <label for="siteLogoFile" style="font-weight:800; font-size:0.95rem; color:#0f172a; margin:0; display:flex; align-items:center; gap:0.5rem;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                            Website Logo Image
                        </label>
                        <span style="font-size:0.75rem; background:#e0e7ff; color:#3730a3; padding:0.2rem 0.5rem; border-radius:6px; font-weight:600;">PNG / SVG / WEBP / JPG</span>
                    </div>

                    <!-- Dropify Drag-and-Drop Image Uploader -->
                    <div class="dropify-wrapper-box" style="margin-bottom:0.75rem;">
                        <input 
                            type="file" 
                            id="siteLogoFile" 
                            name="site_logo_file" 
                            class="dropify" 
                            data-height="160" 
                            data-max-file-size="5M" 
                            data-allowed-file-extensions="png jpg jpeg webp svg"
                            <?php if (!empty($currentLogoUrl)): ?>
                            data-default-file="<?= h($currentLogoUrl) ?>"
                            <?php endif; ?>
                            <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>
                        >
                        <input type="hidden" name="remove_logo" id="removeLogoInput" value="0">
                    </div>

                    <span style="font-size:0.75rem; color:#64748b; margin-top:0.3rem; margin-bottom:0.75rem; display:block;">
                        Recommended: Transparent PNG or SVG (height 40px to 60px, width 180px to 300px, max 5MB).
                    </span>

                    <!-- Direct logo URL override (optional) -->
                    <div style="margin-top:0.5rem;">
                        <label for="siteLogoText" style="display:block; font-weight:600; font-size:0.78rem; color:#64748b; margin-bottom:0.25rem;">
                            Or Logo File Path / URL:
                        </label>
                        <input type="text" id="siteLogoText" name="settings[site_logo]" value="<?= h($currentLogo) ?>" placeholder="uploads/branding/logo.png" class="admin-form-control" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #e2e8f0; border-radius:6px; font-size:0.82rem; font-family:monospace; background:#fff;" <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                    </div>
                </div>

                <!-- MODULE B: WEBSITE FAVICON -->
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                        <label for="siteFaviconFile" style="font-weight:800; font-size:0.95rem; color:#0f172a; margin:0; display:flex; align-items:center; gap:0.5rem;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            Browser Favicon
                        </label>
                        <span style="font-size:0.75rem; background:#ecfdf5; color:#047857; padding:0.2rem 0.5rem; border-radius:6px; font-weight:600;">ICO / PNG / SVG</span>
                    </div>

                    <!-- Dropify Drag-and-Drop Image Uploader -->
                    <div class="dropify-wrapper-box" style="margin-bottom:0.75rem;">
                        <input 
                            type="file" 
                            id="siteFaviconFile" 
                            name="site_favicon_file" 
                            class="dropify" 
                            data-height="160" 
                            data-max-file-size="2M" 
                            data-allowed-file-extensions="ico png svg webp"
                            <?php if (!empty($currentFavicon) && !empty($currentFaviconUrl)): ?>
                            data-default-file="<?= h($currentFaviconUrl) ?>"
                            <?php endif; ?>
                            <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>
                        >
                        <input type="hidden" name="remove_favicon" id="removeFaviconInput" value="0">
                    </div>

                    <span style="font-size:0.75rem; color:#64748b; margin-top:0.3rem; margin-bottom:0.75rem; display:block;">
                        Supported formats: .ico, .png, .svg (Recommended: 32x32px or 48x48px square, max 2MB).
                    </span>

                    <!-- Direct favicon path override (optional) -->
                    <div style="margin-top:0.5rem;">
                        <label for="siteFaviconText" style="display:block; font-weight:600; font-size:0.78rem; color:#64748b; margin-bottom:0.25rem;">
                            Or Favicon File Path / URL:
                        </label>
                        <input type="text" id="siteFaviconText" name="settings[site_favicon]" value="<?= h($currentFavicon) ?>" placeholder="uploads/branding/favicon.png" class="admin-form-control" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #e2e8f0; border-radius:6px; font-size:0.82rem; font-family:monospace; background:#fff;" <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                    </div>
                </div>

            </div>

            <!-- Row 3: Footer About Text & Copyright Notice -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.5rem;">
                <div>
                    <label for="inputFooterText" style="display:block; font-weight:700; font-size:0.88rem; color:#1e293b; margin-bottom:0.35rem;">
                        Footer Brand Description / About Snippet
                    </label>
                    <textarea id="inputFooterText" name="settings[site_footer_text]" rows="2" class="admin-form-control" style="width:100%; padding:0.7rem 0.9rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; resize:vertical;" placeholder="Brief mission statement shown in the website footer..." <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>><?= h($currentFooterText) ?></textarea>
                    <span style="font-size:0.78rem; color:#64748b; margin-top:0.25rem; display:block;">
                        Displayed under the logo in Column 1 of the public website footer.
                    </span>
                </div>

                <div>
                    <label for="inputCopyright" style="display:block; font-weight:700; font-size:0.88rem; color:#1e293b; margin-bottom:0.35rem;">
                        Footer Copyright Notice
                    </label>
                    <input type="text" id="inputCopyright" name="settings[site_copyright]" value="<?= h($currentCopyright) ?>" class="admin-form-control" style="width:100%; padding:0.7rem 0.9rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" placeholder="e.g. &copy; 2026 CareerNexaa. All rights reserved." <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                    <span style="font-size:0.78rem; color:#64748b; margin-top:0.25rem; display:block;">
                        Appears at the very bottom bar of every page on the website.
                    </span>
                </div>
            </div>

        </div>

        <!-- ================================================================= -->
        <!-- CARD 2: GENERAL & OPERATIONAL CONFIGURATION                       -->
        <!-- ================================================================= -->
        <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:14px; padding:2rem; box-shadow:var(--shadow-sm);">
            <h3 style="font-size:1.15rem; font-weight:800; color:#0f172a; margin:0 0 1.25rem 0; border-bottom:1px solid #f1f5f9; padding-bottom:0.75rem; display:flex; align-items:center; gap:0.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                General & Operational Parameters
            </h3>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:1.5rem;">
                <div>
                    <label for="inputContactEmail" style="display:block; font-weight:700; font-size:0.88rem; color:#334155; margin-bottom:0.35rem;">
                        Contact & Support Email
                    </label>
                    <input type="email" id="inputContactEmail" name="settings[contact_email]" value="<?= h($currentEmail) ?>" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" placeholder="support@careernexaa.com" <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                    <span style="font-size:0.75rem; color:#64748b; margin-top:0.25rem; display:block;">
                        Used for visitor inquiries sent from the contact form and public footer.
                    </span>
                </div>

                <div>
                    <label for="inputPerPage" style="display:block; font-weight:700; font-size:0.88rem; color:#334155; margin-bottom:0.35rem;">
                        Articles Per Page (Pagination)
                    </label>
                    <input type="number" min="1" max="50" id="inputPerPage" name="settings[items_per_page]" value="<?= h($currentPerPage) ?>" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                    <span style="font-size:0.75rem; color:#64748b; margin-top:0.25rem; display:block;">
                        Number of article cards shown per page on the job listings archive.
                    </span>
                </div>

                <div>
                    <label for="selectAds" style="display:block; font-weight:700; font-size:0.88rem; color:#334155; margin-bottom:0.35rem;">
                        Monetization / Ad Slots
                    </label>
                    <select id="selectAds" name="settings[enable_ads]" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                        <option value="0" <?= $currentEnableAds == '0' ? 'selected' : '' ?>>Disabled / No Advertisements</option>
                        <option value="1" <?= $currentEnableAds == '1' ? 'selected' : '' ?>>Enabled (Ad Banners Active)</option>
                    </select>
                    <span style="font-size:0.75rem; color:#64748b; margin-top:0.25rem; display:block;">
                        Enable or disable display advertising scripts across public templates.
                    </span>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- CARD 3: SEO, SEARCH CONSOLE & ANALYTICS INTEGRATIONS              -->
        <!-- ================================================================= -->
        <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:14px; padding:2rem; box-shadow:var(--shadow-sm);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; border-bottom:1px solid #f1f5f9; padding-bottom:0.75rem; flex-wrap:wrap; gap:0.5rem;">
                <h3 style="font-size:1.15rem; font-weight:800; color:#0f172a; margin:0; display:flex; align-items:center; gap:0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Search Engine Optimization (SEO) & Webmaster Tags
                </h3>
                <a href="<?= ADMIN_URL ?>/seo.php" style="font-size:0.82rem; color:#059669; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:0.3rem;">
                    Advanced SEO Suite &rarr;
                </a>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.5rem; margin-bottom:1.5rem;">
                <div>
                    <label for="inputGSC" style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                        Google Search Console Meta Verification Tag
                    </label>
                    <input type="text" id="inputGSC" name="settings[google_search_console_code]" value="<?= h($currentGSC) ?>" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.85rem; font-family:monospace;" placeholder="google-site-verification=..." <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                    <span style="font-size:0.75rem; color:#64748b; margin-top:0.25rem; display:block;">
                        Enter either full meta tag or verification token. Automatically embedded in &lt;head&gt;.
                    </span>
                </div>

                <div>
                    <label for="inputBing" style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                        Bing Webmaster Tools Auth Tag
                    </label>
                    <input type="text" id="inputBing" name="settings[bing_webmaster_code]" value="<?= h($currentBing) ?>" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.85rem; font-family:monospace;" placeholder="msvalidate.01=..." <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                    <span style="font-size:0.75rem; color:#64748b; margin-top:0.25rem; display:block;">
                        Verification code for Microsoft Bing and Yahoo crawler indexing.
                    </span>
                </div>

                <div>
                    <label for="inputGA4" style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                        Google Analytics 4 (GA4) Measurement ID
                    </label>
                    <input type="text" id="inputGA4" name="settings[ga4_measurement_id]" value="<?= h($currentGA4) ?>" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.85rem; font-family:monospace;" placeholder="G-XXXXXXXXXX" <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                    <span style="font-size:0.75rem; color:#64748b; margin-top:0.25rem; display:block;">
                        Real-time analytics tracking code for traffic analysis and user events.
                    </span>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.5rem;">
                <div>
                    <label for="inputTitleTpl" style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                        Default Meta Title Template
                    </label>
                    <input type="text" id="inputTitleTpl" name="settings[default_meta_title_template]" value="<?= h($currentTitleTpl) ?>" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" placeholder="{title} | CareerNexaa" <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                    <span style="font-size:0.75rem; color:#64748b; margin-top:0.25rem; display:block;">
                        Available variable: <code>{title}</code>. Will be replaced with individual post/page title.
                    </span>
                </div>

                <div>
                    <label for="inputMetaDesc" style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                        Default Meta Description Fallback
                    </label>
                    <textarea id="inputMetaDesc" name="settings[default_meta_description]" rows="2" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.88rem; resize:vertical;" placeholder="Discover verified jobs and career guides..." <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>><?= h($currentMetaDesc) ?></textarea>
                    <span style="font-size:0.75rem; color:#64748b; margin-top:0.25rem; display:block;">
                        Used on general portal pages when page-specific meta description is missing.
                    </span>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- CARD 4: SYSTEM & OPERATIONAL STATUS CONTROLS                      -->
        <!-- ================================================================= -->
        <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:14px; padding:2rem; box-shadow:var(--shadow-sm);">
            <h3 style="font-size:1.15rem; font-weight:800; color:#0f172a; margin:0 0 1.25rem 0; border-bottom:1px solid #f1f5f9; padding-bottom:0.75rem; display:flex; align-items:center; gap:0.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                System Maintenance & Service Mode
            </h3>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.5rem; align-items:center;">
                <div>
                    <label for="selectMaint" style="display:block; font-weight:700; font-size:0.88rem; color:#334155; margin-bottom:0.35rem;">
                        Maintenance Mode
                    </label>
                    <select id="selectMaint" name="settings[maintenance_mode]" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                        <option value="0" <?= $currentMaintMode == '0' ? 'selected' : '' ?>>Disabled — Website is Live to Public</option>
                        <option value="1" <?= $currentMaintMode == '1' ? 'selected' : '' ?>>Enabled — Show "Under Maintenance" Splash Page</option>
                    </select>
                    <span style="font-size:0.75rem; color:#64748b; margin-top:0.25rem; display:block;">
                        When active, only authenticated administrators can view website content; general visitors see a maintenance notice.
                    </span>
                </div>

                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem; font-size:0.85rem; color:#475569;">
                    <div style="font-weight:700; color:#0f172a; margin-bottom:0.25rem; display:flex; align-items:center; gap:0.4rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        Security & Storage Notice
                    </div>
                    Uploaded branding assets are saved to <code>uploads/branding/</code> with strict MIME validation. Text changes update instantly across all active templates.
                </div>
            </div>
        </div>

    </div>

    <!-- Sticky Bottom Actions Bar -->
    <?php if (is_super_admin() || has_permission('settings.edit')): ?>
    <div style="position:sticky; bottom:1rem; background:rgba(255, 255, 255, 0.95); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); border:1px solid var(--admin-border); border-radius:12px; padding:1rem 1.5rem; display:flex; justify-content:space-between; align-items:center; box-shadow:0 10px 25px -5px rgba(0,0,0,0.1); z-index:90;">
        <div style="font-size:0.85rem; color:#64748b;">
            Make sure to review changes before saving.
        </div>
        <div style="display:flex; gap:0.75rem;">
            <button type="submit" class="admin-btn admin-btn-primary" style="padding:0.75rem 2.25rem; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:0.6rem; font-size:0.95rem; box-shadow:0 4px 6px -1px rgba(5, 150, 105, 0.2);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Save All Changes & Update Branding
            </button>
        </div>
    </div>
    <?php endif; ?>
</form>

<script>
// Live preview for Brand Name input
document.getElementById('inputSiteName')?.addEventListener('input', function(e) {
    const val = e.target.value.trim() || 'CareerNexaa';
    
    // Update live text brand helper
    const textPreview = document.getElementById('liveTextBrandPreview');
    if (textPreview) {
        const half = Math.max(1, Math.floor(val.length / 2));
        textPreview.innerHTML = escapeHtml(val.substring(0, half)) + '<span style="color:#059669;">' + escapeHtml(val.substring(half)) + '</span>';
    }
});

function escapeHtml(text) {
    return text.replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

<script>
// Ensure Dropify instances are bound cleanly on the branding inputs
$(document).ready(function() {
    if (typeof $.fn.dropify !== 'undefined') {
        if (!$('#siteLogoFile').data('dropify')) {
            const drLogo = $('#siteLogoFile').dropify({
                messages: {
                    'default': 'Drag & drop website logo here or click to browse',
                    'replace': 'Drag & drop or click to replace logo',
                    'remove':  'Remove Logo',
                    'error':   'Oops, something went wrong with the file.'
                },
                error: {
                    'fileSize': 'The file size is too big ({{ value }} max).',
                    'fileExtension': 'The image format is not allowed ({{ value }} only).'
                }
            });

            drLogo.on('dropify.afterClear', function() {
                $('#removeLogoInput').val('1');
                $('#siteLogoText').val('');
            });
        }

        if (!$('#siteFaviconFile').data('dropify')) {
            const drFav = $('#siteFaviconFile').dropify({
                messages: {
                    'default': 'Drag & drop browser favicon or click to browse',
                    'replace': 'Drag & drop or click to replace favicon',
                    'remove':  'Remove Favicon',
                    'error':   'Oops, something went wrong with the file.'
                },
                error: {
                    'fileSize': 'The file size is too big ({{ value }} max).',
                    'fileExtension': 'The image format is not allowed ({{ value }} only).'
                }
            });

            drFav.on('dropify.afterClear', function() {
                $('#removeFaviconInput').val('1');
                $('#siteFaviconText').val('');
            });
        }
    }
});
</script>
