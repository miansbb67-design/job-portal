<?php
$f = fopen('C:/Users/FineTechShop/.gemini/antigravity-ide/brain/9e70a1f1-4148-468f-bd4e-92515ad4c93a/.system_generated/logs/transcript.jsonl', 'r');
$i = 0;
while (($line = fgets($f)) !== false) {
    $i++;
    if ($i >= 464 && $i <= 475) {
        $data = json_decode($line, true);
        echo "[$i] Type: " . ($data['type'] ?? '') . " | Tool: " . (isset($data['tool_calls'][0]['name']) ? $data['tool_calls'][0]['name'] : '') . " | Content: " . substr($data['content'] ?? '', 0, 100) . "\n";
    }
}
fclose($f);
