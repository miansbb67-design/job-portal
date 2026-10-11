<?php
$f = fopen('C:/Users/FineTechShop/.gemini/antigravity-ide/brain/9e70a1f1-4148-468f-bd4e-92515ad4c93a/.system_generated/logs/transcript.jsonl', 'r');
$i = 0;
while (($line = fgets($f)) !== false) {
    $i++;
    $data = json_decode($line, true);
    if (($data['type'] ?? '') === 'USER_INPUT') {
        echo "Line $i: " . substr($data['content'] ?? '', 0, 150) . "\n---\n";
    }
}
fclose($f);
