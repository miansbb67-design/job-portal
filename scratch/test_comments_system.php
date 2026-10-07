<?php
/**
 * Comprehensive Comment System Test Script
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();
$baseUrl = 'http://localhost/job-portal';

echo "====================================================\n";
echo "1. VERIFYING COMMENTS TABLE & SUBMISSION FUNCTION\n";
echo "====================================================\n";

// Get first article
$art = $db->query("SELECT id, title, slug FROM articles WHERE status = 'published' AND is_trash = 0 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$art) {
    die("No published article found to test.\n");
}
echo "Testing on Article #{$art['id']}: '{$art['title']}'\n";

// 1. Test short comment (e.g. 2 chars 'Ok') - previously failed, should now pass!
$shortComment = "Ok";
$res1 = submit_article_comment($art['id'], "Tester Short", "short@example.com", $shortComment);
echo "Short comment ('Ok') result: " . ($res1['success'] ? 'SUCCESS' : 'FAILED: ' . $res1['message']) . "\n";

// 2. Test comment without email (optional email) - should pass!
$noEmailComment = "Great insights in this guide!";
$res2 = submit_article_comment($art['id'], "Tester NoEmail", "", $noEmailComment);
echo "No-email comment result: " . ($res2['success'] ? 'SUCCESS' : 'FAILED: ' . $res2['message']) . "\n";

// 3. Test HTTP API via curl (with session and CSRF token)
echo "\n====================================================\n";
echo "2. TESTING HTTP JSON API (api/comment.php)\n";
echo "====================================================\n";

$cookieJar = tempnam(sys_get_temp_dir(), 'comm_test_');
$articleUrl = "{$baseUrl}/article.php?slug=" . urlencode($art['slug']);

$ch = curl_init($articleUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$articleHtml = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/i', $articleHtml, $m);
$csrfToken = $m[1] ?? '';

if (empty($csrfToken)) {
    die("Failed to extract CSRF token from article page.\n");
}

$payload = json_encode([
    'article_id' => $art['id'],
    'user_name' => 'Automated Tester',
    'user_email' => 'autotester@example.com',
    'comment_text' => 'This is an end-to-end API test comment: ' . time(),
    'csrf_token' => $csrfToken,
    'comments_bot_trap' => ''
]);

$ch = curl_init("{$baseUrl}/api/comment.php");
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

echo "API HTTP Status: {$httpCode}\n";
echo "API Response: {$resp}\n";

$json = json_decode($resp, true);
if ($httpCode === 200 && !empty($json['success'])) {
    echo "SUCCESS: API comment submission works smoothly!\n";
} else {
    echo "FAILED: API comment submission failed.\n";
}

// 4. Verify rendering on article page
echo "\n====================================================\n";
echo "3. VERIFYING COMMENT RENDERS ON PUBLIC ARTICLE PAGE\n";
echo "====================================================\n";

$ch = curl_init($articleUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$freshHtml = curl_exec($ch);
curl_close($ch);

if (strpos($freshHtml, 'Automated Tester') !== false) {
    echo "SUCCESS: 'Automated Tester' comment rendered on public article page!\n";
} else {
    echo "FAILED: Comment not found on public article page.\n";
}

// 5. Test Section Visibility for Comments (Hide & Show)
echo "\n====================================================\n";
echo "4. TESTING SECTION VISIBILITY TOGGLE (comments ON/OFF)\n";
echo "====================================================\n";

// Turn OFF article_comments
set_section_visibility('article_comments', 0);
$ch = curl_init($articleUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$htmlHidden = curl_exec($ch);
curl_close($ch);

$commentsPresentWhenOff = (strpos($htmlHidden, 'id="comments"') !== false);
echo "Comments section present when OFF: " . ($commentsPresentWhenOff ? 'YES (FAILED)' : 'NO (PASSED - Cleanly Hidden)') . "\n";

// Turn ON article_comments
set_section_visibility('article_comments', 1);
$ch = curl_init($articleUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$htmlVisible = curl_exec($ch);
curl_close($ch);

$commentsPresentWhenOn = (strpos($htmlVisible, 'id="comments"') !== false);
echo "Comments section present when ON: " . ($commentsPresentWhenOn ? 'YES (PASSED - Restored)' : 'NO (FAILED)') . "\n";

@unlink($cookieJar);
echo "\n====================================================\n";
echo "ALL COMMENT SYSTEM TESTS COMPLETED!\n";
echo "====================================================\n";
