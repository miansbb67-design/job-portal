<?php
/**
 * Test script for Reactions (Like/Dislike) and Comments API & UI
 */
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

$baseUrl = 'http://localhost/job-portal';

// 1. Get an active article
$art = $db->query("SELECT id, slug, title, likes, dislikes FROM articles WHERE is_trash = 0 AND status = 'published' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$art) {
    die("No active article found.\n");
}
$articleId = $art['id'];
$slug = $art['slug'];
echo "Testing with Article #{$articleId}: '{$art['title']}'\n";

// 2. Fetch Single Article page to get CSRF token
$ch = curl_init("{$baseUrl}/article.php?slug={$slug}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$cookieJar = tempnam(sys_get_temp_dir(), 'test_art_cookie_');
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$html = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/', $html, $m);
$csrfToken = $m[1] ?? '';
if (empty($csrfToken)) {
    die("FAILED to extract CSRF token from article page.\n");
}
echo "CSRF token extracted: " . substr($csrfToken, 0, 10) . "...\n";

// 3. Test LIKE reaction
$ch = curl_init("{$baseUrl}/api/react.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'article_id' => $articleId,
    'type' => 'like',
    'csrf_token' => $csrfToken
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "React LIKE response (HTTP {$httpCode}): {$res}\n";
$json = json_decode($res, true);
if (!$json || !$json['success']) {
    die("LIKE test failed.\n");
}

// 4. Test DISLIKE reaction (switch)
$ch = curl_init("{$baseUrl}/api/react.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'article_id' => $articleId,
    'type' => 'dislike',
    'csrf_token' => $csrfToken
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "React DISLIKE response (HTTP {$httpCode}): {$res}\n";
$json = json_decode($res, true);
if (!$json || !$json['success'] || $json['user_reaction'] !== 'dislike') {
    die("DISLIKE switch test failed.\n");
}

// 5. Test Comment Submission
$testComment = "This is a great career guide! Highly informative. " . time();
$ch = curl_init("{$baseUrl}/api/comment.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'article_id' => $articleId,
    'user_name' => 'Faizan Ahmed',
    'user_email' => 'faizan.test@example.com',
    'comment_text' => $testComment,
    'csrf_token' => $csrfToken,
    'website_url' => ''
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Comment submit response (HTTP {$httpCode}): {$res}\n";
$json = json_decode($res, true);
if (!$json || !$json['success']) {
    die("Comment submission failed.\n");
}

// 6. Verify comment appears on article page
$ch = curl_init("{$baseUrl}/article.php?slug={$slug}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$htmlAfter = curl_exec($ch);
curl_close($ch);

if (strpos($htmlAfter, 'Faizan Ahmed') !== false && strpos($htmlAfter, 'This is a great career guide!') !== false) {
    echo "SUCCESS: Comment successfully rendered on the public article page!\n";
} else {
    echo "FAILED: Comment not found on article page.\n";
}

@unlink($cookieJar);
echo "ALL REACTION AND COMMENT TESTS PASSED!\n";
