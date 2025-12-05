<?php
/**
 * Fix Missing Tables
 * Creates all required tables that may be missing
 *
 * Access: /fix_tables.php?key=widgethook_reset_2024&fix=1
 */

define('FIX_KEY', 'widgethook_reset_2024');

if (!isset($_GET['key']) || $_GET['key'] !== FIX_KEY) {
    http_response_code(403);
    die('Unauthorized. Usage: fix_tables.php?key=widgethook_reset_2024&fix=1');
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
echo "=== Fix Missing Tables ===\n\n";

$dry_run = !isset($_GET['fix']);

if ($dry_run) {
    echo "DRY RUN MODE - Add &fix=1 to actually make changes\n\n";
}

// Tables that must exist
$required_tables = [
    'users_logs' => "CREATE TABLE IF NOT EXISTS `users_logs` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `type` varchar(64) DEFAULT NULL,
        `ip` varchar(64) DEFAULT NULL,
        `device_type` varchar(16) DEFAULT NULL,
        `os_name` varchar(16) DEFAULT NULL,
        `country_code` varchar(8) DEFAULT NULL,
        `datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `users_logs_user_id` (`user_id`),
        KEY `users_logs_ip` (`ip`),
        KEY `users_logs_type` (`type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'internal_notifications' => "CREATE TABLE IF NOT EXISTS `internal_notifications` (
        `internal_notification_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `for_who` varchar(16) DEFAULT NULL,
        `from_who` varchar(16) DEFAULT NULL,
        `icon` varchar(64) DEFAULT NULL,
        `title` varchar(128) DEFAULT NULL,
        `description` varchar(1024) DEFAULT NULL,
        `url` varchar(512) DEFAULT NULL,
        `is_read` tinyint(4) DEFAULT 0,
        `datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`internal_notification_id`),
        KEY `internal_notifications_user_id` (`user_id`),
        KEY `internal_notifications_for_who` (`for_who`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'plans' => "CREATE TABLE IF NOT EXISTS `plans` (
        `plan_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` varchar(256) NOT NULL DEFAULT '',
        `description` varchar(256) NOT NULL DEFAULT '',
        `monthly_price` float DEFAULT NULL,
        `annual_price` float DEFAULT NULL,
        `lifetime_price` float DEFAULT NULL,
        `trial_days` int(11) UNSIGNED DEFAULT 0,
        `settings` text DEFAULT NULL,
        `taxes_ids` text DEFAULT NULL,
        `codes_ids` text DEFAULT NULL,
        `color` varchar(16) DEFAULT NULL,
        `status` tinyint(4) NOT NULL DEFAULT 0,
        `order` int(10) UNSIGNED DEFAULT 0,
        `datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`plan_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'redeemed_codes' => "CREATE TABLE IF NOT EXISTS `redeemed_codes` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `code_id` int(11) DEFAULT NULL,
        `user_id` int(11) DEFAULT NULL,
        `datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `redeemed_codes_code_id` (`code_id`),
        KEY `redeemed_codes_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

$issues_found = 0;
$issues_fixed = 0;

foreach ($required_tables as $table => $create_sql) {
    echo "Checking table: {$table}\n";

    $check = database()->query("SHOW TABLES LIKE '{$table}'");

    if ($check->num_rows > 0) {
        echo "   EXISTS - OK\n\n";
    } else {
        $issues_found++;
        echo "   MISSING";

        if (!$dry_run) {
            $result = database()->query($create_sql);
            if ($result) {
                echo " -> CREATED!\n\n";
                $issues_fixed++;
            } else {
                echo " -> ERROR: " . database()->error . "\n\n";
            }
        } else {
            echo "\n\n";
        }
    }
}

echo "=== Summary ===\n";
echo "Issues found: {$issues_found}\n";
if (!$dry_run) {
    echo "Issues fixed: {$issues_fixed}\n";
}

if ($dry_run && $issues_found > 0) {
    echo "\nTo fix these issues, run:\n";
    echo "fix_tables.php?key=widgethook_reset_2024&fix=1\n";
}

echo "\n=== Done ===\n";
