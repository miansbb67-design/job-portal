<?php
echo "Testing HTTP connection to careernexaa.com...\n";

// Let's resolve via 37.27.52.96 directly if local DNS fails
$ch = curl_init('http://careernexaa.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RESOLVE, ['careernexaa.com:80:37.27.52.96', 'careernexaa.com:443:37.27.52.96']);
$res = curl_exec($ch);
$info = curl_getinfo($ch);
$err = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Effective URL: " . $info['url'] . "\n";
if ($err) {
    echo "cURL Error: " . $err . "\n";
} else {
    echo "Response preview (first 400 chars):\n";
    echo substr($res, 0, 400) . "\n";
}

echo "\nTesting HTTPS...\n";
$ch2 = curl_init('https://careernexaa.com');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch2, CURLOPT_TIMEOUT, 15);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_RESOLVE, ['careernexaa.com:80:37.27.52.96', 'careernexaa.com:443:37.27.52.96']);
$res2 = curl_exec($ch2);
$info2 = curl_getinfo($ch2);
$err2 = curl_error($ch2);
curl_close($ch2);

echo "HTTPS Code: " . $info2['http_code'] . "\n";
if ($err2) {
    echo "cURL Error (HTTPS): " . $err2 . "\n";
} else {
    echo "HTTPS Response preview:\n";
    echo substr($res2, 0, 400) . "\n";
}
