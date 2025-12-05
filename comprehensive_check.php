<?php
/**
 * Comprehensive Application Check
 * Tests each component step by step
 *
 * Access: /comprehensive_check.php?key=widgethook_fix_2024
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'widgethook_fix_2024') {
    http_response_code(403);
    die("Unauthorized");
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "=== COMPREHENSIVE APPLICATION CHECK ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

$errors = [];

// Shutdown handler for fatal errors
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
            echo "\nCode:\n";
            for ($i = $start; $i < $end; $i++) {
                $marker = ($i + 1 == $error['line']) ? '>>> ' : '    ';
                echo $marker . ($i + 1) . ': ' . rtrim($lines[$i]) . "\n";
            }
        }
    }
});

// Test 1: Constants (DEBUG=1 to see real errors, not pretty 500 page)
echo "[1] Defining constants...\n";
const DEBUG = 1;  // Important: must be 1 to bypass 500.php error handler
const MYSQL_DEBUG = 0;
const LOGGING = 0;
const CACHE = 0;
const ALTUMCODE = 66;
echo "    OK\n";

// Test 2: Load init.php (this defines paths and autoloader)
echo "\n[2] Loading init.php...\n";
require_once realpath(__DIR__) . '/app/init.php';
echo "    init.php loaded OK\n";
echo "    ROOT_PATH: " . ROOT_PATH . "\n";
echo "    APP_PATH: " . APP_PATH . "\n";

// Test 3: Database connection
echo "\n[3] Testing database...\n";
try {
    $db = db();
    $result = $db->rawQuery("SELECT 1 as test");
    echo "    Connection: OK\n";
} catch (Exception $e) {
    echo "    Connection: FAILED - " . $e->getMessage() . "\n";
    $errors[] = "Database connection failed";
}

// Test 4: Settings
echo "\n[4] Testing settings...\n";
try {
    $s = settings();
    echo "    Main title: " . ($s->main->title ?? 'N/A') . "\n";
    echo "    Plan free exists: " . (isset($s->plan_free) ? 'YES' : 'NO') . "\n";
    echo "    Plan custom exists: " . (isset($s->plan_custom) ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "    FAILED: " . $e->getMessage() . "\n";
    $errors[] = "Settings loading failed";
}

// Test 5: Critical tables
echo "\n[5] Checking critical database tables...\n";
$tables_to_check = [
    'users' => ['user_id', 'email', 'plan_id', 'plan_settings'],
    'settings' => ['id', 'key', 'value'],
    'pages' => ['page_id', 'url', 'title', 'is_published', 'language', 'icon', 'plans_ids'],
    'plans' => ['plan_id', 'name', 'settings'],
];

foreach ($tables_to_check as $table => $expected_columns) {
    echo "    Table '$table': ";
    $result = db()->rawQuery("DESCRIBE `$table`");
    if ($result) {
        $columns = array_column($result, 'Field');
        $missing = array_diff($expected_columns, $columns);
        if (empty($missing)) {
            echo "OK (" . count($columns) . " columns)\n";
        } else {
            echo "MISSING columns: " . implode(', ', $missing) . "\n";
            $errors[] = "Table $table missing columns: " . implode(', ', $missing);
        }
    } else {
        echo "ERROR - table may not exist\n";
        $errors[] = "Table $table not found or error";
    }
}

// Test 6: Pages query (the one that was failing)
echo "\n[6] Testing pages query...\n";
try {
    $result = database()->query("SELECT `url`, `title`, `type`, `open_in_new_tab`, `language`, `icon`, `position`, `plans_ids` FROM `pages` WHERE `is_published` = 1 ORDER BY `order`");
    if ($result) {
        $count = 0;
        while ($row = $result->fetch_object()) {
            $count++;
        }
        echo "    Query OK - found $count pages\n";
    } else {
        echo "    Query returned FALSE\n";
        $errors[] = "Pages query returned false";
    }
} catch (Exception $e) {
    echo "    FAILED: " . $e->getMessage() . "\n";
    $errors[] = "Pages query failed: " . $e->getMessage();
}

// Test 7: Controller loading
echo "\n[7] Testing Controller...\n";
if (class_exists('Altum\Controllers\Controller')) {
    echo "    Controller class: OK\n";
} else {
    echo "    Controller class: NOT FOUND\n";
    $errors[] = "Controller class not found";
}

// Test 8: App instantiation
echo "\n[8] Testing App instantiation...\n";
try {
    // Simulate a basic request
    $_GET['altum'] = '';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['HTTP_HOST'] = parse_url(SITE_URL, PHP_URL_HOST);
    $_SERVER['REQUEST_URI'] = '/';

    ob_start();
    $App = new Altum\App();
    $output = ob_get_clean();

    echo "    App created: OK\n";
    echo "    Output length: " . strlen($output) . " bytes\n";

    if (strlen($output) > 0) {
        // Check if it's HTML
        if (strpos($output, '<html') !== false || strpos($output, '<!DOCTYPE') !== false) {
            echo "    Response type: HTML (good!)\n";
        } elseif (strpos($output, 'Internal server error') !== false) {
            echo "    Response type: ERROR PAGE\n";
            $errors[] = "App returned error page";
        }
    }
} catch (Exception $e) {
    echo "    FAILED: " . $e->getMessage() . "\n";
    echo "    File: " . $e->getFile() . "\n";
    echo "    Line: " . $e->getLine() . "\n";
    $errors[] = "App instantiation failed: " . $e->getMessage();
}

// Summary
echo "\n\n========== SUMMARY ==========\n";
if (empty($errors)) {
    echo "All checks passed!\n";
    echo "The site should be working now.\n";
} else {
    echo "Found " . count($errors) . " issue(s):\n";
    foreach ($errors as $i => $error) {
        echo "  " . ($i + 1) . ". $error\n";
    }
}

echo "\n*** DELETE THIS FILE WHEN DONE ***\n";
