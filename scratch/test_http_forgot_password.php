<?php
// Test HTTP POST to forgot-password.php
$ch = curl_init('http://localhost/job-portal/admin/forgot-password.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
$html = curl_exec($ch);

// Extract CSRF token
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $matches);
$csrf = $matches[1] ?? '';
echo "CSRF Token extracted: " . substr($csrf, 0, 16) . "...\n";

// Now submit with username 'admin'
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $csrf,
    'username_or_email' => 'admin'
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$res = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Final URL: " . $info['url'] . "\n";

if (strpos($res, 'A 6-digit verification code has been sent') !== false || strpos($info['url'], 'reset-password.php') !== false) {
    echo "SUCCESS: Redirected to reset-password.php with success message!\n";
} else {
    echo "Check response:\n" . substr($res, 0, 500) . "\n";
}

// Clean cookie
@unlink(__DIR__ . '/cookie.txt');
