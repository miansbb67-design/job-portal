<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mail_service.php';

echo "Testing SMTP to mianfaizanahmed8@gmail.com...\n";
$res = send_smtp_email([
    'to_email' => 'mianfaizanahmed8@gmail.com',
    'to_name' => 'Faizan',
    'subject' => 'CareerNexaa: 123456 is your verification code',
    'body_html' => '<p>Test verification code to mianfaizanahmed8@gmail.com</p>',
    'debug' => true
]);
print_r($res);
