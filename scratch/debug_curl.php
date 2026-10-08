<?php
$baseUrl = 'http://localhost/job-portal';

require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$db->exec("DELETE FROM login_attempts");

$cookieFile = __DIR__ . '/test_cookie.txt';
@unlink($cookieFile);

$ch = curl_init($baseUrl . '/admin/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$html = curl_exec($ch);
curl_close($ch);

if (preg_match_all('/<div class="alert-error"[^>]*>(.*?)<\/div>/s', $html, $m)) {
    echo "Found alert-error in cURL fetch:\n";
    foreach ($m[0] as $match) {
        echo strip_tags($match) . "\n---\n";
    }
} else {
    echo "NO alert-error in cURL fetch!\n";
}
