<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();
// Check if setting exists
$stmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = 'ga4_measurement_id'");
$stmt->execute();
$exists = $stmt->fetchColumn() > 0;

if ($exists) {
    $upd = $db->prepare("UPDATE settings SET setting_value = 'G-XH87VZP3DS', updated_at = NOW() WHERE setting_key = 'ga4_measurement_id'");
    $upd->execute();
    echo "Updated existing ga4_measurement_id setting.\n";
} else {
    $ins = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_group, updated_at) VALUES ('ga4_measurement_id', 'G-XH87VZP3DS', 'seo', NOW())");
    $ins->execute();
    echo "Inserted new ga4_measurement_id setting.\n";
}

$chk = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'ga4_measurement_id'");
$chk->execute();
echo "Current setting_value: " . $chk->fetchColumn() . "\n";
