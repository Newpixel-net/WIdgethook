<?php
/*
 * Debug User Dashboard AJAX
 * DELETE THIS FILE AFTER DEBUGGING
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug User Dashboard</h1><pre>";

define('ALTUMCODE', true);
require_once 'config.php';

$mysqli = new mysqli(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "=== Checking 'main' settings ===\n";
$result = $mysqli->query("SELECT `value` FROM `settings` WHERE `key` = 'main'");
$row = $result->fetch_assoc();

if (!$row) {
    echo "ERROR: 'main' settings NOT FOUND!\n";
} else {
    $main = json_decode($row['value']);
    echo "main->chart_days: " . (isset($main->chart_days) ? $main->chart_days : 'NOT SET') . "\n";
    echo "main->chart_cache: " . (isset($main->chart_cache) ? $main->chart_cache : 'NOT SET') . "\n";
    echo "main->default_timezone: " . (isset($main->default_timezone) ? $main->default_timezone : 'NOT SET') . "\n";
}

echo "\n=== Fixing missing main settings ===\n";
if ($row) {
    $main = json_decode($row['value'], true) ?: [];

    // Add missing defaults
    if (!isset($main['chart_days'])) {
        $main['chart_days'] = 30;
        echo "Added chart_days = 30\n";
    }
    if (!isset($main['chart_cache'])) {
        $main['chart_cache'] = 12;
        echo "Added chart_cache = 12\n";
    }
    if (!isset($main['default_timezone'])) {
        $main['default_timezone'] = 'UTC';
        echo "Added default_timezone = UTC\n";
    }

    $json = json_encode($main);
    $escaped = $mysqli->real_escape_string($json);
    $mysqli->query("UPDATE `settings` SET `value` = '$escaped' WHERE `key` = 'main'");
    echo "Settings updated!\n";
}

echo "\n=== Testing dashboard AJAX endpoint ===\n";
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$ajax_url = $base_url . "/dashboard/get_stats_ajax";

$cookies = [];
foreach ($_COOKIE as $name => $value) {
    $cookies[] = "$name=$value";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ajax_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $cookies));
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "URL: $ajax_url\n";
echo "HTTP Code: $http_code\n";
echo "Response (first 1000 chars):\n";
echo htmlspecialchars(substr($response, 0, 1000));

$mysqli->close();
echo "\n\n=== DONE ===</pre>";
