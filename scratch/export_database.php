<?php
/**
 * Export Production Database SQL Dump
 */
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

$tables = [
    'activity_logs',
    'admin_permissions',
    'admins',
    'article_reactions',
    'articles',
    'broken_links',
    'categories',
    'comments',
    'login_attempts',
    'pages',
    'permissions',
    'redirects',
    'section_visibility',
    'settings'
];

$sql = "-- ========================================================\n";
$sql .= "-- CareerNexaa Job Portal - Production Database Schema & Seed Data\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- Database: job_portal_db\n";
$sql .= "-- ========================================================\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
$sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
$sql .= "SET time_zone = '+00:00';\n\n";

foreach ($tables as $table) {
    $sql .= "-- --------------------------------------------------------\n";
    $sql .= "-- Table structure for table `{$table}`\n";
    $sql .= "-- --------------------------------------------------------\n";
    $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";

    $createStmt = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
    $createTable = $createStmt['Create Table'] ?? '';
    $sql .= $createTable . ";\n\n";

    // Dump Data if not a transient log table (broken_links, login_attempts, activity_logs, article_reactions, comments)
    if (!in_array($table, ['broken_links', 'login_attempts', 'activity_logs', 'article_reactions', 'comments'])) {
        $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $sql .= "-- Dumping data for table `{$table}`\n";
            foreach ($rows as $row) {
                $cols = array_map(function($c) { return "`" . str_replace("`", "``", $c) . "`"; }, array_keys($row));
                $vals = array_map(function($v) use ($db) {
                    if ($v === null) return 'NULL';
                    return $db->quote($v);
                }, array_values($row));
                $sql .= "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
            }
            $sql .= "\n";
        }
    }
}

$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

file_put_contents(__DIR__ . '/../database.sql', $sql);
echo "Clean database.sql generated successfully! (" . strlen($sql) . " bytes)\n";
