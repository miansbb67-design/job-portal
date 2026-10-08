<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mail_service.php';

echo "Testing SMTP email dispatch...\n";
echo "SMTP_HOST: " . getenv('SMTP_HOST') . "\n";
echo "SMTP_PORT: " . getenv('SMTP_PORT') . "\n";
echo "SMTP_USER: " . getenv('SMTP_USER') . "\n";
echo "SMTP_PASS: " . (getenv('SMTP_PASS') ? 'SET (' . strlen(getenv('SMTP_PASS')) . ' chars)' : 'EMPTY') . "\n";

$result = send_smtp_email([
    'to_email' => 'miansbb67@gmail.com',
    'to_name' => 'Faizan Test',
    'subject' => 'Test Verification Code ' . rand(100000, 999999),
    'body_html' => '<p>This is a test verification code email.</p>',
    'debug' => true
]);

echo "\nResult:\n";
print_r($result);
