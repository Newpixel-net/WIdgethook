<?php
/**
 * Database Fix - Add missing payments table
 * Access: /fix_payments_table.php?key=widgethook_reset_2024
 */

define('FIX_KEY', 'widgethook_reset_2024');

if (!isset($_GET['key']) || $_GET['key'] !== FIX_KEY) {
    http_response_code(403);
    die('Unauthorized. Usage: fix_payments_table.php?key=widgethook_reset_2024');
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
echo "=== Payments Table Fix ===\n\n";

// Check if payments table exists
$table_check = database()->query("SHOW TABLES LIKE 'payments'");
if ($table_check->num_rows > 0) {
    echo "payments table already exists!\n";

    // Show columns
    $cols = database()->query("SHOW COLUMNS FROM `payments`");
    echo "Columns: ";
    $col_names = [];
    while ($col = $cols->fetch_object()) {
        $col_names[] = $col->Field;
    }
    echo implode(', ', $col_names) . "\n";

} else {
    echo "payments table MISSING - creating it...\n\n";

    if (!isset($_GET['fix'])) {
        echo "To create the table, add &fix=1 to the URL:\n";
        echo "fix_payments_table.php?key=widgethook_reset_2024&fix=1\n";
    } else {
        // Create the payments table
        $create_sql = "
        CREATE TABLE IF NOT EXISTS `payments` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `plan_id` varchar(16) DEFAULT NULL,
            `payment_id` varchar(128) DEFAULT NULL,
            `payment_proof` varchar(40) DEFAULT NULL,
            `payer_id` varchar(64) DEFAULT NULL,
            `subscription_id` varchar(64) DEFAULT NULL,
            `email` varchar(256) DEFAULT NULL,
            `name` varchar(256) DEFAULT NULL,
            `plan` text DEFAULT NULL,
            `processor` varchar(32) DEFAULT NULL,
            `type` varchar(32) DEFAULT NULL,
            `frequency` varchar(32) DEFAULT NULL,
            `billing` text DEFAULT NULL,
            `taxes_ids` text DEFAULT NULL,
            `base_amount` float DEFAULT NULL,
            `code` varchar(32) DEFAULT NULL,
            `discount_amount` float DEFAULT NULL,
            `total_amount` float DEFAULT NULL,
            `total_amount_default_currency` float DEFAULT NULL,
            `currency` varchar(4) DEFAULT NULL,
            `status` tinyint(4) DEFAULT 1,
            `datetime` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `payments_payment_id_index` (`payment_id`),
            KEY `payments_payer_id_index` (`payer_id`),
            KEY `payments_subscription_id_index` (`subscription_id`),
            KEY `payments_plan_id_index` (`plan_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $result = database()->query($create_sql);

        if ($result) {
            echo "SUCCESS: payments table created!\n";
        } else {
            echo "ERROR: " . database()->error . "\n";
        }
    }
}

// Also check for codes table (needed for discounts)
echo "\n=== Codes Table Check ===\n";
$codes_check = database()->query("SHOW TABLES LIKE 'codes'");
if ($codes_check->num_rows > 0) {
    echo "codes table exists\n";
} else {
    echo "codes table MISSING\n";
    if (isset($_GET['fix'])) {
        $create_codes = "
        CREATE TABLE IF NOT EXISTS `codes` (
            `code_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` varchar(64) DEFAULT NULL,
            `type` varchar(32) DEFAULT NULL,
            `days` int(11) DEFAULT NULL,
            `code` varchar(32) NOT NULL DEFAULT '',
            `discount` int(11) NOT NULL DEFAULT 0,
            `quantity` int(11) NOT NULL DEFAULT 1,
            `redeemed` int(11) NOT NULL DEFAULT 0,
            `is_enabled` tinyint(4) NOT NULL DEFAULT 1,
            `datetime` datetime NOT NULL,
            PRIMARY KEY (`code_id`),
            KEY `code` (`code`),
            KEY `type` (`type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        if (database()->query($create_codes)) {
            echo "codes table created!\n";
        }
    }
}

// Check for redeemed_codes table
echo "\n=== Redeemed Codes Table Check ===\n";
$redeemed_check = database()->query("SHOW TABLES LIKE 'redeemed_codes'");
if ($redeemed_check->num_rows > 0) {
    echo "redeemed_codes table exists\n";
} else {
    echo "redeemed_codes table MISSING\n";
    if (isset($_GET['fix'])) {
        $create_redeemed = "
        CREATE TABLE IF NOT EXISTS `redeemed_codes` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `code_id` int(11) UNSIGNED NOT NULL,
            `user_id` int(11) NOT NULL,
            `datetime` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `code_id` (`code_id`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        if (database()->query($create_redeemed)) {
            echo "redeemed_codes table created!\n";
        }
    }
}

echo "\n=== Done ===\n";
echo "\nAfter fixing, test: /admin/payments\n";
