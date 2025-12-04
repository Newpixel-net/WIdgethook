<?php
/*
 * Deep Debug - Catches the actual PHP error
 * DELETE AFTER DEBUGGING
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Deep Debug Dashboard AJAX</h1><pre>";

// Use the proper bootstrap like index.php does
const DEBUG = 1;
const MYSQL_DEBUG = 0;
const LOGGING = 0;
const CACHE = 1;
const ALTUMCODE = 66;

try {
    require_once realpath(__DIR__) . '/app/init.php';

    echo "App initialized successfully!\n\n";

    echo "=== Settings Check ===\n";
    echo "main->chart_days: " . (settings()->main->chart_days ?? 'NOT SET') . "\n";
    echo "main->chart_cache: " . (settings()->main->chart_cache ?? 'NOT SET') . "\n";

    echo "\n=== Session Check ===\n";
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        echo "User ID: $user_id\n";

        $user = db()->where('user_id', $user_id)->getOne('users');
        if ($user) {
            echo "User email: " . $user->email . "\n";
            echo "User timezone: " . ($user->timezone ?? 'NOT SET') . "\n";

            // Fix missing timezone
            if (empty($user->timezone)) {
                echo "\nFixing missing timezone...\n";
                db()->where('user_id', $user_id)->update('users', ['timezone' => 'UTC']);
                echo "Set to UTC\n";
            }

            echo "\n=== Testing Dashboard Queries ===\n";

            // Test convert_tz_sql
            $timezone = $user->timezone ?: 'UTC';
            echo "Testing get_convert_tz_sql with timezone: $timezone\n";
            $convert_tz_sql = get_convert_tz_sql('`datetime`', $timezone);
            echo "Result: $convert_tz_sql\n";

            // Test the statistics query
            $start_date = (new \DateTime())->modify('-30 day')->format('Y-m-d');
            $end_date = (new \DateTime())->modify('+1 day')->format('Y-m-d');

            $query = "SELECT COUNT(`id`) AS `impressions` FROM `track_notifications` WHERE `user_id` = {$user_id} LIMIT 1";
            echo "\nTest query: $query\n";
            $result = database()->query($query);
            if ($result) {
                echo "Query OK!\n";
            } else {
                echo "Query ERROR: " . database()->error . "\n";
            }

            // Test get_chart_data function
            echo "\n=== Testing get_chart_data ===\n";
            $test_data = [];
            $chart = get_chart_data($test_data);
            echo "get_chart_data OK!\n";

            // Test Cache class
            echo "\n=== Testing Cache ===\n";
            $cache_result = \Altum\Cache::cache_function_result('test_key', 'test_tag', function() {
                return 'test_value';
            }, 60);
            echo "Cache test result: $cache_result\n";

        } else {
            echo "User not found in database!\n";
        }
    } else {
        echo "Not logged in! Please log in first.\n";
        echo "Available session keys: " . implode(', ', array_keys($_SESSION ?? [])) . "\n";
    }

} catch (Throwable $e) {
    echo "\n=== ERROR CAUGHT ===\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DONE ===</pre>";
