<?php
/*
 * AJAX Debug Script - Tests what the admin dashboard AJAX endpoint returns
 * Access via: https://landingo.net/ajax_debug.php
 * DELETE THIS FILE AFTER DEBUGGING
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AJAX Endpoint Debug</h1>";
echo "<pre>";

// Load the application
define('ALTUMCODE', true);
define('ROOT', __DIR__);

// Check config
if(!file_exists('config.php')) {
    die("ERROR: config.php not found!");
}

require_once 'config.php';

echo "=== Config Check ===\n";
echo "DATABASE_SERVER: " . (defined('DATABASE_SERVER') && DATABASE_SERVER ? 'SET' : 'EMPTY') . "\n";
echo "DATABASE_NAME: " . (defined('DATABASE_NAME') && DATABASE_NAME ? DATABASE_NAME : 'EMPTY') . "\n";
echo "DATABASE_USERNAME: " . (defined('DATABASE_USERNAME') && DATABASE_USERNAME ? 'SET' : 'EMPTY') . "\n";
echo "DATABASE_PASSWORD: " . (defined('DATABASE_PASSWORD') && DATABASE_PASSWORD ? 'SET' : 'EMPTY') . "\n\n";

// Try database connection
echo "=== Database Connection ===\n";
try {
    $mysqli = new mysqli(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    echo "Connected successfully!\n\n";
} catch (Exception $e) {
    die("Connection error: " . $e->getMessage());
}

// Test each query from get_stats_ajax
echo "=== Testing AdminIndex AJAX Queries ===\n\n";

$queries = [
    'campaigns' => "SELECT count(`campaign_id`) FROM `campaigns`",
    'notifications' => "SELECT count(`notification_id`) FROM `notifications`",
    'track_notifications' => "SELECT MAX(`id`) FROM `track_notifications`",
    'track_logs' => "SELECT MAX(`id`) FROM `track_logs`",
    'track_conversions' => "SELECT MAX(`id`) FROM `track_conversions`",
    'users' => "SELECT count(`user_id`) FROM `users`",
    'payments' => "SELECT count(`id`) FROM `payments`",
    'payments_total' => "SELECT sum(`total_amount_default_currency`) FROM `payments`",
    'campaigns_month' => "SELECT count(*) FROM `campaigns` WHERE `datetime` >= '" . date('Y-m-01') . "'",
    'notifications_month' => "SELECT count(*) FROM `notifications` WHERE `datetime` >= '" . date('Y-m-01') . "'",
    'track_notifications_month' => "SELECT count(*) FROM `track_notifications` WHERE `datetime` >= '" . date('Y-m-01') . "'",
    'track_logs_month' => "SELECT count(*) FROM `track_logs` WHERE `datetime` >= '" . date('Y-m-01') . "'",
    'track_conversions_month' => "SELECT count(*) FROM `track_conversions` WHERE `datetime` >= '" . date('Y-m-01') . "'",
    'users_month' => "SELECT count(*) FROM `users` WHERE `datetime` >= '" . date('Y-m-01') . "'",
    'payments_month' => "SELECT count(*) FROM `payments` WHERE `datetime` >= '" . date('Y-m-01') . "'",
    'payments_amount_month' => "SELECT sum(`total_amount_default_currency`) FROM `payments` WHERE `datetime` >= '" . date('Y-m-01') . "'",
];

foreach ($queries as $name => $query) {
    echo "[$name]\n";
    echo "Query: $query\n";
    try {
        $result = $mysqli->query($query);
        if ($result === false) {
            echo "ERROR: " . $mysqli->error . "\n";
        } else {
            $row = $result->fetch_row();
            echo "Result: " . ($row[0] ?? 'NULL') . "\n";
        }
    } catch (Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// Check required tables
echo "=== Table Existence Check ===\n";
$tables = ['campaigns', 'notifications', 'track_notifications', 'track_logs', 'track_conversions', 'users', 'payments', 'plans', 'settings'];
foreach ($tables as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    $exists = $result->num_rows > 0;
    echo "$table: " . ($exists ? "EXISTS" : "MISSING") . "\n";
}

echo "\n=== Settings Check ===\n";
$result = $mysqli->query("SELECT `key`, `value` FROM `settings` WHERE `key` IN ('license', 'payment', 'internal_notifications')");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $decoded = json_decode($row['value']);
        if ($row['key'] === 'license') {
            echo "license->type: " . ($decoded->type ?? 'NOT SET') . "\n";
        } elseif ($row['key'] === 'payment') {
            echo "payment->is_enabled: " . ($decoded->is_enabled ?? 'NOT SET') . "\n";
            echo "payment->default_currency: " . ($decoded->default_currency ?? 'NOT SET') . "\n";
        } elseif ($row['key'] === 'internal_notifications') {
            echo "internal_notifications->admins_is_enabled: " . (isset($decoded->admins_is_enabled) ? ($decoded->admins_is_enabled ? 'true' : 'false') : 'NOT SET') . "\n";
        }
    }
} else {
    echo "ERROR querying settings: " . $mysqli->error . "\n";
}

$mysqli->close();
echo "\n=== Debug Complete ===\n";
echo "</pre>";
