<?php
$html = file_get_contents('http://localhost/job-portal/admin/login.php');
if (preg_match_all('/<div class="alert-error"[^>]*>(.*?)<\/div>/s', $html, $m)) {
    echo "Found alert-error in direct fetch:\n";
    foreach ($m[0] as $match) {
        echo strip_tags($match) . "\n---\n";
    }
} else {
    echo "No alert-error found in direct fetch!\n";
}
