<?php
$url = "http://localhost/job-portal/article.php?slug=transitioning-into-ai-data-analytics-practical-6-month-roadmap";
$html = file_get_contents($url);
echo "HTML Length: " . strlen($html) . "\n";
if (strpos($html, "Sehar Aqeel") !== false) {
    echo "SUCCESS: 'Sehar Aqeel' is in HTML!\n";
} else {
    echo "MISSING: 'Sehar Aqeel' is NOT in HTML!\n";
}
if (strpos($html, "hii") !== false) {
    echo "SUCCESS: 'hii' is in HTML!\n";
} else {
    echo "MISSING: 'hii' is NOT in HTML!\n";
}
