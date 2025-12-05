<?php
/**
 * Check ALL database tables for missing columns
 *
 * Access: /check_all_tables.php?key=widgethook_fix_2024
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'widgethook_fix_2024') {
    die("Unauthorized");
}

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

echo "=== DATABASE SCHEMA CHECK ===\n\n";

// Load config directly
$config = file_get_contents(__DIR__ . '/config.php');
preg_match("/define\s*\(\s*['\"]DATABASE_SERVER['\"]\s*,\s*['\"]([^'\"]*)/", $config, $m1);
preg_match("/define\s*\(\s*['\"]DATABASE_NAME['\"]\s*,\s*['\"]([^'\"]*)/", $config, $m2);
preg_match("/define\s*\(\s*['\"]DATABASE_USERNAME['\"]\s*,\s*['\"]([^'\"]*)/", $config, $m3);
preg_match("/define\s*\(\s*['\"]DATABASE_PASSWORD['\"]\s*,\s*['\"]([^'\"]*)/", $config, $m4);

$mysqli = new mysqli($m1[1], $m3[1], $m4[1], $m2[1]);
$mysqli->set_charset('utf8mb4');

// Load schema.sql to get expected columns
$schema = file_get_contents(__DIR__ . '/install/schema.sql');

// Parse CREATE TABLE statements
preg_match_all('/CREATE TABLE[^`]*`([^`]+)`[^(]*\(([^;]+)\)\s*ENGINE/s', $schema, $matches, PREG_SET_ORDER);

$issues = [];

foreach ($matches as $match) {
    $table_name = $match[1];
    $table_def = $match[2];

    // Extract column names from schema
    $expected_columns = [];
    $lines = explode("\n", $table_def);
    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^`([^`]+)`/', $line, $col_match)) {
            $expected_columns[] = $col_match[1];
        }
    }

    // Check if table exists and get actual columns
    $result = $mysqli->query("DESCRIBE `$table_name`");
    if ($result) {
        $actual_columns = [];
        while ($row = $result->fetch_assoc()) {
            $actual_columns[] = $row['Field'];
        }

        $missing = array_diff($expected_columns, $actual_columns);
        if (!empty($missing)) {
            $issues[$table_name] = $missing;
            echo "TABLE: $table_name\n";
            echo "  Missing: " . implode(', ', $missing) . "\n\n";
        }
    } else {
        echo "TABLE: $table_name - NOT FOUND IN DATABASE\n\n";
        $issues[$table_name] = ['TABLE_MISSING'];
    }
}

echo "\n=== SUMMARY ===\n";
if (empty($issues)) {
    echo "All tables have all expected columns!\n";
} else {
    echo "Found " . count($issues) . " tables with issues.\n";

    // Generate SQL fixes
    echo "\n=== SQL FIXES ===\n";
    foreach ($issues as $table => $missing_cols) {
        if ($missing_cols[0] === 'TABLE_MISSING') continue;

        foreach ($missing_cols as $col) {
            // Try to get column definition from schema
            preg_match("/`$col`\s+([^,\n]+)/", $schema, $def);
            if ($def) {
                echo "ALTER TABLE `$table` ADD COLUMN `$col` " . trim($def[1]) . ";\n";
            }
        }
    }
}

$mysqli->close();
echo "\n*** DELETE THIS FILE WHEN DONE ***\n";
