<?php
/*
 * Deep Debug - Catches the actual PHP error
 * DELETE AFTER DEBUGGING
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Deep Debug Dashboard AJAX</h1><pre>";

// Manually simulate what the Dashboard::get_stats_ajax does
define('ALTUMCODE', true);
define('ROOT', __DIR__);
define('APP_PATH', ROOT . '/app/');
define('THEME_PATH', ROOT . '/themes/altum/');
define('ASSETS_PATH', ROOT . '/themes/altum/assets/');
define('UPLOADS_PATH', ROOT . '/uploads/');

require_once 'config.php';

// Load the app
try {
    require_once APP_PATH . 'includes/product.php';
    require_once APP_PATH . 'core/Traits.php';
    require_once ROOT . '/vendor/autoload.php';

    // Initialize database
    \Altum\Database::initialize();

    echo "Database initialized.\n";

    // Get settings
    $settings = \Altum\Settings::initialize();
    echo "Settings loaded.\n";
    echo "main->chart_days: " . (settings()->main->chart_days ?? 'NOT SET') . "\n";
    echo "main->chart_cache: " . (settings()->main->chart_cache ?? 'NOT SET') . "\n";

    // Check if user is logged in
    echo "\nChecking session...\n";
    session_start();

    if (isset($_SESSION['user_id'])) {
        echo "User ID in session: " . $_SESSION['user_id'] . "\n";

        // Get user
        $user_id = $_SESSION['user_id'];
        $user = db()->where('user_id', $user_id)->getOne('users');

        if ($user) {
            echo "User found: " . $user->email . "\n";
            echo "User timezone: " . ($user->timezone ?? 'NOT SET') . "\n";

            // Fix timezone if missing
            if (empty($user->timezone)) {
                echo "Fixing missing timezone...\n";
                db()->where('user_id', $user_id)->update('users', ['timezone' => 'UTC']);
                $user->timezone = 'UTC';
                echo "Set timezone to UTC\n";
            }

            // Test the query that's probably failing
            echo "\nTesting get_convert_tz_sql...\n";
            $convert_tz_sql = get_convert_tz_sql('`datetime`', $user->timezone);
            echo "SQL: $convert_tz_sql\n";

            echo "\nTesting statistics query...\n";
            $start_date_query = (new \DateTime())->modify('-30 day')->format('Y-m-d');
            $end_date_query = (new \DateTime())->modify('+1 day')->format('Y-m-d');

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

            echo "Query:\n$query\n\n";

            $result = database()->query($query);
            if ($result === false) {
                echo "Query ERROR: " . database()->error . "\n";
            } else {
                echo "Query succeeded! Rows: " . $result->num_rows . "\n";
            }

            echo "\nTesting get_chart_data...\n";
            $test_data = [];
            $chart_result = get_chart_data($test_data);
            echo "get_chart_data succeeded!\n";
            print_r($chart_result);

        } else {
            echo "User NOT found in database!\n";
        }
    } else {
        echo "No user_id in session. Please log in first!\n";
    }

} catch (Throwable $e) {
    echo "\n=== CAUGHT ERROR ===\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DONE ===</pre>";
