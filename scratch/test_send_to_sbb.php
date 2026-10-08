<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/mail_service.php';

echo "Testing SMTP to miansbb67@gmail.com...\n";
$res = send_smtp_email([
    'to_email' => 'miansbb67@gmail.com',
    'to_name' => 'Faizan',
    'subject' => 'CareerNexaa: 654321 is your verification code',
    'body_html' => '<p>Test verification code to miansbb67@gmail.com</p>',
    'debug' => false
]);
print_r($res);
