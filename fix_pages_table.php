<?php
/**
 * Fix missing columns in pages table
 *
 * Access: /fix_pages_table.php?key=widgethook_fix_2024
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'widgethook_fix_2024') {
    http_response_code(403);
    die("Unauthorized");
}

header('Content-Type: text/plain');
echo "=== FIX PAGES TABLE ===\n\n";

// Load the application
const DEBUG = 0;
const MYSQL_DEBUG = 0;
const LOGGING = 0;
const CACHE = 0;
const ALTUMCODE = 66;
require_once realpath(__DIR__) . '/app/init.php';

$db = db();

// Check current columns in pages table
echo "[1] Checking current pages table structure...\n";
$result = $db->rawQuery("DESCRIBE `pages`");
$existing_columns = [];
foreach ($result as $row) {
    $existing_columns[] = $row['Field'];
    echo "    - " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n[2] Adding missing columns...\n";

// List of columns to add
$columns_to_add = [
    'language' => "ALTER TABLE `pages` ADD COLUMN `language` varchar(32) DEFAULT NULL AFTER `position`",
    'icon' => "ALTER TABLE `pages` ADD COLUMN `icon` varchar(64) DEFAULT NULL AFTER `language`",
    'plans_ids' => "ALTER TABLE `pages` ADD COLUMN `plans_ids` text DEFAULT NULL AFTER `icon`",
    'is_published' => "ALTER TABLE `pages` ADD COLUMN `is_published` tinyint(4) NOT NULL DEFAULT 1 AFTER `plans_ids`",
];

$changes_made = false;
foreach ($columns_to_add as $column => $sql) {
    if (!in_array($column, $existing_columns)) {
        echo "    Adding column: $column ... ";
        try {
            $db->rawQuery($sql);
            echo "OK\n";
            $changes_made = true;
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    } else {
        echo "    Column '$column' already exists.\n";
    }
}

if (!$changes_made) {
    echo "    No changes needed - all columns exist.\n";
}

// Verify the fix
echo "\n[3] Verifying fix...\n";
$result = $db->rawQuery("DESCRIBE `pages`");
echo "    Pages table now has " . count($result) . " columns:\n";
foreach ($result as $row) {
    echo "    - " . $row['Field'] . "\n";
}

// Test the query that was failing
echo "\n[4] Testing the query that was failing...\n";
try {
    $result = $db->rawQuery("SELECT `url`, `title`, `type`, `open_in_new_tab`, `language`, `icon`, `position`, `plans_ids` FROM `pages` WHERE `is_published` = 1 ORDER BY `order`");
    echo "    Query executed successfully!\n";
    echo "    Found " . count($result) . " published pages.\n";
} catch (Exception $e) {
    echo "    ERROR: " . $e->getMessage() . "\n";
}

// Clear cache
echo "\n[5] Clearing cache...\n";
try {
    cache()->deleteItemsByTag('pages');
    echo "    Cache cleared.\n";
} catch (Exception $e) {
    echo "    Cache clear failed (not critical): " . $e->getMessage() . "\n";
}

echo "\n=== FIX COMPLETE ===\n";
echo "\nTry loading your site again!\n";
echo "\n*** DELETE THIS FILE WHEN DONE ***\n";
