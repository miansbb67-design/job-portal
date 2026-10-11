<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/job-portal/index.php';

ob_start();
require __DIR__ . '/../index.php';
$html = ob_get_clean();

if (strpos($html, 'home-notification-box') !== false) {
    echo "[OK] home-notification-box found in index.php\n";
} else {
    echo "[FAIL] home-notification-box not found\n";
}

if (strpos($html, 'btnEnableNotifications') !== false) {
    echo "[OK] btnEnableNotifications found in index.php\n";
} else {
    echo "[FAIL] btnEnableNotifications not found\n";
}

if (strpos($html, 'Enter your work email address') !== false) {
    echo "[WARN] Old newsletter still present\n";
} else {
    echo "[OK] Old newsletter email box completely removed!\n";
}
