<?php
/*
 * Fix User Settings
 * Sets missing timezone for users
 * DELETE AFTER USE
 */

echo "<pre>Fix User Settings\n=================\n\n";

define('ALTUMCODE', true);
require_once 'config.php';

$mysqli = new mysqli(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Find users with empty timezone
$result = $mysqli->query("SELECT user_id, email, timezone FROM users WHERE timezone IS NULL OR timezone = ''");
echo "Users with missing timezone:\n";

$count = 0;
while ($row = $result->fetch_assoc()) {
    echo "- User {$row['user_id']} ({$row['email']}): timezone = '{$row['timezone']}'\n";
    $count++;
}

if ($count == 0) {
    echo "None found.\n";
} else {
    echo "\nFixing $count users...\n";
    $mysqli->query("UPDATE users SET timezone = 'UTC' WHERE timezone IS NULL OR timezone = ''");
    echo "Set timezone to 'UTC' for all affected users.\n";
}

// Also check the plan_settings column
echo "\n\nChecking plan_settings for users...\n";
$result = $mysqli->query("SELECT user_id, email, plan_settings FROM users WHERE plan_settings IS NULL OR plan_settings = '' OR plan_settings = '{}'");
$count = 0;
while ($row = $result->fetch_assoc()) {
    echo "- User {$row['user_id']} ({$row['email']}): plan_settings is empty\n";
    $count++;
}

if ($count > 0) {
    echo "\nFixing plan_settings...\n";
    // Get default plan settings from the free plan
    $plan = $mysqli->query("SELECT settings FROM plans WHERE plan_id = 'free'")->fetch_assoc();
    if ($plan && $plan['settings']) {
        $settings = $mysqli->real_escape_string($plan['settings']);
        $mysqli->query("UPDATE users SET plan_settings = '$settings' WHERE plan_settings IS NULL OR plan_settings = '' OR plan_settings = '{}'");
        echo "Applied default plan settings.\n";
    }
}

echo "\n\n=== Now testing dashboard AJAX ===\n";

// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared.\n";
}

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

// Test user dashboard
$cookies = [];
foreach ($_COOKIE as $name => $value) {
    $cookies[] = "$name=$value";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . "/dashboard/get_stats_ajax");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $cookies));
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nUser Dashboard AJAX:\n";
echo "HTTP Code: $code\n";
echo "Response: " . substr($response, 0, 500) . "\n";

// Test admin payments
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . "/admin/payments");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $cookies));
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nAdmin Payments Page:\n";
echo "HTTP Code: $code\n";
echo "Response length: " . strlen($response) . " bytes\n";

if ($code == 200 && strlen($response) > 1000) {
    echo "Looks OK!\n";
} else {
    echo "First 500 chars: " . substr($response, 0, 500) . "\n";
}

$mysqli->close();
echo "\n=== DONE ===</pre>";
