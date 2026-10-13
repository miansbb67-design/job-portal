<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

$defaults = [
    'google_analytics_api_key' => 'AIzaSyCCWXlnB6t_OwDg_yzTZ3UuT3i2-v75jBQ',
    'google_analytics_property_id' => '',
    'ga4_measurement_id' => 'G-XH87VZP3DS',
    'google_analytics_service_account' => ''
];

foreach ($defaults as $key => $val) {
    $chk = $db->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
    $chk->execute([$key]);
    if ($chk->fetchColumn() == 0) {
        $ins = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_group, updated_at) VALUES (?, ?, 'seo', NOW())");
        $ins->execute([$key, $val]);
        echo "Inserted $key\n";
    } else {
        // If it exists, update API key if empty or default
        if ($key === 'google_analytics_api_key') {
            $upd = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $upd->execute([$val, $key]);
            echo "Updated $key with user key\n";
        }
    }
}
echo "Done initializing Google Analytics settings.\n";
