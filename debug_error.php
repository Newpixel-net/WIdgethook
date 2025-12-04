<?php
/**
 * Debug error display - access via yoursite.com/debug_error.php
 * DELETE THIS FILE AFTER DEBUGGING
 */

// Enable all error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Override the shutdown handler to show actual errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error) {
        echo "<h2>PHP Error Details:</h2>";
        echo "<pre>";
        print_r($error);
        echo "</pre>";
    }
});

// Define required constants
define('ALTUMCODE', 1);
define('ROOT_PATH', __DIR__ . '/');

// Try to load the application and catch any errors
try {
    echo "<h1>Debug Mode - Testing Application Load</h1>";
    echo "<p>PHP Version: " . PHP_VERSION . "</p>";
    echo "<p>Server Time: " . date('Y-m-d H:i:s') . "</p>";
    echo "<hr>";

    // Test config file
    if (file_exists('config.php')) {
        echo "<p>✓ config.php exists</p>";
        require_once 'config.php';
        echo "<p>✓ config.php loaded successfully</p>";
        echo "<p>DEBUG constant: " . (defined('DEBUG') ? (DEBUG ? 'true' : 'false') : 'not defined') . "</p>";
        echo "<p>SITE_URL: " . (defined('SITE_URL') ? SITE_URL : 'not defined') . "</p>";
    } else {
        echo "<p>✗ config.php NOT FOUND</p>";
    }

    echo "<hr>";

    // Test database connection
    if (defined('DATABASE_SERVER') && defined('DATABASE_NAME')) {
        echo "<p>Testing database connection...</p>";
        $mysqli = @new mysqli(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
        if ($mysqli->connect_error) {
            echo "<p>✗ Database connection failed: " . $mysqli->connect_error . "</p>";
        } else {
            echo "<p>✓ Database connected successfully</p>";

            // Check for settings table
            $result = $mysqli->query("SELECT * FROM settings WHERE `key` = 'license'");
            if ($result && $row = $result->fetch_assoc()) {
                echo "<p>✓ License setting found: " . htmlspecialchars($row['value']) . "</p>";
            } else {
                echo "<p>✗ License setting NOT FOUND in database</p>";
            }

            // Check for product_info
            $result = $mysqli->query("SELECT * FROM settings WHERE `key` = 'product_info'");
            if ($result && $row = $result->fetch_assoc()) {
                echo "<p>✓ Product info found: " . htmlspecialchars($row['value']) . "</p>";
            } else {
                echo "<p>✗ Product info NOT FOUND in database</p>";
            }

            $mysqli->close();
        }
    }

    echo "<hr>";
    echo "<h2>Now attempting full application load...</h2>";

    // Try loading init.php
    require_once 'app/init.php';
    echo "<p>✓ app/init.php loaded successfully</p>";

} catch (Throwable $e) {
    echo "<h2>Exception caught:</h2>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
