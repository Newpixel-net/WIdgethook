<?php
/**
 * Debug Query - Test the exact database query
 * Must buffer output until after init.php to preserve sessions
 */

// Buffer ALL output - nothing before this
ob_start();

const DEBUG = 1;
const MYSQL_DEBUG = 0;
const LOGGING = 0;
const CACHE = 1;
const ALTUMCODE = 66;

require_once __DIR__ . '/app/init.php';

// Start session using the app's session helper (this handles all the logic)
session_start_if_not_started();

// Now we can output
ob_end_clean();

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== Query Debug ===\n\n";

// Use session_get helper which properly handles sessions
$user_id = session_get('user_id');
if (!$user_id) {
    die("Not logged in. Log in first at /login, then visit this page.\n");
}
echo "User ID: {$user_id}\n\n";

// Get user
$user = db()->where('user_id', $user_id)->getOne('users');
echo "User email: {$user->email}\n";
echo "User timezone: " . ($user->timezone ?: 'NULL') . "\n\n";

// Check Date class
echo "=== Date Class Check ===\n";
echo "Date::\$default_timezone: " . (\Altum\Date::$default_timezone ?? 'NULL') . "\n";
echo "Date::\$timezone: " . (\Altum\Date::$timezone ?? 'NULL') . "\n\n";

// Test timezone
$timezone = $user->timezone ?: 'UTC';
echo "Using timezone: {$timezone}\n";

// Test get_timezone_difference
echo "\n=== Timezone Difference Test ===\n";
try {
    $tz_diff = \Altum\Date::get_timezone_difference(\Altum\Date::$default_timezone ?? 'UTC', $timezone);
    echo "Timezone difference: {$tz_diff}\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Test convert_tz_sql
echo "\n=== Convert TZ SQL Test ===\n";
try {
    $convert_tz_sql = get_convert_tz_sql('`datetime`', $timezone);
    echo "Convert TZ SQL: {$convert_tz_sql}\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Build the query
$chart_days = settings()->main->chart_days ?? 30;
$start_date_query = (new \DateTime())->modify('-' . $chart_days . ' day')->format('Y-m-d');
$end_date_query = (new \DateTime())->modify('+1 day')->format('Y-m-d');

echo "\n=== Query Parameters ===\n";
echo "chart_days: {$chart_days}\n";
echo "start_date: {$start_date_query}\n";
echo "end_date: {$end_date_query}\n";

// Build the full query
$query = "
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

echo "\n=== Full Query ===\n";
echo trim($query) . "\n";

// Check if track_notifications table exists
echo "\n=== Table Check ===\n";
$table_check = database()->query("SHOW TABLES LIKE 'track_notifications'");
echo "track_notifications table exists: " . ($table_check->num_rows > 0 ? "YES" : "NO") . "\n";

// Check table structure
if ($table_check->num_rows > 0) {
    $columns = database()->query("SHOW COLUMNS FROM `track_notifications`");
    echo "Columns: ";
    $cols = [];
    while ($col = $columns->fetch_object()) {
        $cols[] = $col->Field;
    }
    echo implode(', ', $cols) . "\n";

    // Count records
    $count = database()->query("SELECT COUNT(*) as cnt FROM `track_notifications` WHERE `user_id` = {$user_id}");
    $row = $count->fetch_object();
    echo "Records for this user: {$row->cnt}\n";
}

// Test MySQL timezone support
echo "\n=== MySQL Timezone Test ===\n";
$tz_test = database()->query("SELECT NOW() as now_time");
if ($tz_test) {
    $row = $tz_test->fetch_object();
    echo "MySQL NOW(): {$row->now_time}\n";
}

// Test CONVERT_TZ
$tz_convert_test = database()->query("SELECT CONVERT_TZ(NOW(), '+00:00', '+02:00') as converted");
if ($tz_convert_test) {
    $row = $tz_convert_test->fetch_object();
    echo "CONVERT_TZ test: " . ($row->converted ?? 'NULL - TZ tables not loaded!') . "\n";
}

// Run the actual query
echo "\n=== Running Query ===\n";
$result = database()->query($query);

if ($result === false) {
    echo "QUERY FAILED!\n";
    echo "MySQL Error: " . database()->error . "\n";
    echo "MySQL Errno: " . database()->errno . "\n";
} else {
    echo "Query succeeded!\n";
    echo "Rows returned: " . $result->num_rows . "\n";

    if ($result->num_rows > 0) {
        echo "\nResults:\n";
        while ($row = $result->fetch_object()) {
            echo "  {$row->formatted_date}: {$row->impressions} impressions\n";
        }
    }
}

echo "\n=== Done ===\n";
