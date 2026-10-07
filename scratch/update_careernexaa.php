<?php
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();

$updates = [
    'site_name' => 'CareerNexaa',
    'default_meta_title_template' => '{title} | CareerNexaa',
    'site_footer_text' => 'CareerNexaa is your trusted portal for verified job vacancies, competitive exam guides, and career growth strategies.',
    'site_copyright' => '© ' . date('Y') . ' CareerNexaa. All rights reserved.',
    'contact_email' => 'contact@careernexaa.com'
];

$stmt = $db->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");

foreach ($updates as $k => $v) {
    $stmt->execute([$v, $k]);
    echo "Updated {$k} -> {$v}\n";
}

echo "All settings successfully updated to CareerNexaa!\n";
