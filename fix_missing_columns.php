<?php
/**
 * Fix Missing Columns
 * Access: /fix_missing_columns.php?key=widgethook_reset_2024&fix=1
 */

define('FIX_KEY', 'widgethook_reset_2024');

if (!isset($_GET['key']) || $_GET['key'] !== FIX_KEY) {
    http_response_code(403);
    die('Unauthorized. Usage: fix_missing_columns.php?key=widgethook_reset_2024&fix=1');
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
echo "=== Fix Missing Columns ===\n\n";

$fixes = [
    // users.avatar
    [
        'table' => 'users',
        'column' => 'avatar',
        'sql' => "ALTER TABLE `users` ADD COLUMN `avatar` varchar(40) DEFAULT NULL"
    ],
    // payments.payer_id
    [
        'table' => 'payments',
        'column' => 'payer_id',
        'sql' => "ALTER TABLE `payments` ADD COLUMN `payer_id` varchar(64) DEFAULT NULL"
    ],
    // payments.subscription_id
    [
        'table' => 'payments',
        'column' => 'subscription_id',
        'sql' => "ALTER TABLE `payments` ADD COLUMN `subscription_id` varchar(64) DEFAULT NULL"
    ],
];

foreach ($fixes as $fix) {
    echo "Checking {$fix['table']}.{$fix['column']}:\n";

    $check = database()->query("SHOW COLUMNS FROM `{$fix['table']}` LIKE '{$fix['column']}'");

    if ($check->num_rows > 0) {
        echo "   Already exists - OK\n\n";
    } else {
        echo "   MISSING\n";

        if (!isset($_GET['fix'])) {
            echo "   Add &fix=1 to URL to fix\n\n";
        } else {
            $result = database()->query($fix['sql']);
            if ($result) {
                echo "   FIXED!\n\n";
            } else {
                echo "   ERROR: " . database()->error . "\n\n";
            }
        }
    }
}

// Add indexes if needed
if (isset($_GET['fix'])) {
    echo "Adding indexes...\n";

    // payer_id index
    $idx1 = database()->query("ALTER TABLE `payments` ADD INDEX `payments_payer_id_index` (`payer_id`)");
    echo "   payer_id index: " . ($idx1 ? "OK" : "skipped (may already exist)") . "\n";

    // subscription_id index
    $idx2 = database()->query("ALTER TABLE `payments` ADD INDEX `payments_subscription_id_index` (`subscription_id`)");
    echo "   subscription_id index: " . ($idx2 ? "OK" : "skipped (may already exist)") . "\n";
}

echo "\n=== Done ===\n";
echo "\nNow test: /admin/payments\n";
