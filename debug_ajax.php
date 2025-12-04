<?php
/**
 * Debug the Dashboard AJAX endpoint
 * Access: /debug_ajax.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');
echo "=== Dashboard AJAX Debug ===\n\n";

// Bootstrap the app
const DEBUG = 1;
const MYSQL_DEBUG = 0;
const LOGGING = 0;
const CACHE = 1;
const ALTUMCODE = 66;

try {
    require_once __DIR__ . '/app/init.php';
    echo "1. App initialized: OK\n";
} catch (Throwable $e) {
    die("1. App init FAILED: " . $e->getMessage() . "\n");
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "2. Session status: " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Inactive") . "\n";
echo "   Session ID: " . session_id() . "\n";

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo "3. User: NOT LOGGED IN\n";
    echo "\n   You need to be logged in to test the dashboard.\n";
    echo "   Please log in first, then visit this page again.\n";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "3. User ID from session: {$user_id}\n";

// Get user from database
try {
    $user = db()->where('user_id', $user_id)->getOne('users');
    if (!$user) {
        die("4. User lookup FAILED: User not found in database\n");
    }
    echo "4. User from DB: {$user->email}\n";
    echo "   Timezone: " . ($user->timezone ?: 'NOT SET') . "\n";
} catch (Throwable $e) {
    die("4. User lookup FAILED: " . $e->getMessage() . "\n");
}

// Check settings
echo "\n5. Settings check:\n";
try {
    $chart_days = settings()->main->chart_days ?? null;
    $chart_cache = settings()->main->chart_cache ?? null;
    echo "   chart_days: " . ($chart_days ?? 'NULL') . "\n";
    echo "   chart_cache: " . ($chart_cache ?? 'NULL') . "\n";

    if ($chart_days === null) {
        echo "   WARNING: chart_days is NULL, will cause issues!\n";
    }
} catch (Throwable $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Test the actual queries from get_stats_ajax
echo "\n6. Testing get_stats_ajax queries:\n";

try {
    $timezone = $user->timezone ?: 'UTC';
    echo "   Using timezone: {$timezone}\n";

    // Test get_convert_tz_sql
    $convert_tz_sql = get_convert_tz_sql('`datetime`', $timezone);
    echo "   convert_tz_sql: {$convert_tz_sql}\n";

    $chart_days = settings()->main->chart_days ?? 30;
    $start_date_query = (new \DateTime())->modify('-' . $chart_days . ' day')->format('Y-m-d');
    $end_date_query = (new \DateTime())->modify('+1 day')->format('Y-m-d');
    echo "   Date range: {$start_date_query} to {$end_date_query}\n";

    // Test the statistics query
    $statistics_result_query = "
        SELECT
            COUNT(`id`) AS `impressions`,
            DATE_FORMAT({$convert_tz_sql}, '%Y-%m-%d') AS `formatted_date`
        FROM
            `track_notifications`
        WHERE
            `user_id` = {$user_id}
            AND `type` = 'impression'
            AND ({$convert_tz_sql} BETWEEN '{$start_date_query}' AND '{$end_date_query}')
        GROUP BY
            `formatted_date`
        ORDER BY
            `formatted_date`
    ";

    echo "\n   Running statistics query...\n";
    $result = database()->query($statistics_result_query);
    if ($result) {
        $count = $result->num_rows;
        echo "   Query OK! Rows returned: {$count}\n";
    } else {
        echo "   Query ERROR: " . database()->error . "\n";
    }

} catch (Throwable $e) {
    echo "   EXCEPTION: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
}

// Test cache function
echo "\n7. Testing Cache::cache_function_result:\n";
try {
    $test_result = \Altum\Cache::cache_function_result(
        'debug_test_' . time(),
        'debug',
        function() {
            return ['test' => 'value'];
        },
        60
    );
    echo "   Cache function: OK\n";
    echo "   Result: " . json_encode($test_result) . "\n";
} catch (Throwable $e) {
    echo "   Cache EXCEPTION: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test get_chart_data function
echo "\n8. Testing get_chart_data:\n";
try {
    $test_data = [];
    $chart = get_chart_data($test_data);
    echo "   get_chart_data: OK\n";
} catch (Throwable $e) {
    echo "   get_chart_data EXCEPTION: " . $e->getMessage() . "\n";
}

// Test Response class
echo "\n9. Testing Response::json:\n";
try {
    // Don't actually output JSON, just test if the class works
    $response_class_exists = class_exists('\Altum\Response');
    echo "   Response class exists: " . ($response_class_exists ? 'YES' : 'NO') . "\n";

    if ($response_class_exists) {
        $methods = get_class_methods('\Altum\Response');
        echo "   Response methods: " . implode(', ', $methods) . "\n";
    }
} catch (Throwable $e) {
    echo "   Response EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
echo "\nIf all tests passed, try making the actual AJAX call.\n";
echo "Check browser console for errors when loading /dashboard\n";
