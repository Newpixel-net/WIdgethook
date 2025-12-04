<?php
/**
 * Comprehensive Database Schema Fix
 * Adds all missing columns to tables
 *
 * Access: /fix_schema.php?key=widgethook_reset_2024&fix=1
 */

define('FIX_KEY', 'widgethook_reset_2024');

if (!isset($_GET['key']) || $_GET['key'] !== FIX_KEY) {
    http_response_code(403);
    die('Unauthorized. Usage: fix_schema.php?key=widgethook_reset_2024&fix=1');
}

ob_start();

const DEBUG = 1;
const MYSQL_DEBUG = 0;
const LOGGING = 0;
const CACHE = 1;
const ALTUMCODE = 66;

require_once __DIR__ . '/app/init.php';

ob_end_clean();

header('Content-Type: text/plain');
echo "=== Comprehensive Schema Fix ===\n\n";

// All columns that need to exist
$required_columns = [
    'users' => [
        'avatar' => "ALTER TABLE `users` ADD COLUMN `avatar` varchar(40) DEFAULT NULL",
        'is_newsletter_subscribed' => "ALTER TABLE `users` ADD COLUMN `is_newsletter_subscribed` tinyint(4) DEFAULT 0",
        'source' => "ALTER TABLE `users` ADD COLUMN `source` varchar(32) DEFAULT NULL",
        'continent_code' => "ALTER TABLE `users` ADD COLUMN `continent_code` varchar(8) DEFAULT NULL",
        'city_name' => "ALTER TABLE `users` ADD COLUMN `city_name` varchar(64) DEFAULT NULL",
        'device_type' => "ALTER TABLE `users` ADD COLUMN `device_type` varchar(16) DEFAULT NULL",
        'os_name' => "ALTER TABLE `users` ADD COLUMN `os_name` varchar(32) DEFAULT NULL",
        'browser_name' => "ALTER TABLE `users` ADD COLUMN `browser_name` varchar(32) DEFAULT NULL",
        'browser_language' => "ALTER TABLE `users` ADD COLUMN `browser_language` varchar(8) DEFAULT NULL",
        'extra' => "ALTER TABLE `users` ADD COLUMN `extra` text DEFAULT NULL",
        'preferences' => "ALTER TABLE `users` ADD COLUMN `preferences` text DEFAULT NULL",
        'anti_phishing_code' => "ALTER TABLE `users` ADD COLUMN `anti_phishing_code` varchar(8) DEFAULT NULL",
        'currency' => "ALTER TABLE `users` ADD COLUMN `currency` varchar(4) DEFAULT NULL",
    ],
    'payments' => [
        'payer_id' => "ALTER TABLE `payments` ADD COLUMN `payer_id` varchar(64) DEFAULT NULL",
        'subscription_id' => "ALTER TABLE `payments` ADD COLUMN `subscription_id` varchar(64) DEFAULT NULL",
    ],
    'track_notifications' => [
        'user_id' => "ALTER TABLE `track_notifications` ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `id`",
    ],
];

$dry_run = !isset($_GET['fix']);

if ($dry_run) {
    echo "DRY RUN MODE - Add &fix=1 to actually make changes\n\n";
}

$issues_found = 0;
$issues_fixed = 0;

foreach ($required_columns as $table => $columns) {
    echo "=== Table: {$table} ===\n";

    // Check if table exists
    $table_check = database()->query("SHOW TABLES LIKE '{$table}'");
    if ($table_check->num_rows === 0) {
        echo "   TABLE DOES NOT EXIST - Skipping\n\n";
        continue;
    }

    // Get existing columns
    $existing_result = database()->query("SHOW COLUMNS FROM `{$table}`");
    $existing = [];
    while ($col = $existing_result->fetch_object()) {
        $existing[$col->Field] = true;
    }

    foreach ($columns as $column => $sql) {
        if (isset($existing[$column])) {
            echo "   {$column}: OK\n";
        } else {
            $issues_found++;
            echo "   {$column}: MISSING";

            if (!$dry_run) {
                $result = database()->query($sql);
                if ($result) {
                    echo " -> FIXED!\n";
                    $issues_fixed++;
                } else {
                    echo " -> ERROR: " . database()->error . "\n";
                }
            } else {
                echo "\n";
            }
        }
    }
    echo "\n";
}

// Add indexes
if (!$dry_run && $issues_fixed > 0) {
    echo "=== Adding Indexes ===\n";

    $indexes = [
        "ALTER TABLE `track_notifications` ADD INDEX `track_notifications_user_id_index` (`user_id`)",
        "ALTER TABLE `payments` ADD INDEX `payments_payer_id_index` (`payer_id`)",
        "ALTER TABLE `payments` ADD INDEX `payments_subscription_id_index` (`subscription_id`)",
    ];

    foreach ($indexes as $idx_sql) {
        $result = @database()->query($idx_sql);
        echo "   " . ($result ? "Index added" : "Index skipped (may exist)") . "\n";
    }
}

echo "\n=== Summary ===\n";
echo "Issues found: {$issues_found}\n";
if (!$dry_run) {
    echo "Issues fixed: {$issues_fixed}\n";
}

if ($dry_run && $issues_found > 0) {
    echo "\nTo fix these issues, run:\n";
    echo "fix_schema.php?key=widgethook_reset_2024&fix=1\n";
}

echo "\n=== Done ===\n";
