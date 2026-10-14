<?php
/**
 * Monetization & Advertising Management System
 * Supports Google AdSense, Adsterra, Monetag, and Custom Ad Placements.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Initialize monetization tables and seed default ad placement slots
 *
 * @param PDO $db
 */
function init_monetization_tables($db) {
    static $initialized = false;
    if ($initialized) return;

    try {
        // 1. Ad Placements Table
        $db->exec("
            CREATE TABLE IF NOT EXISTS `ad_placements` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `slot_key` VARCHAR(50) NOT NULL,
                `slot_name` VARCHAR(100) NOT NULL,
                `slot_description` VARCHAR(255) DEFAULT NULL,
                `is_enabled` TINYINT(1) NOT NULL DEFAULT 0,
                `ad_network` VARCHAR(50) NOT NULL DEFAULT 'adsense',
                `custom_code` MEDIUMTEXT DEFAULT NULL,
                `device_target` VARCHAR(50) NOT NULL DEFAULT 'all',
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_slot_key` (`slot_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Ad Units Table (Reusable ad codes)
        $db->exec("
            CREATE TABLE IF NOT EXISTS `ad_units` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `unit_name` VARCHAR(100) NOT NULL,
                `network` VARCHAR(50) NOT NULL DEFAULT 'adsense',
                `unit_format` VARCHAR(50) NOT NULL DEFAULT 'display',
                `code_snippet` MEDIUMTEXT DEFAULT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. Seed Default Ad Placement Slots (Exact Screenshot 1:1 match)
        $defaultSlots = [
            ['header_ad', 'Header Ad', 'Header, after navigation', 0, 'adsense', 1],
            ['in_article_ad', 'In-Article Ad', 'Between article paragraphs', 0, 'adsense', 2],
            ['between_content_ad', 'Between Content Ad', 'Between content sections / listing rows', 0, 'adsense', 3],
            ['sidebar_ad', 'Sidebar Ad', 'Sidebar (desktop / tablet)', 0, 'adsense', 4],
            ['below_content_ad', 'Below Content Ad', 'After article / vlog content', 0, 'adsense', 5],
            ['related_content_ad', 'Related Content Ad', 'Related-content area', 0, 'adsense', 6]
        ];

        $checkStmt = $db->prepare("SELECT COUNT(*) FROM ad_placements WHERE slot_key = ?");
        $insertStmt = $db->prepare("
            INSERT INTO ad_placements (slot_key, slot_name, slot_description, is_enabled, ad_network, sort_order) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($defaultSlots as $slot) {
            $checkStmt->execute([$slot[0]]);
            if ($checkStmt->fetchColumn() == 0) {
                $insertStmt->execute($slot);
            }
        }

        $initialized = true;
    } catch (Throwable $e) {
        error_log("Monetization DB Init Error: " . $e->getMessage());
    }
}

/**
 * Get all configured ad placements
 *
 * @param PDO|null $db
 * @return array
 */
function get_all_ad_placements($db = null) {
    if (!$db) $db = getDBConnection();
    init_monetization_tables($db);

    try {
        $stmt = $db->query("SELECT * FROM ad_placements ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Get a specific ad placement by slot key
 *
 * @param string $slotKey
 * @param PDO|null $db
 * @return array|null
 */
function get_ad_placement($slotKey, $db = null) {
    if (!$db) $db = getDBConnection();
    init_monetization_tables($db);

    try {
        $stmt = $db->prepare("SELECT * FROM ad_placements WHERE slot_key = ? LIMIT 1");
        $stmt->execute([$slotKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Get a monetization setting value from settings table
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function get_monetization_setting($key, $default = null) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute(['monetization_' . $key]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null) ? $val : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

/**
 * Save a monetization setting value
 *
 * @param string $key
 * @param string $value
 * @return bool
 */
function save_monetization_setting($key, $value) {
    try {
        $db = getDBConnection();
        $settingKey = 'monetization_' . $key;
        $stmt = $db->prepare("
            INSERT INTO settings (setting_key, setting_value, setting_group) 
            VALUES (?, ?, 'monetization')
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");
        return $stmt->execute([$settingKey, $value]);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Render policy-compliant monetization ad slot in frontend pages
 *
 * @param string $slotKey 'header_ad' | 'in_article_ad' | 'between_content_ad' | 'sidebar_ad' | 'below_content_ad' | 'related_content_ad'
 * @param string $extraClass Optional CSS class
 * @return void
 */
function render_monetization_ad($slotKey, $extraClass = '') {
    // 1. Check Global Master Monetization Switch (Default: 1 / Enabled)
    $masterSwitch = get_monetization_setting('master_enable', '1');
    if ($masterSwitch === '0') {
        return;
    }

    // 2. Hide for logged-in administrators if enabled
    $hideForAdmin = get_monetization_setting('hide_for_admin', '0');
    if ($hideForAdmin === '1' && function_exists('is_admin_logged_in') && is_admin_logged_in()) {
        return;
    }

    // 3. Fetch slot configuration
    $slot = get_ad_placement($slotKey);
    if (!$slot || empty($slot['is_enabled'])) {
        return; // Slot is turned OFF
    }

    // 4. Device target check
    $deviceTarget = $slot['device_target'] ?? 'all';
    $deviceClass = '';
    if ($deviceTarget === 'desktop') {
        $deviceClass = 'hide-on-mobile';
    } elseif ($deviceTarget === 'mobile') {
        $deviceClass = 'hide-on-desktop';
    }

    // 5. Ad Label / Disclosure setting (AdSense compliance: "Advertisement" or "Sponsored")
    $showLabel = get_monetization_setting('show_ad_label', '1');
    $labelText = get_monetization_setting('ad_label_text', 'Advertisement');

    // 6. Output Container
    $containerClass = 'monetization-ad-wrapper slot-' . htmlspecialchars($slotKey) . ' ' . $deviceClass . ' ' . $extraClass;
    
    echo "\n<!-- Monetization Slot: " . htmlspecialchars($slot['slot_name']) . " -->\n";
    echo '<div class="' . trim($containerClass) . '" style="margin: 1.5rem auto; text-align: center; max-width: 100%; clear: both;">';
    
    if ($showLabel === '1') {
        echo '<div style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; font-weight: 700; margin-bottom: 0.35rem;">';
        echo htmlspecialchars($labelText);
        echo '</div>';
    }

    // 7. Render according to configured ad network
    $network = $slot['ad_network'] ?? 'adsense';
    $customCode = trim($slot['custom_code'] ?? '');

    if (!empty($customCode)) {
        // Output custom code or configured network snippet directly
        echo $customCode;
    } elseif ($network === 'adsense') {
        $pubId = get_monetization_setting('adsense_pub_id', '');
        if (!empty($pubId)) {
            echo '<ins class="adsbygoogle"
                 style="display:block"
                 data-ad-client="' . htmlspecialchars($pubId) . '"
                 data-ad-slot="' . htmlspecialchars($slot['id'] . '00' . $slot['sort_order']) . '"
                 data-ad-format="auto"
                 data-full-width-responsive="true"></ins>
            <script>
                 (adsbygoogle = window.adsbygoogle || []).push({});
            </script>';
        } else {
            // High-end placeholder indicating slot is ready
            echo '<div style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:8px; padding:1.25rem; color:#64748b; font-size:0.85rem; display:inline-flex; align-items:center; gap:0.5rem;">';
            echo '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path></svg>';
            echo '<span>' . htmlspecialchars($slot['slot_name']) . ' - AdSense Active Slot</span>';
            echo '</div>';
        }
    } elseif ($network === 'adsterra') {
        $adsterraCode = get_monetization_setting('adsterra_banner_code', '');
        echo !empty($adsterraCode) ? $adsterraCode : '<!-- Adsterra Slot Active -->';
    } elseif ($network === 'monetag') {
        $monetagCode = get_monetization_setting('monetag_banner_code', '');
        echo !empty($monetagCode) ? $monetagCode : '<!-- Monetag Slot Active -->';
    }

    echo '</div>';
    echo "\n<!-- /Monetization Slot -->\n";
}

/**
 * Output global network scripts (AdSense Auto Ads, Adsterra Social Bar, Monetag Tag) in the <head> tag
 *
 * @return void
 */
function render_monetization_head_scripts() {
    // 1. Check Global Master Switch
    $masterSwitch = get_monetization_setting('master_enable', '1');
    if ($masterSwitch === '0') return;

    // 2. Hide for logged-in admins if configured
    $hideForAdmin = get_monetization_setting('hide_for_admin', '0');
    if ($hideForAdmin === '1' && function_exists('is_admin_logged_in') && is_admin_logged_in()) {
        return;
    }

    echo "\n<!-- Global Monetization Scripts -->\n";

    // A. Google AdSense Library & Auto Ads
    $pubId = get_monetization_setting('adsense_pub_id', '');
    $autoAds = get_monetization_setting('adsense_auto_ads', '1');
    if (!empty($pubId)) {
        echo '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . htmlspecialchars($pubId) . '" crossorigin="anonymous"></script>' . "\n";
    }

    // B. Adsterra Header Scripts (Social Bar, Popunder)
    $adsterraEnabled = get_monetization_setting('adsterra_enabled', '0');
    if ($adsterraEnabled === '1') {
        $socialBarOn = get_monetization_setting('adsterra_social_bar_enable', '0');
        $socialBarCode = get_monetization_setting('adsterra_social_bar_code', '');
        if ($socialBarOn === '1' && !empty($socialBarCode)) {
            echo $socialBarCode . "\n";
        }

        $popunderOn = get_monetization_setting('adsterra_popunder_enable', '0');
        $popunderCode = get_monetization_setting('adsterra_popunder_code', '');
        if ($popunderOn === '1' && !empty($popunderCode)) {
            echo $popunderCode . "\n";
        }
    }

    // C. Monetag Header Scripts (MultiTag, Vignette, In-Page Push)
    $monetagEnabled = get_monetization_setting('monetag_enabled', '0');
    if ($monetagEnabled === '1') {
        $multiTag = get_monetization_setting('monetag_multitag_code', '');
        if (!empty($multiTag)) {
            echo $multiTag . "\n";
        }

        $pushOn = get_monetization_setting('monetag_push_enable', '0');
        $pushCode = get_monetization_setting('monetag_push_code', '');
        if ($pushOn === '1' && !empty($pushCode)) {
            echo $pushCode . "\n";
        }

        $vignetteOn = get_monetization_setting('monetag_vignette_enable', '0');
        $vignetteCode = get_monetization_setting('monetag_vignette_code', '');
        if ($vignetteOn === '1' && !empty($vignetteCode)) {
            echo $vignetteCode . "\n";
        }
    }

    echo "<!-- /Global Monetization Scripts -->\n";
}

/**
 * Insert In-Article ad slot after specified number of paragraphs
 *
 * @param string $contentHtml
 * @param int $paragraphIndex Default: 2 (after 2nd paragraph)
 * @return string
 */
function inject_in_article_ad($contentHtml, $paragraphIndex = 2) {
    $slot = get_ad_placement('in_article_ad');
    if (!$slot || empty($slot['is_enabled'])) {
        return $contentHtml;
    }

    ob_start();
    render_monetization_ad('in_article_ad');
    $adHtml = ob_get_clean();

    if (empty(trim($adHtml))) {
        return $contentHtml;
    }

    // Split at closing paragraph tags
    $parts = explode('</p>', $contentHtml);
    if (count($parts) <= 1) {
        return $contentHtml . $adHtml;
    }

    $targetIdx = min($paragraphIndex, count($parts) - 1);
    $output = '';
    foreach ($parts as $idx => $part) {
        $output .= $part;
        if ($idx < count($parts) - 1) {
            $output .= '</p>';
        }
        if (($idx + 1) === $targetIdx) {
            $output .= $adHtml;
        }
    }

    return $output;
}
