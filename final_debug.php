<?php
/**
 * Final Debug Script - Tests the COMPLETE application flow
 *
 * Access: /final_debug.php?key=widgethook_fix_2024
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'widgethook_fix_2024') {
    http_response_code(403);
    die("Unauthorized");
}

// Force error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

echo "=== FINAL DEBUG SCRIPT ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n\n";

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "\n\n========== FATAL ERROR ==========\n";
        echo "Type: " . $error['type'] . "\n";
        echo "Message: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";

        if (file_exists($error['file'])) {
            $lines = file($error['file']);
            $start = max(0, $error['line'] - 5);
            $end = min(count($lines), $error['line'] + 5);
            echo "\nContext:\n";
            for ($i = $start; $i < $end; $i++) {
                $marker = ($i + 1 == $error['line']) ? ' >> ' : '    ';
                echo $marker . ($i + 1) . ': ' . rtrim($lines[$i]) . "\n";
            }
        }
        echo "=================================\n";
    }
});

// Step 1: Define constants (same as index.php)
echo "[1] Defining constants...\n";
const DEBUG = 0;  // Keep DEBUG=0 to use normal flow
const MYSQL_DEBUG = 0;
const LOGGING = 1;
const CACHE = 1;
const ALTUMCODE = 66;
echo "    Done.\n";

// Step 2: Load init.php
echo "\n[2] Loading init.php...\n";
echo "    (If script stops here, error is in init.php)\n";
require_once realpath(__DIR__) . '/app/init.php';
echo "    init.php loaded successfully!\n";

// Step 3: Check critical components
echo "\n[3] Checking components:\n";

// Check database
echo "    Database connection: ";
try {
    $db = db();
    if ($db) {
        // Try a simple query
        $result = $db->rawQuery("SELECT 1 as test");
        echo "OK\n";
    } else {
        echo "FAILED (null)\n";
    }
} catch (Throwable $e) {
    echo "ERROR - " . $e->getMessage() . "\n";
}

// Check settings
echo "    Settings loaded: ";
try {
    $s = settings();
    if ($s && isset($s->main)) {
        echo "OK (site: " . ($s->main->title ?? 'N/A') . ")\n";
    } else {
        echo "FAILED\n";
    }
} catch (Throwable $e) {
    echo "ERROR - " . $e->getMessage() . "\n";
}

// Check user function
echo "    user() function: ";
try {
    $u = user();
    echo ($u ? "logged in" : "not logged in") . "\n";
} catch (Throwable $e) {
    echo "ERROR - " . $e->getMessage() . "\n";
}

// Step 4: Test App instantiation
echo "\n[4] Creating Altum\\App instance...\n";
echo "    (If script stops here, error is in App constructor)\n";
try {
    $App = new Altum\App();
    echo "    App created successfully!\n";
} catch (Throwable $e) {
    echo "    ERROR: " . $e->getMessage() . "\n";
    echo "    File: " . $e->getFile() . "\n";
    echo "    Line: " . $e->getLine() . "\n";
    echo "    Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
echo "\nIf you see this message, the application loaded successfully!\n";
echo "The 500 error might be route-specific. Try:\n";
echo "  1. Clear your browser cache\n";
echo "  2. Try a different page\n";
echo "  3. Check uploads/logs/ for error logs\n";
