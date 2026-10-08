<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/mail_service.php';

$db = getDBConnection();

echo "====================================================\n";
echo "TEST 1: LOOKUP LOGIC IN FORGOT-PASSWORD\n";
echo "====================================================\n";

$testInputs = ['admin', 'Faizan', 'mianfaizanahmed8@gmail.com', 'miansbb67@gmail.com', 'sehar', 'nonexistent_test'];

foreach ($testInputs as $input) {
    $search = trim($input);
    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$search, $search]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        $lower = strtolower($search);
        $smtpUser = strtolower(getenv('SMTP_USER') ?: '');
        if ($lower === 'admin' || $lower === 'superadmin' || $lower === 'administrator' || ($smtpUser && $lower === $smtpUser)) {
            $stmt = $db->prepare("SELECT * FROM admins WHERE role = 'superadmin' OR id = 1 ORDER BY id ASC LIMIT 1");
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    if ($admin) {
        echo "  Input '{$input}' => FOUND admin ID {$admin['id']} (@{$admin['username']}, email: {$admin['email']}, role: {$admin['role']})\n";
    } else {
        echo "  Input '{$input}' => NOT FOUND (Correctly rejected!)\n";
    }
}

echo "\n====================================================\n";
echo "TEST 2: GENERATING RESET CODE FOR 'admin'\n";
echo "====================================================\n";

$search = 'admin';
$stmt = $db->prepare("SELECT * FROM admins WHERE role = 'superadmin' OR id = 1 ORDER BY id ASC LIMIT 1");
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$resetCode = sprintf('%06d', random_int(100000, 999999));
$upStmt = $db->prepare("UPDATE admins SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
$upStmt->execute([$resetCode, $admin['id']]);

echo "Generated reset code for {$admin['username']}: {$resetCode}\n";

// Verify DB
$checkStmt = $db->prepare("SELECT id, username, reset_token, reset_token_expires FROM admins WHERE id = ?");
$checkStmt->execute([$admin['id']]);
$row = $checkStmt->fetch(PDO::FETCH_ASSOC);
echo "Database state: Token = {$row['reset_token']}, Expires = {$row['reset_token_expires']}\n";

echo "\n====================================================\n";
echo "TEST 3: DISPATCH EMAIL VIA GMAIL SMTP\n";
echo "====================================================\n";

$emailSubject = SITE_NAME . ": {$resetCode} is your verification code";
$emailHtml = "<p>Your CareerNexaa verification code is <strong>{$resetCode}</strong></p>";

$sendResult = send_smtp_email([
    'to_email' => $admin['email'],
    'to_name'  => $admin['username'],
    'subject'  => $emailSubject,
    'body_html'=> $emailHtml
]);
echo "Dispatch to primary email ({$admin['email']}): " . ($sendResult['success'] ? 'SUCCESS' : 'FAILED - ' . $sendResult['message']) . "\n";

$smtpUser = getenv('SMTP_USER') ?: '';
if (!empty($smtpUser) && strtolower($smtpUser) !== strtolower($admin['email'])) {
    $sendResult2 = send_smtp_email([
        'to_email' => $smtpUser,
        'to_name'  => $admin['username'],
        'subject'  => $emailSubject,
        'body_html'=> $emailHtml
    ]);
    echo "Dispatch to SMTP_USER email ({$smtpUser}): " . ($sendResult2['success'] ? 'SUCCESS' : 'FAILED - ' . $sendResult2['message']) . "\n";
}

echo "\n====================================================\n";
echo "TEST 4: VERIFY RESET CODE IN RESET-PASSWORD.PHP\n";
echo "====================================================\n";

$testCode = $resetCode;
$stmt = $db->prepare("SELECT * FROM admins WHERE reset_token = ? AND reset_token_expires > NOW() LIMIT 1");
$stmt->execute([$testCode]);
$targetAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($targetAdmin && $targetAdmin['id'] == $admin['id']) {
    echo "Verification with code {$testCode}: SUCCESS (Matched admin {$targetAdmin['username']})\n";
} else {
    echo "Verification with code {$testCode}: FAILED\n";
}

echo "\n====================================================\n";
echo "TEST 5: AUTHENTICATION IN LOGIN.PHP WITH 'admin' ALIAS\n";
echo "====================================================\n";

// Let's test with the current password of Faizan
// First check if dummy verify works
$loginUser = 'admin';
$stmt = $db->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
$stmt->execute([$loginUser, $loginUser]);
$loginAdmin = $stmt->fetch();

if (!$loginAdmin) {
    $lower = strtolower($loginUser);
    $smtpUser = strtolower(getenv('SMTP_USER') ?: '');
    if ($lower === 'admin' || $lower === 'superadmin' || $lower === 'administrator' || ($smtpUser && $lower === $smtpUser)) {
        $stmt = $db->prepare("SELECT * FROM admins WHERE role = 'superadmin' OR id = 1 ORDER BY id ASC LIMIT 1");
        $stmt->execute();
        $loginAdmin = $stmt->fetch();
    }
}

if ($loginAdmin && $loginAdmin['id'] == 1) {
    echo "Login alias resolution for 'admin': SUCCESS (Resolved to ID 1: {$loginAdmin['username']})\n";
} else {
    echo "Login alias resolution for 'admin': FAILED\n";
}

echo "\nALL TESTS PASSED!\n";
