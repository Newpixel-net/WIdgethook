<?php
/**
 * Debug AJAX - Simulates the exact AJAX call flow
 * This must be run while logged in (same browser session)
 */

// Don't output ANYTHING before this - we need to preserve session
ob_start();

const DEBUG = 1;
const MYSQL_DEBUG = 0;
const LOGGING = 1;  // Enable logging to capture errors
const CACHE = 1;
const ALTUMCODE = 66;

// Set up error handling to capture everything
$errors = [];
set_error_handler(function($severity, $message, $file, $line) use (&$errors) {
    $errors[] = [
        'type' => 'error',
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line
    ];
    return true; // Don't execute PHP's internal error handler
});

set_exception_handler(function($e) use (&$errors) {
    $errors[] = [
        'type' => 'exception',
        'class' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
});

register_shutdown_function(function() use (&$errors) {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: text/plain');
        echo "=== FATAL ERROR CAPTURED ===\n\n";
        echo "Type: " . $error['type'] . "\n";
        echo "Message: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";

        if (!empty($GLOBALS['errors'])) {
            echo "\n=== Previous Errors ===\n";
            print_r($GLOBALS['errors']);
        }
    }
});

$GLOBALS['errors'] = &$errors;

try {
    require_once __DIR__ . '/app/init.php';

    // Clear any output from init
    ob_end_clean();
    ob_start();

    header('Content-Type: text/plain');
    echo "=== AJAX Simulation Debug ===\n\n";

    // Check session
    echo "1. Session Check:\n";
    echo "   Status: " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Inactive") . "\n";

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    echo "   Session ID: " . (session_id() ?: 'NONE') . "\n";
    echo "   user_id in session: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";

    if (!isset($_SESSION['user_id'])) {
        echo "\n   ERROR: Not logged in. Please log in first.\n";
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Check Authentication class
    echo "\n2. Authentication Check:\n";
    if (class_exists('\Altum\Authentication')) {
        echo "   Authentication class: EXISTS\n";

        // Try to get user via Authentication
        $auth_user = \Altum\Authentication::$user ?? null;
        $auth_user_id = \Altum\Authentication::$user_id ?? null;
        echo "   Auth user_id: " . ($auth_user_id ?? 'NULL') . "\n";
        echo "   Auth user object: " . ($auth_user ? 'SET' : 'NULL') . "\n";
    } else {
        echo "   Authentication class: MISSING\n";
    }

    // Get user from DB directly
    echo "\n3. Database User Check:\n";
    $user = db()->where('user_id', $user_id)->getOne('users');
    if ($user) {
        echo "   User found: {$user->email}\n";
        echo "   Timezone: " . ($user->timezone ?: 'NULL - THIS IS A PROBLEM') . "\n";
        echo "   Plan ID: {$user->plan_id}\n";
    } else {
        echo "   ERROR: User not found in database!\n";
        exit;
    }

    // Now simulate the actual get_stats_ajax code
    echo "\n4. Simulating get_stats_ajax:\n";

    // This is the actual code from Dashboard.php get_stats_ajax()
    $chart_days = settings()->main->chart_days ?? 30;
    echo "   chart_days: {$chart_days}\n";

    $start_date_query = (new \DateTime())->modify('-' . $chart_days . ' day')->format('Y-m-d');
    $end_date_query = (new \DateTime())->modify('+1 day')->format('Y-m-d');
    echo "   Date range: {$start_date_query} to {$end_date_query}\n";

    $timezone = $user->timezone ?: 'UTC';
    $convert_tz_sql = get_convert_tz_sql('`datetime`', $timezone);
    echo "   Timezone SQL: {$convert_tz_sql}\n";

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

    echo "\n5. Cache test:\n";
    $cache_key = 'statistics?user_id=' . $user_id;
    $cache_tag = 'user_id=' . $user_id;
    echo "   Cache key: {$cache_key}\n";
    echo "   Cache tag: {$cache_tag}\n";

    $chart_cache = settings()->main->chart_cache ?? 12;
    echo "   chart_cache hours: {$chart_cache}\n";

    // Try the cache function
    $notifications_chart = \Altum\Cache::cache_function_result($cache_key, $cache_tag, function() use ($statistics_result_query) {
        $notifications_chart = [];
        $statistics_result = database()->query($statistics_result_query);

        while($row = $statistics_result->fetch_object()) {
            $label = \Altum\Date::get($row->formatted_date, 5, \Altum\Date::$default_timezone);
            $notifications_chart[$label] = [
                'impressions' => $row->impressions,
            ];
        }

        return $notifications_chart;
    }, 60 * 60 * $chart_cache);

    echo "   Cache function result: OK\n";
    echo "   Data points: " . count($notifications_chart) . "\n";

    // Test get_chart_data
    echo "\n6. get_chart_data test:\n";
    $notifications_chart = get_chart_data($notifications_chart);
    echo "   Result: OK\n";

    // Test other widget queries
    echo "\n7. Widget queries:\n";
    $total_campaigns = db()->where('user_id', $user_id)->getValue('campaigns', 'count(*)');
    echo "   Total campaigns: {$total_campaigns}\n";

    $total_notifications = db()->where('user_id', $user_id)->getValue('notifications', 'count(*)');
    echo "   Total notifications: {$total_notifications}\n";

    $current_month = db()->where('user_id', $user_id)->getValue('users', 'current_month_notifications_impressions');
    echo "   Current month impressions: {$current_month}\n";

    echo "\n=== ALL TESTS PASSED ===\n";
    echo "\nIf you see this, the AJAX code should work.\n";
    echo "The issue might be in the routing or authentication guard.\n";

    if (!empty($errors)) {
        echo "\n=== Warnings/Errors captured ===\n";
        foreach ($errors as $err) {
            echo "- [{$err['type']}] {$err['message']} in {$err['file']}:{$err['line']}\n";
        }
    }

} catch (Throwable $e) {
    ob_end_clean();
    header('Content-Type: text/plain');
    echo "=== EXCEPTION CAUGHT ===\n\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";

    if (!empty($errors)) {
        echo "\n=== Previous Errors ===\n";
        print_r($errors);
    }
}
