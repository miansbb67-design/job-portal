<?php
$ch = curl_init('http://careernexaa.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_RESOLVE, ['careernexaa.com:80:37.27.52.96', 'careernexaa.com:443:37.27.52.96']);
$res = curl_exec($ch);
curl_close($ch);

echo "FULL LIVE SERVER RESPONSE:\n";
echo strip_tags($res) . "\n";
