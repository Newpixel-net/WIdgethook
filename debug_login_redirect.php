<?php
/**
 * Debug Login Redirect Issue
 *
 * Access: /debug_login_redirect.php?key=widgethook_fix_2024
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'widgethook_fix_2024') {
    http_response_code(403);
    die("Unauthorized");
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG LOGIN REDIRECT ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// Shutdown handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "\n\n========== FATAL ERROR ==========\n";
        echo "Message: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";

        if (file_exists($error['file'])) {
            $lines = file($error['file']);
            $start = max(0, $error['line'] - 3);
            $end = min(count($lines), $error['line'] + 3);
            echo "\nContext:\n";
            for ($i = $start; $i < $end; $i++) {
                $marker = ($i + 1 == $error['line']) ? '>>> ' : '    ';
                echo $marker . ($i + 1) . ': ' . rtrim($lines[$i]) . "\n";
            }
        }
    }
});

// Simulate the login?redirect=dashboard request
$_GET['altum'] = 'login';
$_GET['redirect'] = 'dashboard';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/login?redirect=dashboard';
$_SERVER['QUERY_STRING'] = 'redirect=dashboard';

echo "[1] Simulating: /login?redirect=dashboard\n\n";

// Load the app
const DEBUG = 1;
const MYSQL_DEBUG = 0;
const LOGGING = 0;
const CACHE = 0;
const ALTUMCODE = 66;

echo "[2] Loading init.php...\n";
require_once realpath(__DIR__) . '/app/init.php';
echo "    OK\n\n";

echo "[3] Checking settings...\n";
$s = settings();
echo "    Site: " . ($s->main->title ?? 'N/A') . "\n";
echo "    Users register enabled: " . ($s->users->register_is_enabled ? 'YES' : 'NO') . "\n\n";

echo "[4] Testing process_and_get_redirect_params()...\n";
$redirect = process_and_get_redirect_params();
echo "    Result: " . var_export($redirect, true) . "\n\n";

echo "[5] Creating App (this will try to render the login page)...\n";
try {
    ob_start();
    $App = new Altum\App();
    $output = ob_get_clean();

    echo "    App created successfully!\n";
    echo "    Output length: " . strlen($output) . " bytes\n";

    if (strpos($output, 'Internal server error') !== false) {
        echo "    WARNING: Output contains error page!\n";
    } elseif (strpos($output, '<html') !== false || strpos($output, '<!DOCTYPE') !== false) {
        echo "    Response: HTML page (good!)\n";
    }

    // Check for specific elements
    if (strpos($output, 'login') !== false || strpos($output, 'Login') !== false) {
        echo "    Contains login form: YES\n";
    }

} catch (Throwable $e) {
    echo "    ERROR: " . $e->getMessage() . "\n";
    echo "    File: " . $e->getFile() . "\n";
    echo "    Line: " . $e->getLine() . "\n";
    echo "    Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
