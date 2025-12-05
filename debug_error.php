<?php
/**
 * Debug the actual error - bypasses the pretty 500 error handler
 *
 * Access: /debug_error.php?key=widgethook_fix_2024
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'widgethook_fix_2024') {
    http_response_code(403);
    die("Unauthorized");
}

// Force all errors to display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// IMPORTANT: Set DEBUG = 1 BEFORE including index.php
// This prevents 500.php from hiding the error
const DEBUG = 1;
const MYSQL_DEBUG = 0;
const LOGGING = 0;
const CACHE = 0;

echo "<pre>\n";
echo "=== DEBUG ERROR SCRIPT ===\n\n";
echo "DEBUG mode enabled - actual errors will be shown.\n\n";

// Register our own shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "\n\n=== FATAL ERROR ===\n";
        echo "Type: " . $error['type'] . "\n";
        echo "Message: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";

        // Show some context from the file
        if (file_exists($error['file'])) {
            $lines = file($error['file']);
            $start = max(0, $error['line'] - 5);
            $end = min(count($lines), $error['line'] + 5);
            echo "\nCode context:\n";
            for ($i = $start; $i < $end; $i++) {
                $marker = ($i + 1 == $error['line']) ? ' >> ' : '    ';
                echo $marker . ($i + 1) . ': ' . $lines[$i];
            }
        }
    }
});

echo "About to load the application...\n\n";

// This is what index.php does
const ALTUMCODE = 66;
require_once realpath(__DIR__) . '/app/init.php';

echo "[OK] init.php loaded successfully\n";

// Now try creating App
echo "[..] Creating Altum\\App instance...\n";
$App = new Altum\App();

echo "[OK] App created successfully!\n";
echo "\n=== NO ERRORS - SITE SHOULD WORK ===\n";
echo "</pre>";
