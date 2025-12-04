<?php
/**
 * Database Fix - Add missing user_id column to track_notifications
 * Access: /fix_db.php?key=widgethook_reset_2024
 */

define('FIX_KEY', 'widgethook_reset_2024');

if (!isset($_GET['key']) || $_GET['key'] !== FIX_KEY) {
    http_response_code(403);
    die('Unauthorized. Usage: fix_db.php?key=widgethook_reset_2024');
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
echo "=== Database Schema Fix ===\n\n";

// Check current columns in track_notifications
echo "1. Checking track_notifications table:\n";
$columns_result = database()->query("SHOW COLUMNS FROM `track_notifications`");

if ($columns_result === false) {
    die("   ERROR: Table doesn't exist! MySQL error: " . database()->error . "\n");
}

$columns = [];
while ($col = $columns_result->fetch_object()) {
    $columns[$col->Field] = $col;
}
echo "   Current columns: " . implode(', ', array_keys($columns)) . "\n\n";

// Check if user_id column exists
if (isset($columns['user_id'])) {
    echo "2. user_id column: EXISTS - No fix needed!\n";
} else {
    echo "2. user_id column: MISSING - Needs to be added\n\n";

    if (!isset($_GET['fix'])) {
        echo "   To apply the fix, add &fix=1 to the URL:\n";
        echo "   fix_db.php?key=widgethook_reset_2024&fix=1\n";
    } else {
        echo "   Adding user_id column...\n";

        // Add user_id column
        $alter_query = "ALTER TABLE `track_notifications` ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `id`";
        $result = database()->query($alter_query);

        if ($result) {
            echo "   SUCCESS: user_id column added!\n\n";

            // Add index for better performance
            echo "   Adding index on user_id...\n";
            $index_query = "ALTER TABLE `track_notifications` ADD INDEX `track_notifications_user_id_index` (`user_id`)";
            $index_result = database()->query($index_query);

            if ($index_result) {
                echo "   SUCCESS: Index added!\n\n";
            } else {
                echo "   WARNING: Could not add index: " . database()->error . "\n\n";
            }

            // Try to populate user_id from notifications table
            echo "   Attempting to populate user_id from existing data...\n";
            $update_query = "
                UPDATE `track_notifications` tn
                INNER JOIN `notifications` n ON tn.notification_id = n.notification_id
                SET tn.user_id = n.user_id
                WHERE tn.user_id IS NULL
            ";
            $update_result = database()->query($update_query);

            if ($update_result) {
                $affected = database()->affected_rows;
                echo "   Updated {$affected} rows with user_id from notifications table\n";
            } else {
                echo "   Could not populate user_id: " . database()->error . "\n";
                echo "   (This is OK if there's no data yet)\n";
            }
        } else {
            echo "   ERROR: " . database()->error . "\n";
        }
    }
}

// Verify final state
echo "\n3. Final verification:\n";
$verify = database()->query("SHOW COLUMNS FROM `track_notifications`");
$final_cols = [];
while ($col = $verify->fetch_object()) {
    $final_cols[] = $col->Field;
}
echo "   Columns: " . implode(', ', $final_cols) . "\n";

// Test query
echo "\n4. Test query:\n";
$test = database()->query("SELECT COUNT(*) as cnt FROM `track_notifications` WHERE `user_id` IS NOT NULL OR `user_id` IS NULL");
if ($test) {
    $row = $test->fetch_object();
    echo "   Query works! Total rows: {$row->cnt}\n";
} else {
    echo "   Query failed: " . database()->error . "\n";
}

echo "\n=== Done ===\n";
echo "\nNow test the dashboard at /dashboard\n";
