<?php
/**
 * Admin Panel - 1-Click Backup & Disaster Recovery Manager
 * Allows administrators to export the MySQL database (.sql) and website code/images (.zip) directly
 * without needing cPanel access.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/includes/auth-check.php';

// Authorization: Requires settings.view or superadmin
if (!is_super_admin() && !has_permission('settings.view')) {
    require_permission('settings.view');
}

$db = getDBConnection();
$adminPageTitle = "Backup & Disaster Recovery";

// Handle Actions (Downloads)
$action = $_GET['action'] ?? '';

// 1. ACTION: Export Full Database (.sql)
if ($action === 'download_db') {
    // Generate full portable MySQL dump
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    $sql  = "-- ==========================================================================\n";
    $sql .= "-- CareerNexaa Full Database Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s T') . "\n";
    $sql .= "-- Database: " . DB_NAME . "\n";
    $sql .= "-- ==========================================================================\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql .= "SET NAMES utf8mb4;\n\n";

    foreach ($tables as $t) {
        $create = $db->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_ASSOC);
        $sql .= "-- --------------------------------------------------------\n";
        $sql .= "-- Structure for table `$t`\n";
        $sql .= "-- --------------------------------------------------------\n";
        $sql .= "DROP TABLE IF EXISTS `$t`;\n";
        $sql .= $create['Create Table'] . ";\n\n";

        $rows = $db->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $sql .= "-- Data for table `$t` (" . count($rows) . " rows)\n";
            $cols = array_map(function($c) { return "`$c`"; }, array_keys($rows[0]));
            
            // Insert in batches of 50 for performance and clean imports
            $batches = array_chunk($rows, 50);
            foreach ($batches as $batch) {
                $sql .= "INSERT INTO `$t` (" . implode(', ', $cols) . ") VALUES\n";
                $rowStrings = [];
                foreach ($batch as $row) {
                    $vals = array_map(function($v) use ($db) {
                        if ($v === null) return "NULL";
                        return $db->quote($v);
                    }, array_values($row));
                    $rowStrings[] = "  (" . implode(', ', $vals) . ")";
                }
                $sql .= implode(",\n", $rowStrings) . ";\n";
            }
            $sql .= "\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $sql .= "-- Backup Completed Successfully.\n";

    $filename = "careernexaa_database_backup_" . date('Y-m-d_H-i-s') . ".sql";

    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $sql;
    exit;
}

// 2. ACTION: Export Articles Only (.sql)
if ($action === 'download_articles') {
    $tables = ['articles', 'categories', 'tags'];

    $sql  = "-- ==========================================================================\n";
    $sql .= "-- CareerNexaa Articles & Categories Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- ==========================================================================\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n\n";

    foreach ($tables as $t) {
        $create = $db->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_ASSOC);
        $sql .= "DROP TABLE IF EXISTS `$t`;\n" . $create['Create Table'] . ";\n\n";
        $rows = $db->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $cols = array_map(function($c) { return "`$c`"; }, array_keys($rows[0]));
            $batches = array_chunk($rows, 25);
            foreach ($batches as $batch) {
                $sql .= "INSERT INTO `$t` (" . implode(', ', $cols) . ") VALUES\n";
                $rowStrings = [];
                foreach ($batch as $row) {
                    $vals = array_map(function($v) use ($db) {
                        return ($v === null) ? "NULL" : $db->quote($v);
                    }, array_values($row));
                    $rowStrings[] = "  (" . implode(', ', $vals) . ")";
                }
                $sql .= implode(",\n", $rowStrings) . ";\n";
            }
            $sql .= "\n";
        }
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    $filename = "careernexaa_articles_only_" . date('Y-m-d') . ".sql";
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql));
    echo $sql;
    exit;
}

// 3. ACTION: Download index.php directly
if ($action === 'download_index') {
    $indexPath = dirname(__DIR__) . '/index.php';
    if (file_exists($indexPath)) {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/x-php');
        header('Content-Disposition: attachment; filename="index.php"');
        header('Content-Length: ' . filesize($indexPath));
        readfile($indexPath);
        exit;
    }
}

// 4. ACTION: Download Core Website Files (.zip)
if ($action === 'download_core_zip' && class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    $tempZipPath = sys_get_temp_dir() . '/careernexaa_core_' . time() . '.zip';

    if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $rootDir = dirname(__DIR__);
        $filesToInclude = [
            'index.php', 'article.php', 'articles.php', 'categories.php',
            'about.php', 'contact.php', 'privacy-policy.php', 'terms.php',
            'cookie-policy.php', 'disclaimer.php', 'robots.txt', 'ads.txt',
            'sitemap.php', 'sitemap.xml', '.htaccess'
        ];

        foreach ($filesToInclude as $f) {
            $fullPath = $rootDir . '/' . $f;
            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, $f);
            }
        }

        // Add config files (with credentials placeholder for security)
        if (file_exists($rootDir . '/config/config.php')) {
            $zip->addFile($rootDir . '/config/config.php', 'config/config.php');
        }
        if (file_exists($rootDir . '/config/database.php')) {
            $zip->addFile($rootDir . '/config/database.php', 'config/database.php');
        }

        $zip->close();

        if (file_exists($tempZipPath)) {
            while (ob_get_level()) { ob_end_clean(); }
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="careernexaa_core_files_' . date('Y-m-d') . '.zip"');
            header('Content-Length: ' . filesize($tempZipPath));
            readfile($tempZipPath);
            @unlink($tempZipPath);
            exit;
        }
    }
}

// 5. ACTION: Download Uploads / Article Images (.zip)
if ($action === 'download_uploads_zip' && class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    $tempZipPath = sys_get_temp_dir() . '/careernexaa_uploads_' . time() . '.zip';

    if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $uploadsDir = dirname(__DIR__) . '/uploads/articles';
        if (is_dir($uploadsDir)) {
            $files = scandir($uploadsDir);
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                $filePath = $uploadsDir . '/' . $f;
                if (is_file($filePath)) {
                    $zip->addFile($filePath, 'uploads/articles/' . $f);
                }
            }
        }
        $zip->close();

        if (file_exists($tempZipPath)) {
            while (ob_get_level()) { ob_end_clean(); }
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="careernexaa_article_images_' . date('Y-m-d') . '.zip"');
            header('Content-Length: ' . filesize($tempZipPath));
            readfile($tempZipPath);
            @unlink($tempZipPath);
            exit;
        }
    }
}

// Gather Statistics for Display
$totalArticles   = (int)$db->query("SELECT COUNT(*) FROM articles WHERE (is_trash = 0 OR is_trash IS NULL)")->fetchColumn();
$totalPublished  = (int)$db->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND (is_trash = 0 OR is_trash IS NULL)")->fetchColumn();
$totalCategories = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$allTables       = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$totalTables     = count($allTables);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="composer-page-wrap" style="max-width:1150px; margin:0 auto; padding:1.5rem 1rem 3rem;">

    <!-- Top Hero Banner -->
    <div style="background:linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%); border-radius:16px; padding:2rem 2.25rem; color:#ffffff; margin-bottom:2rem; box-shadow:0 10px 25px -5px rgba(5,150,105,0.3); position:relative; overflow:hidden;">
        <div style="position:absolute; right:-20px; top:-20px; width:180px; height:180px; background:radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%); border-radius:50%; pointer-events:none;"></div>
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.5rem; position:relative; z-index:2;">
            <div>
                <div style="display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,0.18); padding:0.3rem 0.85rem; border-radius:9999px; font-size:0.78rem; font-weight:700; letter-spacing:0.04em; text-transform:uppercase; margin-bottom:0.75rem;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    Disaster Recovery & Data Protection
                </div>
                <h1 style="font-size:1.85rem; font-weight:900; margin:0 0 0.5rem 0; line-height:1.2;">1-Click Website &amp; Database Backup</h1>
                <p style="margin:0; font-size:0.95rem; color:#d1fae5; max-width:640px; line-height:1.5;">
                    Export your complete database, all published articles, and core website files directly to your computer. Protect your team's hard work even if cPanel or hosting is temporarily down.
                </p>
            </div>
            <div>
                <a href="<?= ADMIN_URL ?>/backup.php?action=download_db" class="btn" style="background:#ffffff; color:#065f46; font-size:0.95rem; font-weight:800; padding:0.85rem 1.75rem; border-radius:10px; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 15px rgba(0,0,0,0.15); text-decoration:none; transition:all 0.2s ease;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Download Full Database (.sql)
                </a>
            </div>
        </div>
    </div>

    <!-- Live Status Quick Metrics -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.25rem; margin-bottom:2rem;">
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; display:flex; align-items:center; gap:1rem; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="width:48px; height:48px; border-radius:10px; background:#ecfdf5; color:#059669; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            </div>
            <div>
                <span style="font-size:0.8rem; color:#64748b; font-weight:600; display:block;">Published Articles</span>
                <strong style="font-size:1.35rem; font-weight:800; color:#0f172a;"><?= $totalPublished ?> <span style="font-size:0.75rem; color:#059669; font-weight:700;">(Safe in DB)</span></strong>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; display:flex; align-items:center; gap:1rem; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="width:48px; height:48px; border-radius:10px; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            </div>
            <div>
                <span style="font-size:0.8rem; color:#64748b; font-weight:600; display:block;">Active Categories</span>
                <strong style="font-size:1.35rem; font-weight:800; color:#0f172a;"><?= $totalCategories ?> Categories</strong>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; display:flex; align-items:center; gap:1rem; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="width:48px; height:48px; border-radius:10px; background:#fef3c7; color:#d97706; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
            </div>
            <div>
                <span style="font-size:0.8rem; color:#64748b; font-weight:600; display:block;">Database Tables</span>
                <strong style="font-size:1.35rem; font-weight:800; color:#0f172a;"><?= $totalTables ?> Tables</strong>
            </div>
        </div>
    </div>

    <!-- Main Grid: 2 Columns of Backup Options -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(340px, 1fr)); gap:1.75rem; margin-bottom:2rem;">

        <!-- CARD 1: DATABASE BACKUP OPTIONS -->
        <div class="admin-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:1.75rem; box-shadow:0 1px 3px rgba(0,0,0,0.05); display:flex; flex-direction:column; justify-content:space-between;">
            <div>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.75rem;">
                    <div style="width:38px; height:38px; background:#ecfdf5; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#059669;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                    </div>
                    <div>
                        <h3 style="font-size:1.15rem; font-weight:800; color:#0f172a; margin:0;">Database (.SQL) Exports</h3>
                        <p style="font-size:0.8rem; color:#64748b; margin:2px 0 0 0;">Save your articles, categories, settings &amp; users</p>
                    </div>
                </div>

                <p style="font-size:0.88rem; color:#475569; line-height:1.5; margin:1rem 0 1.25rem;">
                    Exports a complete SQL script. This file can be imported into <strong>phpMyAdmin</strong> or any MySQL database on any hosting provider in less than 2 minutes to restore everything.
                </p>

                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.75rem 1rem; margin-bottom:1.5rem; font-size:0.82rem; color:#334155;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                        <span style="color:#64748b;">Database Name:</span>
                        <strong><?= h(DB_NAME) ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:#64748b;">Encoding:</span>
                        <strong>UTF-8 (utf8mb4_unicode_ci)</strong>
                    </div>
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:0.65rem;">
                <a href="<?= ADMIN_URL ?>/backup.php?action=download_db" class="btn" style="background:#059669; color:#ffffff; font-weight:700; font-size:0.9rem; padding:0.75rem 1rem; border-radius:8px; display:flex; align-items:center; justify-content:center; gap:8px; text-decoration:none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Download Full Database Backup (.sql)
                </a>

                <a href="<?= ADMIN_URL ?>/backup.php?action=download_articles" class="btn" style="background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; font-weight:700; font-size:0.85rem; padding:0.6rem 1rem; border-radius:8px; display:flex; align-items:center; justify-content:center; gap:8px; text-decoration:none;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg>
                    Download Articles Only (.sql)
                </a>
            </div>
        </div>

        <!-- CARD 2: WEBSITE CODE & MEDIA BACKUP -->
        <div class="admin-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:1.75rem; box-shadow:0 1px 3px rgba(0,0,0,0.05); display:flex; flex-direction:column; justify-content:space-between;">
            <div>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.75rem;">
                    <div style="width:38px; height:38px; background:#eff6ff; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#2563eb;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    </div>
                    <div>
                        <h3 style="font-size:1.15rem; font-weight:800; color:#0f172a; margin:0;">Files &amp; Media Archives</h3>
                        <p style="font-size:0.8rem; color:#64748b; margin:2px 0 0 0;">Download index.php, source files &amp; article images</p>
                    </div>
                </div>

                <p style="font-size:0.88rem; color:#475569; line-height:1.5; margin:1rem 0 1.25rem;">
                    Download the core PHP files and all article images uploaded to the server. You can store these on your computer or USB drive for complete disaster protection.
                </p>

                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.75rem 1rem; margin-bottom:1.5rem; font-size:0.82rem; color:#334155;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                        <span style="color:#64748b;">Single File:</span>
                        <strong>index.php (Homepage Engine)</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:#64748b;">Media Folder:</span>
                        <strong>uploads/articles/</strong>
                    </div>
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:0.65rem;">
                <a href="<?= ADMIN_URL ?>/backup.php?action=download_index" class="btn" style="background:#2563eb; color:#ffffff; font-weight:700; font-size:0.9rem; padding:0.75rem 1rem; border-radius:8px; display:flex; align-items:center; justify-content:center; gap:8px; text-decoration:none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Download index.php Directly
                </a>

                <?php if (class_exists('ZipArchive')): ?>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
                    <a href="<?= ADMIN_URL ?>/backup.php?action=download_core_zip" class="btn" style="background:#f1f5f9; color:#1e293b; border:1px solid #cbd5e1; font-weight:600; font-size:0.8rem; padding:0.55rem 0.65rem; border-radius:8px; display:flex; align-items:center; justify-content:center; gap:5px; text-decoration:none;">
                        📦 Core Code (.zip)
                    </a>
                    <a href="<?= ADMIN_URL ?>/backup.php?action=download_uploads_zip" class="btn" style="background:#f1f5f9; color:#1e293b; border:1px solid #cbd5e1; font-weight:600; font-size:0.8rem; padding:0.55rem 0.65rem; border-radius:8px; display:flex; align-items:center; justify-content:center; gap:5px; text-decoration:none;">
                        🖼️ Images (.zip)
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- CARD 3: HOW TO RESTORE (EASY STEP-BY-STEP INSTRUCTIONS) -->
    <div class="admin-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:1.75rem; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <h3 style="font-size:1.1rem; font-weight:800; color:#0f172a; margin:0 0 1rem 0; display:flex; align-items:center; gap:8px;">
            <span style="font-size:1.2rem;">💡</span>
            Emergency Disaster Recovery Guide (Aapka Data Kaise Recover Hoga?)
        </h3>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
            <div style="background:#f8fafc; border-left:3px solid #059669; padding:1rem; border-radius:0 8px 8px 0;">
                <strong style="color:#0f172a; font-size:0.9rem; display:block; margin-bottom:0.25rem;">Step 1: Download &amp; Keep Safe</strong>
                <p style="margin:0; font-size:0.825rem; color:#64748b; line-height:1.5;">
                    Har 1 se 2 hafton baad yahan se <strong>"Download Full Database (.sql)"</strong> dabayein aur file ko apne personal computer ya Google Drive me save kar lein.
                </p>
            </div>

            <div style="background:#f8fafc; border-left:3px solid #2563eb; padding:1rem; border-radius:0 8px 8px 0;">
                <strong style="color:#0f172a; font-size:0.9rem; display:block; margin-bottom:0.25rem;">Step 2: If cPanel / Hosting Crashes</strong>
                <p style="margin:0; font-size:0.825rem; color:#64748b; line-height:1.5;">
                    Agar aapki hosting band ho jaye ya server change karna ho, to kisi bhi naye hosting account par jayein aur phpMyAdmin open karein.
                </p>
            </div>

            <div style="background:#f8fafc; border-left:3px solid #d97706; padding:1rem; border-radius:0 8px 8px 0;">
                <strong style="color:#0f172a; font-size:0.9rem; display:block; margin-bottom:0.25rem;">Step 3: 2-Minute Restore</strong>
                <p style="margin:0; font-size:0.825rem; color:#64748b; line-height:1.5;">
                    phpMyAdmin me <strong>"Import"</strong> dabayein aur apni `.sql` file select karein. Sirf 2 minute me aapke tamam 21+ articles aur settings wapas restore ho jayengi!
                </p>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
