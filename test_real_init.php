<?php
/**
 * Test the REAL init.php flow
 * This mimics exactly what index.php does
 *
 * Access: /test_real_init.php?key=widgethook_fix_2024
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'widgethook_fix_2024') {
    http_response_code(403);
    die("Unauthorized");
}

// Enable all error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');
echo "=== TESTING REAL INIT.PHP FLOW ===\n\n";

// Set up error handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "\n\n=== FATAL ERROR DETECTED ===\n";
        echo "Type: " . $error['type'] . "\n";
        echo "Message: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";
    }
});

echo "[1] Current directory: " . __DIR__ . "\n";
echo "[2] Checking if init.php exists: " . (file_exists(__DIR__ . '/app/init.php') ? 'YES' : 'NO') . "\n";

// Check what ALTUMCODE is set to in index.php
echo "[3] Reading index.php to check ALTUMCODE...\n";
$index_content = file_get_contents(__DIR__ . '/index.php');
if (preg_match("/define\s*\(\s*['\"]ALTUMCODE['\"]\s*,\s*(\d+)\s*\)/", $index_content, $matches)) {
    echo "    ALTUMCODE value in index.php: " . $matches[1] . "\n";
}

echo "\n[4] About to require init.php...\n";
echo "    (If this is the last line you see, init.php crashed)\n\n";

// This is EXACTLY what index.php does
define('ALTUMCODE', 66);
require_once __DIR__ . '/app/init.php';

echo "[5] init.php loaded successfully!\n";
echo "[6] Checking loaded classes/traits:\n";
echo "    - Altum\\Traits\\Paramsable: " . (trait_exists('Altum\\Traits\\Paramsable') ? 'YES' : 'NO') . "\n";
echo "    - Altum\\Controllers\\Controller: " . (class_exists('Altum\\Controllers\\Controller') ? 'YES' : 'NO') . "\n";

echo "\n[7] Checking if database connected:\n";
echo "    - db() function exists: " . (function_exists('db') ? 'YES' : 'NO') . "\n";
if (function_exists('db')) {
    try {
        $db = db();
        echo "    - db() returns: " . (is_object($db) ? get_class($db) : gettype($db)) . "\n";
    } catch (Throwable $e) {
        echo "    - db() error: " . $e->getMessage() . "\n";
    }
}

echo "\n[8] Checking settings:\n";
echo "    - settings() function exists: " . (function_exists('settings') ? 'YES' : 'NO') . "\n";
if (function_exists('settings')) {
    try {
        $settings = settings();
        echo "    - settings() returns: " . (is_object($settings) ? 'object' : gettype($settings)) . "\n";
    } catch (Throwable $e) {
        echo "    - settings() error: " . $e->getMessage() . "\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
echo "If you see this, init.php works correctly!\n";
echo "The 500 error must be happening later in the routing/controller phase.\n";
