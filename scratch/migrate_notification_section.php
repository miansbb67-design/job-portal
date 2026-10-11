<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

// Check if newsletter exists
$check = $db->query("SELECT id FROM section_visibility WHERE section_key = 'newsletter'")->fetch();
if ($check) {
    $stmt = $db->prepare("UPDATE section_visibility SET section_key = 'notification', section_name = 'NOTIFICATION', title = 'Instant Job & Career Notifications', subtitle = 'Turn on alerts to receive verified hiring, career guides & breaking exam updates directly on your device.' WHERE section_key = 'newsletter'");
    $stmt->execute();
    echo "Updated newsletter section to notification.\n";
} else {
    // Check if notification already exists
    $checkNotif = $db->query("SELECT id FROM section_visibility WHERE section_key = 'notification'")->fetch();
    if (!$checkNotif) {
        $ins = $db->prepare("INSERT INTO section_visibility (section_key, section_name, title, subtitle, item_limit, is_enabled, sort_order) VALUES ('notification', 'NOTIFICATION', 'Instant Job & Career Notifications', 'Turn on alerts to receive verified hiring, career guides & breaking exam updates directly on your device.', 0, 1, 7)");
        $ins->execute();
        echo "Inserted notification section.\n";
    } else {
        echo "Notification section already exists.\n";
    }
}
