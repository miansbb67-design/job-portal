<?php
$f = fopen('C:/Users/FineTechShop/.gemini/antigravity-ide/brain/9e70a1f1-4148-468f-bd4e-92515ad4c93a/.system_generated/logs/transcript.jsonl', 'r');
$i = 0;
while (($line = fgets($f)) !== false) {
    $i++;
    if ($i === 464) {
        $data = json_decode($line, true);
        echo "Thinking:\n" . ($data['thinking'] ?? '') . "\n";
        echo "Content:\n" . ($data['content'] ?? '') . "\n";
        break;
    }
}
fclose($f);
