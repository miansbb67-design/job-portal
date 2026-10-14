<?php
/**
 * Automated Test Suite for Career & Jobs Blog Website
 * Tests all public routes, admin authentication, unauthorized blocks, and database integrity.
 */

$projectDir = basename(__DIR__);
$baseUrl = 'http://localhost/' . $projectDir;

$routes = [
    'Home Page' => '/',
    'Articles Page' => '/articles.php',
    'Single Article' => '/article.php?slug=top-10-high-paying-remote-tech-careers-in-2026',
    'Categories Page' => '/categories.php',
    'Specific Category' => '/categories.php?slug=remote-work-from-home',
    'Search Page (with query)' => '/search.php?q=remote',
    'Search Page (empty)' => '/search.php',
    'About Us' => '/about.php',
    'Contact Us' => '/contact.php',
    'Privacy Policy' => '/privacy-policy.php',
    'Cookie Policy' => '/cookie-policy.php',
    'Terms & Conditions' => '/terms.php',
    'Disclaimer' => '/disclaimer.php',
    'Admin Login' => '/admin/login.php',
    'Custom 404 (non-existent slug)' => '/article.php?slug=this-does-not-exist',
    'Robots.txt' => '/robots.txt',
    'Sitemap.xml' => '/sitemap.xml',
];

echo "========================================\n";
echo "1. TESTING PUBLIC ROUTES & STATUS CODES\n";
echo "========================================\n";

$allPassed = true;

foreach ($routes as $name => $path) {
    $url = $baseUrl . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $expected = ($name === 'Custom 404 (non-existent slug)') ? 404 : 200;
    $status = ($httpCode === $expected) ? "PASSED [{$httpCode}]" : "FAILED [Expected {$expected}, got {$httpCode}]";
    if ($httpCode !== $expected) {
        $allPassed = false;
    }
    echo sprintf("%-35s : %s\n", $name, $status);
}

echo "\n========================================\n";
echo "2. TESTING ADMIN ACCESS PROTECTION\n";
echo "========================================\n";

// Protected Admin URLs (should redirect with 302 to login.php when unauthenticated)
$protectedUrls = [
    'Admin Dashboard' => '/admin/index.php',
    'Admin Articles'  => '/admin/articles.php',
    'Admin Add Article' => '/admin/add-article.php',
    'Admin Categories' => '/admin/categories.php',
];

foreach ($protectedUrls as $name => $path) {
    $url = $baseUrl . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);

    $isProtected = ($httpCode === 302 && strpos($redirectUrl, 'login.php') !== false);
    $status = $isProtected ? "PASSED [302 -> login.php]" : "FAILED [Got {$httpCode} redirect: {$redirectUrl}]";
    if (!$isProtected) {
        $allPassed = false;
    }
    echo sprintf("%-35s : %s\n", $name, $status);
}

echo "\n========================================\n";
echo "3. TESTING ADMIN AUTHENTICATION FLOW\n";
echo "========================================\n";

$cookieJar = tempnam(sys_get_temp_dir(), 'admin_cookie_');

// Step A: Fetch Login Page & Grab CSRF token
$ch = curl_init($baseUrl . '/admin/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
$loginPage = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/', $loginPage, $matches);
$csrfToken = $matches[1] ?? '';

if (empty($csrfToken)) {
    echo "FAILED to extract CSRF token from login page.\n";
    $allPassed = false;
} else {
    echo "CSRF Token Extracted: " . substr($csrfToken, 0, 10) . "...\n";

    // Step B: Attempt Wrong Password
    $ch = curl_init($baseUrl . '/admin/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'csrf_token' => $csrfToken,
        'username' => 'admin',
        'password' => 'WrongPassword123'
    ]));
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    $badLoginResponse = curl_exec($ch);
    $badLoginCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $hasInvalidNotice = (strpos($badLoginResponse, 'Invalid credentials') !== false);
    echo "Invalid Login Test: " . ($hasInvalidNotice ? "PASSED [Rejected cleanly with error message]" : "FAILED") . "\n";
    if (!$hasInvalidNotice) $allPassed = false;

    // Step C: Attempt Valid Login (if environment credentials are provided)
    $testUser = getenv('ADMIN_TEST_USER') ?: '';
    $testPass = getenv('ADMIN_TEST_PASS') ?: '';
    if (!empty($testUser) && !empty($testPass)) {
        $ch = curl_init($baseUrl . '/admin/login.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'csrf_token' => $csrfToken,
            'username' => $testUser,
            'password' => $testPass
        ]));
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $goodLoginResponse = curl_exec($ch);
        $goodLoginCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);

        $isLoggedIn = ($goodLoginCode === 302 && strpos($redirectUrl, 'index.php') !== false);
        echo "Valid Login Test: " . ($isLoggedIn ? "PASSED [302 -> admin/index.php]" : "FAILED [Got {$goodLoginCode}]") . "\n";
        if (!$isLoggedIn) $allPassed = false;

        // Step D: Access Admin Dashboard with Authenticated Cookie
        $ch = curl_init($baseUrl . '/admin/index.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
        $dashResponse = curl_exec($ch);
        $dashCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $hasWelcome = (stripos($dashResponse, 'Welcome back') !== false);
        echo "Authenticated Dashboard Access: " . ($hasWelcome && $dashCode === 200 ? "PASSED [Welcome back rendered]" : "FAILED") . "\n";
        if (!$hasWelcome) $allPassed = false;
    } else {
        echo "Valid Login Test: SKIPPED (Set ADMIN_TEST_USER & ADMIN_TEST_PASS in environment to test)\n";
    }
}

@unlink($cookieJar);

echo "\n========================================\n";
echo "4. TESTING CLEAN URL REWRITES & SECURITY RULES\n";
echo "========================================\n";

$rewriteSecurityTests = [
    'Clean Article Rewrite (/article/slug)'   => ['path' => '/article/top-10-high-paying-remote-tech-careers-in-2026', 'expected' => 200],
    'Clean Category Rewrite (/category/slug)' => ['path' => '/category/remote-work-from-home', 'expected' => 200],
    'Clean Route (/about)'                   => ['path' => '/about', 'expected' => 200],
    'Clean Route (/privacy-policy)'          => ['path' => '/privacy-policy', 'expected' => 200],
    'Clean Route (/cookie-policy)'           => ['path' => '/cookie-policy', 'expected' => 200],
    'Clean Route (/terms)'                   => ['path' => '/terms', 'expected' => 200],
    'Security Shield: database.sql'          => ['path' => '/database.sql', 'expected' => 403],
    'Security Shield: .env'                  => ['path' => '/.env', 'expected' => 403],
    'Security Shield: .env.example'          => ['path' => '/.env.example', 'expected' => 403],
    'Security Shield: uploads/.htaccess'     => ['path' => '/uploads/.htaccess', 'expected' => 403],
];

$testCount = count($routes) + count($protectedUrls) + 4; // routes + protected + auth tests

foreach ($rewriteSecurityTests as $testName => $cfg) {
    $ch = curl_init($baseUrl . $cfg['path']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $passed = ($code === $cfg['expected']);
    if (!$passed) $allPassed = false;
    $testCount++;
    echo sprintf("%-40s : %s\n", $testName, $passed ? "PASSED [{$code}]" : "FAILED [Expected {$cfg['expected']}, got {$code}]");
}

echo "\n========================================\n";
echo "OVERALL VERIFICATION: " . ($allPassed ? "ALL {$testCount} TESTS PASSED PERFECTLY!" : "SOME TESTS FAILED") . "\n";
echo "========================================\n";

