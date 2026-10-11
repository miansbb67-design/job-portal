<?php
// Mock server variables
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';

ob_start();
try {
    include __DIR__ . '/../index.php';
    $output = ob_get_clean();
    echo "SUCCESS! Rendered length: " . strlen($output) . " bytes\n";
    
    // Check if bell is in output
    if (strpos($output, 'site-bell-btn') !== false) {
        echo "PASS: site-bell-btn found in HTML\n";
    } else {
        echo "FAIL: site-bell-btn NOT found\n";
    }

    if (strpos($output, 'site-notif-dropdown') !== false) {
        echo "PASS: site-notif-dropdown found in HTML\n";
    } else {
        echo "FAIL: site-notif-dropdown NOT found\n";
    }

    // Check if old notification banner box is NOT in index body
    if (strpos($output, 'Instant Job & Career Notifications') !== false) {
        // Note: Title might be in the dropdown, but check if the old banner container exists
        if (strpos($output, 'newsletter-section') !== false || strpos($output, 'notif-card-body') !== false) {
            echo "FAIL: Old banner section found in body!\n";
        } else {
            echo "PASS: No old banner card in body (Instant Job is only in header bell dropdown)\n";
        }
    } else {
        echo "PASS: Banner not in body\n";
    }

} catch (Throwable $e) {
    ob_end_clean();
    echo "ERROR: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}
