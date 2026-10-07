<?php
/**
 * Automated Verification: Section Visibility Show / Hide System
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();
$baseUrl = 'http://localhost/job-portal';

echo "====================================================\n";
echo "1. VERIFYING DATABASE TABLE & DEFAULT SECTIONS\n";
echo "====================================================\n";

$rows = $db->query("SELECT section_key, section_name, is_enabled FROM section_visibility ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "Total sections found in DB: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo sprintf("- %-28s [%-16s] : %s\n", $r['section_name'], $r['section_key'], $r['is_enabled'] ? 'ENABLED (1)' : 'DISABLED (0)');
    if (!$r['is_enabled']) {
        die("ERROR: All sections must be ENABLED by default!\n");
    }
}

echo "\n====================================================\n";
echo "2. VERIFYING ALL SECTIONS RENDER ON HOMEPAGE\n";
echo "====================================================\n";

$homeHtml = file_get_contents($baseUrl . '/');

$expectedStrings = [
    'Header'            => 'id="site-header"',
    'Hero Spotlight'    => 'editorial-spotlight-card',
    'Live Job Radar'    => 'career-radar-widget',
    'Top Categories'    => 'sector-tracks-grid',
    'Trending Guides'   => 'Trending Career Guides',
    'Jobs Section'      => 'job-postings-strip',
    'Career Toolkit'    => 'career-toolkit-section',
    'Latest Articles'   => 'Latest Articles & News',
    'Footer'            => 'class="site-footer"',
];

foreach ($expectedStrings as $name => $needle) {
    $found = (strpos($homeHtml, $needle) !== false);
    echo sprintf("%-25s : %s\n", $name, $found ? "PRESENT [OK]" : "MISSING [FAIL]");
    if (!$found) {
        die("ERROR: Expected section '{$name}' not found on default homepage!\n");
    }
}

echo "\n====================================================\n";
echo "3. TESTING TOGGLE: CAREER SUCCESS TOOLKIT (OFF -> ON)\n";
echo "====================================================\n";

// A. Turn OFF
set_section_visibility('career_toolkit', 0);
$htmlOff = file_get_contents($baseUrl . '/');
$hasToolkitOff = (strpos($htmlOff, 'career-toolkit-section') !== false);
echo "Toolkit turned OFF -> Present in HTML: " . ($hasToolkitOff ? "YES [FAIL]" : "NO [PASSED - Completely Hidden]") . "\n";
if ($hasToolkitOff) die("Toolkit was not hidden!\n");

// B. Turn back ON
set_section_visibility('career_toolkit', 1);
$htmlOn = file_get_contents($baseUrl . '/');
$hasToolkitOn = (strpos($htmlOn, 'career-toolkit-section') !== false);
echo "Toolkit turned ON  -> Present in HTML: " . ($hasToolkitOn ? "YES [PASSED - Completely Restored]" : "NO [FAIL]") . "\n";
if (!$hasToolkitOn) die("Toolkit was not restored!\n");

echo "\n====================================================\n";
echo "4. TESTING TOGGLE: TRENDING GUIDES (OFF -> ON)\n";
echo "====================================================\n";

// A. Turn OFF
set_section_visibility('trending_guides', 0);
$htmlOff = file_get_contents($baseUrl . '/');
$hasTrendOff = (strpos($htmlOff, 'Trending Career Guides') !== false);
echo "Trending Guides OFF -> Present in HTML: " . ($hasTrendOff ? "YES [FAIL]" : "NO [PASSED - Completely Hidden]") . "\n";
if ($hasTrendOff) die("Trending Guides was not hidden!\n");

// B. Turn back ON
set_section_visibility('trending_guides', 1);
$htmlOn = file_get_contents($baseUrl . '/');
$hasTrendOn = (strpos($htmlOn, 'Trending Career Guides') !== false);
echo "Trending Guides ON  -> Present in HTML: " . ($hasTrendOn ? "YES [PASSED - Completely Restored]" : "NO [FAIL]") . "\n";
if (!$hasTrendOn) die("Trending Guides was not restored!\n");

echo "\n====================================================\n";
echo "5. TESTING TOGGLE: HERO & RADAR INDEPENDENT HIDING\n";
echo "====================================================\n";

// A. Hide only Spotlight
set_section_visibility('hero_featured', 0);
$html = file_get_contents($baseUrl . '/');
$hasSpot = (strpos($html, 'editorial-spotlight-card') !== false);
$hasRadar = (strpos($html, 'career-radar-widget') !== false);
echo "Spotlight OFF, Radar ON -> Spotlight hidden: " . (!$hasSpot ? "YES [PASSED]" : "NO [FAIL]") . ", Radar visible: " . ($hasRadar ? "YES [PASSED]" : "NO [FAIL]") . "\n";

// B. Hide both Spotlight and Radar
set_section_visibility('live_job_radar', 0);
$html = file_get_contents($baseUrl . '/');
$hasHeroSec = (strpos($html, 'cp-hero-section') !== false);
echo "Both OFF -> Entire cp-hero-section removed: " . (!$hasHeroSec ? "YES [PASSED - Zero Empty Space]" : "NO [FAIL]") . "\n";

// C. Restore both
set_section_visibility('hero_featured', 1);
set_section_visibility('live_job_radar', 1);
$html = file_get_contents($baseUrl . '/');
$hasHeroRestored = (strpos($html, 'editorial-spotlight-card') !== false && strpos($html, 'career-radar-widget') !== false);
echo "Both ON -> Restored completely: " . ($hasHeroRestored ? "YES [PASSED]" : "NO [FAIL]") . "\n";

echo "\n====================================================\n";
echo "6. ENSURING FINAL STATE: ALL 9 SECTIONS ARE ENABLED\n";
echo "====================================================\n";

$db->exec("UPDATE section_visibility SET is_enabled = 1");
$allCount = (int)$db->query("SELECT COUNT(*) FROM section_visibility WHERE is_enabled = 1")->fetchColumn();
echo "Total Enabled Sections: {$allCount} / 9.\n";
echo "ALL SECTION VISIBILITY TESTS COMPLETED SUCCESSFULLY!\n";
