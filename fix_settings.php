<?php
/*
 * Fix Settings Script
 * Repairs missing payment and other settings
 * Access via: https://landingo.net/fix_settings.php
 * DELETE THIS FILE AFTER RUNNING
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fix Settings</h1>";
echo "<pre>";

define('ALTUMCODE', true);
require_once 'config.php';

$mysqli = new mysqli(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "Connected to database.\n\n";

// Get current payment settings
$result = $mysqli->query("SELECT `value` FROM `settings` WHERE `key` = 'payment'");
$row = $result->fetch_assoc();
$current = $row ? json_decode($row['value'], true) : [];

echo "Current payment settings:\n";
print_r($current);
echo "\n";

// Define proper defaults
$defaults = [
    'is_enabled' => true,
    'type' => 'both',
    'default_currency' => 'USD',
    'currencies' => ['USD'],
    'taxes_and_billing_is_enabled' => false,
    'invoice_is_enabled' => false,
    'codes_is_enabled' => true,
    'paypal' => ['is_enabled' => false],
    'stripe' => ['is_enabled' => false],
    'offline_payment' => ['is_enabled' => false],
    'currency_exchange_api_key' => ''
];

// Merge with existing (keeping existing values where they exist)
$fixed = array_merge($defaults, $current ?: []);

// Ensure critical values are set
if (empty($fixed['default_currency'])) {
    $fixed['default_currency'] = 'USD';
}
if (!isset($fixed['is_enabled'])) {
    $fixed['is_enabled'] = true;
}
if (empty($fixed['currencies'])) {
    $fixed['currencies'] = ['USD'];
}

$json = json_encode($fixed);
echo "Fixed payment settings:\n";
print_r($fixed);
echo "\n";

// Update the database
$stmt = $mysqli->prepare("UPDATE `settings` SET `value` = ? WHERE `key` = 'payment'");
$stmt->bind_param('s', $json);

if ($stmt->execute()) {
    echo "SUCCESS: Payment settings updated!\n";
} else {
    echo "ERROR: " . $stmt->error . "\n";
}

// Also check/fix internal_notifications settings
$result = $mysqli->query("SELECT `value` FROM `settings` WHERE `key` = 'internal_notifications'");
$row = $result->fetch_assoc();
if (!$row) {
    echo "\nAdding missing internal_notifications setting...\n";
    $internal = json_encode([
        'admins_is_enabled' => true,
        'users_is_enabled' => true,
        'new_user' => true,
        'new_payment' => true
    ]);
    $mysqli->query("INSERT INTO `settings` (`key`, `value`) VALUES ('internal_notifications', '$internal')");
    echo "Added internal_notifications setting.\n";
}

// Verify
echo "\n=== Verification ===\n";
$result = $mysqli->query("SELECT `value` FROM `settings` WHERE `key` = 'payment'");
$row = $result->fetch_assoc();
$verified = json_decode($row['value']);
echo "payment->is_enabled: " . ($verified->is_enabled ? 'true' : 'false') . "\n";
echo "payment->default_currency: " . $verified->default_currency . "\n";

$mysqli->close();

echo "\n=== DONE ===\n";
echo "Now go back to your admin dashboard and refresh!\n";
echo "Remember to DELETE this file when done.\n";
echo "</pre>";
