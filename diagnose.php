<?php
/**
 * Diagnostic script - Access via: yoursite.com/diagnose.php
 * DELETE THIS FILE AFTER DEBUGGING
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ALTUMCODE', 1);
define('ROOT_PATH', __DIR__ . '/');

echo "<h1>System Diagnostics</h1>";
echo "<style>body{font-family:monospace;} .ok{color:green;} .error{color:red;} .warn{color:orange;}</style>";

// Test 1: Config file
echo "<h2>1. Configuration</h2>";
if (file_exists('config.php')) {
    echo "<p class='ok'>✓ config.php exists</p>";
    require_once 'config.php';

    echo "<p>DATABASE_SERVER: " . (defined('DATABASE_SERVER') ? (DATABASE_SERVER ?: '<empty>') : '<not defined>') . "</p>";
    echo "<p>DATABASE_NAME: " . (defined('DATABASE_NAME') ? (DATABASE_NAME ?: '<empty>') : '<not defined>') . "</p>";
    echo "<p>DATABASE_USERNAME: " . (defined('DATABASE_USERNAME') ? (DATABASE_USERNAME ? '***set***' : '<empty>') : '<not defined>') . "</p>";
    echo "<p>SITE_URL: " . (defined('SITE_URL') ? (SITE_URL ?: '<empty>') : '<not defined>') . "</p>";
} else {
    echo "<p class='error'>✗ config.php NOT FOUND</p>";
    die("Cannot continue without config.php");
}

// Test 2: Database connection
echo "<h2>2. Database Connection</h2>";
try {
    $mysqli = new mysqli(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if ($mysqli->connect_error) {
        echo "<p class='error'>✗ Database connection failed: " . $mysqli->connect_error . "</p>";
        die("Cannot continue without database");
    }
    echo "<p class='ok'>✓ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Database exception: " . $e->getMessage() . "</p>";
    die("Cannot continue without database");
}

// Test 3: Critical settings
echo "<h2>3. Critical Database Settings</h2>";

$critical_settings = ['license', 'product_info', 'main', 'payment', 'internal_notifications'];

foreach ($critical_settings as $key) {
    $result = $mysqli->query("SELECT `value` FROM `settings` WHERE `key` = '$key'");
    if ($result && $row = $result->fetch_assoc()) {
        $decoded = json_decode($row['value']);
        if ($key === 'license') {
            echo "<p class='ok'>✓ <strong>license</strong>: " . htmlspecialchars($row['value']) . "</p>";
            if (isset($decoded->type)) {
                echo "<p>&nbsp;&nbsp;&nbsp;→ license.type = <strong>" . $decoded->type . "</strong></p>";
                if (in_array($decoded->type, ['Extended License', 'extended'])) {
                    echo "<p class='ok'>&nbsp;&nbsp;&nbsp;→ Extended License: YES</p>";
                } else {
                    echo "<p class='error'>&nbsp;&nbsp;&nbsp;→ Extended License: NO (this may cause issues)</p>";
                }
            } else {
                echo "<p class='error'>&nbsp;&nbsp;&nbsp;→ license.type is MISSING</p>";
            }
        } elseif ($key === 'product_info') {
            echo "<p class='ok'>✓ <strong>product_info</strong>: " . htmlspecialchars(substr($row['value'], 0, 100)) . "...</p>";
            if (isset($decoded->version)) {
                echo "<p>&nbsp;&nbsp;&nbsp;→ version = " . $decoded->version . "</p>";
            }
        } else {
            echo "<p class='ok'>✓ <strong>$key</strong>: exists</p>";
        }
    } else {
        echo "<p class='error'>✗ <strong>$key</strong>: NOT FOUND IN DATABASE</p>";
    }
}

// Test 4: Check tables
echo "<h2>4. Required Tables</h2>";
$required_tables = ['settings', 'users', 'campaigns', 'notifications', 'payments', 'track_notifications', 'track_logs', 'track_conversions', 'plans'];

foreach ($required_tables as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        $count = $mysqli->query("SELECT COUNT(*) as cnt FROM `$table`")->fetch_assoc()['cnt'];
        echo "<p class='ok'>✓ $table (rows: $count)</p>";
    } else {
        echo "<p class='error'>✗ $table - TABLE MISSING</p>";
    }
}

// Test 5: Test admin stats AJAX
echo "<h2>5. Admin Stats Query Test</h2>";
try {
    $campaigns = $mysqli->query("SELECT count(`campaign_id`) as cnt FROM `campaigns`")->fetch_assoc()['cnt'] ?? 0;
    $notifications = $mysqli->query("SELECT count(`notification_id`) as cnt FROM `notifications`")->fetch_assoc()['cnt'] ?? 0;
    $users = $mysqli->query("SELECT count(`user_id`) as cnt FROM `users`")->fetch_assoc()['cnt'] ?? 0;
    $payments = $mysqli->query("SELECT count(`id`) as cnt FROM `payments`")->fetch_assoc()['cnt'] ?? 0;

    echo "<p class='ok'>✓ Campaigns: $campaigns</p>";
    echo "<p class='ok'>✓ Notifications: $notifications</p>";
    echo "<p class='ok'>✓ Users: $users</p>";
    echo "<p class='ok'>✓ Payments: $payments</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Query failed: " . $e->getMessage() . "</p>";
}

// Test 6: PHP OpCache
echo "<h2>6. PHP Cache Status</h2>";
if (function_exists('opcache_get_status')) {
    $status = @opcache_get_status();
    if ($status && $status['opcache_enabled']) {
        echo "<p class='warn'>⚠ OPcache is ENABLED - may need to be cleared after deployments</p>";
        echo "<p>&nbsp;&nbsp;&nbsp;To clear: Add <code>opcache_reset();</code> or restart PHP</p>";
    } else {
        echo "<p class='ok'>✓ OPcache is disabled or not available</p>";
    }
} else {
    echo "<p class='ok'>✓ OPcache extension not loaded</p>";
}

// Test 7: Check current deployed files
echo "<h2>7. File Deployment Check</h2>";
$check_file = 'app/controllers/admin/AdminPayments.php';
if (file_exists($check_file)) {
    $content = file_get_contents($check_file);
    if (strpos($content, 'License check removed for standalone') !== false) {
        echo "<p class='ok'>✓ AdminPayments.php has the updated code (license checks removed)</p>";
    } else {
        echo "<p class='error'>✗ AdminPayments.php still has OLD code - deployment may have failed!</p>";
    }
} else {
    echo "<p class='error'>✗ AdminPayments.php not found</p>";
}

$mysqli->close();

echo "<hr><p><strong>Diagnostics complete.</strong></p>";
echo "<p class='error'>⚠ DELETE THIS FILE (diagnose.php) when done!</p>";
?>
