<?php
/*
 * Force Fix Payment Settings
 * DELETE THIS FILE AFTER RUNNING
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Force Fix Settings</h1><pre>";

define('ALTUMCODE', true);
require_once 'config.php';

$mysqli = new mysqli(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get current and decode
$result = $mysqli->query("SELECT `value` FROM `settings` WHERE `key` = 'payment'");
$row = $result->fetch_assoc();
$current = json_decode($row['value'], true) ?: [];

echo "Before fix:\n";
echo "is_enabled = " . var_export($current['is_enabled'] ?? 'NOT SET', true) . "\n\n";

// Force set the critical values
$current['is_enabled'] = true;  // Force to boolean true
$current['default_currency'] = 'USD';
$current['type'] = 'both';
$current['currencies'] = ['USD'];

// Encode with proper JSON
$json = json_encode($current, JSON_UNESCAPED_SLASHES);

echo "New JSON:\n$json\n\n";

// Direct SQL update
$escaped = $mysqli->real_escape_string($json);
$sql = "UPDATE `settings` SET `value` = '$escaped' WHERE `key` = 'payment'";

if ($mysqli->query($sql)) {
    echo "SUCCESS: Updated!\n\n";
} else {
    echo "ERROR: " . $mysqli->error . "\n\n";
}

// Verify
$result = $mysqli->query("SELECT `value` FROM `settings` WHERE `key` = 'payment'");
$row = $result->fetch_assoc();
$verified = json_decode($row['value'], true);

echo "After fix:\n";
echo "is_enabled = " . var_export($verified['is_enabled'], true) . "\n";
echo "default_currency = " . $verified['default_currency'] . "\n";

$mysqli->close();
echo "\n=== DONE - Refresh your dashboard now ===</pre>";
