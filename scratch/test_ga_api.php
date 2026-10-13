<?php
$apiKey = 'AIzaSyCCWXlnB6t_OwDg_yzTZ3UuT3i2-v75jBQ';

// Test 1: Simple cURL to Analytics Data API discovery or properties endpoint
$url = "https://analyticsdata.googleapis.com/v1beta/properties/0:runRealtimeReport?key=" . urlencode($apiKey);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'metrics' => [['name' => 'activeUsers']]
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $resp\n";
