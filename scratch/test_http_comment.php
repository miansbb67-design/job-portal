<?php
$cookieJar = tempnam(sys_get_temp_dir(), 'comm_cookie_');
$articleUrl = "http://localhost/job-portal/article.php?slug=top-10-high-paying-remote-jobs-2026";

// Step 1: GET the article page
$ch = curl_init($articleUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$html = curl_exec($ch);
curl_close($ch);

// Extract CSRF token
preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/i', $html, $matches);
$csrfToken = $matches[1] ?? '';
echo "CSRF Token: $csrfToken\n";

if (empty($csrfToken)) {
    die("CSRF Token extraction failed!\n");
}

// Step 2: POST JSON to api/comment.php
$apiUrl = "http://localhost/job-portal/api/comment.php";
$payload = json_encode([
    'article_id' => 1,
    'user_name' => 'Testing User',
    'user_email' => 'tester@example.com',
    'comment_text' => 'Testing comment via HTTP API',
    'csrf_token' => $csrfToken,
    'website_url' => ''
]);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest'
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $resp\n";

@unlink($cookieJar);
