<?php
/**
 * STANDALONE Database Fix Script
 * This script does NOT load init.php - it connects directly to the database
 *
 * Access: /standalone_fix.php?key=widgethook_fix_2024
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'widgethook_fix_2024') {
    http_response_code(403);
    die("Unauthorized. Use: standalone_fix.php?key=widgethook_fix_2024");
}

// Force error display - no error handlers
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "=== STANDALONE DATABASE FIX ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP: " . PHP_VERSION . "\n\n";

// Step 1: Load config.php directly (it just defines constants)
echo "[1] Loading config.php...\n";
$config_file = __DIR__ . '/config.php';
if (!file_exists($config_file)) {
    die("ERROR: config.php not found at: $config_file\n");
}

// Read config.php to extract database credentials
$config_content = file_get_contents($config_file);

// Extract database credentials using regex
preg_match("/define\s*\(\s*['\"]DATABASE_SERVER['\"]\s*,\s*['\"]([^'\"]*)/", $config_content, $m_server);
preg_match("/define\s*\(\s*['\"]DATABASE_NAME['\"]\s*,\s*['\"]([^'\"]*)/", $config_content, $m_name);
preg_match("/define\s*\(\s*['\"]DATABASE_USERNAME['\"]\s*,\s*['\"]([^'\"]*)/", $config_content, $m_user);
preg_match("/define\s*\(\s*['\"]DATABASE_PASSWORD['\"]\s*,\s*['\"]([^'\"]*)/", $config_content, $m_pass);

$db_server = $m_server[1] ?? null;
$db_name = $m_name[1] ?? null;
$db_user = $m_user[1] ?? null;
$db_pass = $m_pass[1] ?? null;

if (!$db_server || !$db_name || !$db_user) {
    die("ERROR: Could not extract database credentials from config.php\n");
}

echo "    Server: $db_server\n";
echo "    Database: $db_name\n";
echo "    User: $db_user\n";
echo "    Password: " . str_repeat('*', strlen($db_pass)) . "\n";

// Step 2: Connect to database
echo "\n[2] Connecting to database...\n";
try {
    $mysqli = new mysqli($db_server, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_error) {
        die("    Connection failed: " . $mysqli->connect_error . "\n");
    }
    echo "    Connected successfully!\n";
    $mysqli->set_charset('utf8mb4');
} catch (Exception $e) {
    die("    Connection error: " . $e->getMessage() . "\n");
}

// Step 3: Check pages table structure
echo "\n[3] Checking pages table structure...\n";
$result = $mysqli->query("DESCRIBE `pages`");
if (!$result) {
    die("    ERROR: Could not describe pages table: " . $mysqli->error . "\n");
}

$existing_columns = [];
while ($row = $result->fetch_assoc()) {
    $existing_columns[] = $row['Field'];
    echo "    - " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

// Step 4: Add missing columns
echo "\n[4] Adding missing columns to pages table...\n";

$columns_to_add = [
    'language' => "ALTER TABLE `pages` ADD COLUMN `language` varchar(32) DEFAULT NULL",
    'icon' => "ALTER TABLE `pages` ADD COLUMN `icon` varchar(64) DEFAULT NULL",
    'plans_ids' => "ALTER TABLE `pages` ADD COLUMN `plans_ids` text DEFAULT NULL",
    'is_published' => "ALTER TABLE `pages` ADD COLUMN `is_published` tinyint(4) NOT NULL DEFAULT 1",
];

foreach ($columns_to_add as $column => $sql) {
    if (!in_array($column, $existing_columns)) {
        echo "    Adding '$column'... ";
        if ($mysqli->query($sql)) {
            echo "OK\n";
        } else {
            echo "ERROR: " . $mysqli->error . "\n";
        }
    } else {
        echo "    '$column' already exists\n";
    }
}

// Step 5: Verify fix
echo "\n[5] Verifying pages table...\n";
$result = $mysqli->query("DESCRIBE `pages`");
$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
}
echo "    Pages table now has $count columns\n";

// Step 6: Test the query
echo "\n[6] Testing the problematic query...\n";
$test_query = "SELECT `url`, `title`, `type`, `open_in_new_tab`, `language`, `icon`, `position`, `plans_ids` FROM `pages` WHERE `is_published` = 1 ORDER BY `order`";
$result = $mysqli->query($test_query);
if ($result) {
    echo "    Query works! Found " . $result->num_rows . " rows.\n";
} else {
    echo "    Query failed: " . $mysqli->error . "\n";
}

// Step 7: Clear cache files
echo "\n[7] Clearing cache...\n";
$cache_dir = __DIR__ . '/uploads/cache';
if (is_dir($cache_dir)) {
    $files = glob($cache_dir . '/*');
    $deleted = 0;
    foreach ($files as $file) {
        if (is_file($file) && basename($file) != '.gitkeep') {
            unlink($file);
            $deleted++;
        }
    }
    echo "    Deleted $deleted cache files\n";
} else {
    echo "    Cache directory not found\n";
}

// Step 8: Check for other potential issues
echo "\n[8] Checking for other potential database issues...\n";

// Check if settings table exists and has data
$result = $mysqli->query("SELECT COUNT(*) as cnt FROM `settings`");
if ($result) {
    $row = $result->fetch_assoc();
    echo "    Settings table: " . $row['cnt'] . " rows\n";
} else {
    echo "    Settings table error: " . $mysqli->error . "\n";
}

// Check users table
$result = $mysqli->query("SELECT COUNT(*) as cnt FROM `users`");
if ($result) {
    $row = $result->fetch_assoc();
    echo "    Users table: " . $row['cnt'] . " rows\n";
} else {
    echo "    Users table error: " . $mysqli->error . "\n";
}

// Check plans table
$result = $mysqli->query("SELECT COUNT(*) as cnt FROM `plans`");
if ($result) {
    $row = $result->fetch_assoc();
    echo "    Plans table: " . $row['cnt'] . " rows\n";
} else {
    echo "    Plans table error: " . $mysqli->error . "\n";
}

$mysqli->close();

echo "\n=== FIX COMPLETE ===\n";
echo "\nNow try loading: https://landingo.net/\n";
echo "\n*** DELETE THIS FILE WHEN DONE ***\n";
