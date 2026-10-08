<?php
/**
 * Test Comment Pending Moderation Lifecycle
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();
$baseUrl = 'http://localhost/job-portal';

echo "====================================================\n";
echo "1. SUBMITTING NEW COMMENT - CHECKING PENDING STATUS\n";
echo "====================================================\n";

$testName = "Moderation Tester";
$testEmail = "modtester@example.com";
$testText = "This comment must NOT auto-approve. It should be pending! " . time();

// Submit comment
$submitRes = submit_article_comment(1, $testName, $testEmail, $testText);
echo "Submit Result:\n";
print_r($submitRes);

if (!$submitRes['success'] || $submitRes['status'] !== 'pending') {
    die("FAILED: Comment was not created with 'pending' status!\n");
}
$commentId = $submitRes['comment']['id'];
echo "Created Comment ID: {$commentId} with status: {$submitRes['status']}\n";

// Verify directly in DB
$commDb = $db->query("SELECT * FROM comments WHERE id = {$commentId}")->fetch(PDO::FETCH_ASSOC);
echo "Database Status: {$commDb['status']}\n";
if ($commDb['status'] !== 'pending') {
    die("FAILED: Database status is NOT 'pending'!\n");
}
echo "PASSED: Comment saved as 'pending' in database.\n";

echo "\n====================================================\n";
echo "2. VERIFYING NOT VISIBLE ON PUBLIC WEBSITE YET\n";
echo "====================================================\n";

$articleUrl = "{$baseUrl}/article.php?slug=top-10-high-paying-remote-jobs-2026";
$publicHtml = file_get_contents($articleUrl);

if (strpos($publicHtml, $testText) !== false) {
    die("FAILED: Pending comment is visible on public website before admin approval!\n");
}
echo "PASSED: Pending comment is NOT visible on public website.\n";

echo "\n====================================================\n";
echo "3. VERIFYING APPEARS IN ADMIN PENDING QUEUE\n";
echo "====================================================\n";

$pendingCount = $db->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
echo "Total Pending Comments: {$pendingCount}\n";
if ($pendingCount < 1) {
    die("FAILED: Pending count is less than 1!\n");
}
echo "PASSED: Appears in pending count.\n";

echo "\n====================================================\n";
echo "4. SIMULATING ADMIN APPROVAL IN ADMIN PANEL\n";
echo "====================================================\n";

// Update status to approved as admin does
$db->prepare("UPDATE comments SET status = 'approved' WHERE id = ?")->execute([$commentId]);
$commDbApproved = $db->query("SELECT * FROM comments WHERE id = {$commentId}")->fetch(PDO::FETCH_ASSOC);
echo "Updated DB Status: {$commDbApproved['status']}\n";
if ($commDbApproved['status'] !== 'approved') {
    die("FAILED: Failed to approve comment!\n");
}
echo "PASSED: Comment successfully approved.\n";

echo "\n====================================================\n";
echo "5. VERIFYING NOW VISIBLE ON PUBLIC WEBSITE\n";
echo "====================================================\n";

$approvedHtml = file_get_contents($articleUrl);
if (strpos($approvedHtml, $testText) === false) {
    die("FAILED: Approved comment is NOT visible on public website!\n");
}
echo "PASSED: Approved comment is now rendered on public website!\n";

// Cleanup test comment
$db->exec("DELETE FROM comments WHERE id = {$commentId}");
echo "\n====================================================\n";
echo "ALL MODERATION FLOW TESTS PASSED PERFECTLY!\n";
echo "====================================================\n";
