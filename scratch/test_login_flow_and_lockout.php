<?php
$baseUrl = 'http://localhost/job-portal';

require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$db->exec("DELETE FROM login_attempts");

echo "========================================================\n";
echo "TEST 1: DIRECT GET TO /admin/login.php (NO RED BOX)\n";
echo "========================================================\n";
$cookieFile = __DIR__ . '/test_cookie.txt';
@unlink($cookieFile);

$ch = curl_init($baseUrl . '/admin/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$html = curl_exec($ch);
curl_close($ch);

$hasRedBox = (strpos($html, '<div class="alert-error"') !== false);
$hasAuthMsg = (strpos($html, 'Please log in to access') !== false);

echo "Initial page load has alert-error: " . ($hasRedBox ? "YES (FAILED)" : "NO (PASSED - Clean screen!)") . "\n";
echo "Initial page load has 'Please log in' message: " . ($hasAuthMsg ? "YES (FAILED)" : "NO (PASSED - Clean screen!)") . "\n";

echo "\n========================================================\n";
echo "TEST 2: REDIRECT FROM /admin/ (UNAUTHENTICATED)\n";
echo "========================================================\n";
$ch = curl_init($baseUrl . '/admin/index.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$redirectHtml = curl_exec($ch);
curl_close($ch);

$hasRedBoxAfterRedirect = (strpos($redirectHtml, '<div class="alert-error"') !== false);
$hasAuthMsgAfterRedirect = (strpos($redirectHtml, 'Please log in to access') !== false);

echo "Redirect has alert-error: " . ($hasRedBoxAfterRedirect ? "YES (FAILED)" : "NO (PASSED - Clean screen!)") . "\n";
echo "Redirect has 'Please log in': " . ($hasAuthMsgAfterRedirect ? "YES (FAILED)" : "NO (PASSED - Clean screen!)") . "\n";

echo "\n========================================================\n";
echo "TEST 3: FAILED LOGIN ATTEMPTS (1 TO 5) & 30-SEC LOCKOUT\n";
echo "========================================================\n";

for ($i = 1; $i <= 5; $i++) {
    // Get fresh CSRF
    $ch = curl_init($baseUrl . '/admin/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $pageHtml = curl_exec($ch);
    curl_close($ch);

    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $pageHtml, $m);
    $csrf = $m[1] ?? '';

    // Submit wrong password
    $ch = curl_init($baseUrl . '/admin/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'csrf_token' => $csrf,
        'username' => 'admin',
        'password' => 'WrongPass_' . $i
    ]));
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $resp = curl_exec($ch);
    curl_close($ch);

    if ($i < 5) {
        $expectedRemaining = 5 - $i;
        $matched = (strpos($resp, "({$expectedRemaining} attempt(s) remaining)") !== false);
        echo "Attempt {$i} failed: " . ($matched ? "PASSED - Shows '({$expectedRemaining} attempt(s) remaining)'" : "CHECK RESPONSE") . "\n";
    } else {
        $locked = (strpos($resp, '30</span> second(s)') !== false || strpos($resp, 'temporarily locked') !== false);
        echo "Attempt 5 failed (Lockout triggered): " . ($locked ? "PASSED - Locked out for 30 seconds with countdown timer!" : "CHECK RESPONSE") . "\n";
    }
}

@unlink($cookieFile);
echo "\n========================================================\n";
echo "TEST FINISHED.\n";
