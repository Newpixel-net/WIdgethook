<?php
/**
 * Simple autoloader test - diagnose why Paramsable trait isn't loading
 * Access: /test_autoloader.php
 * DELETE AFTER USE
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== AUTOLOADER TEST ===\n\n";

// Define constants
define('ALTUMCODE', 66);
define('ROOT_PATH', realpath(__DIR__) . '/');
define('APP_PATH', ROOT_PATH . 'app/');

echo "1. ROOT_PATH: " . ROOT_PATH . "\n";
echo "2. APP_PATH: " . APP_PATH . "\n\n";

// Check if traits directory exists
$traits_dir = APP_PATH . 'traits/';
echo "3. Checking traits directory: {$traits_dir}\n";
echo "   - Directory exists: " . (is_dir($traits_dir) ? 'YES' : 'NO') . "\n";
echo "   - Directory readable: " . (is_readable($traits_dir) ? 'YES' : 'NO') . "\n\n";

// Check Paramsable.php specifically
$paramsable_file = $traits_dir . 'Paramsable.php';
echo "4. Checking Paramsable.php: {$paramsable_file}\n";
echo "   - File exists: " . (file_exists($paramsable_file) ? 'YES' : 'NO') . "\n";
echo "   - File readable: " . (is_readable($paramsable_file) ? 'YES' : 'NO') . "\n";
echo "   - File size: " . (file_exists($paramsable_file) ? filesize($paramsable_file) . " bytes" : 'N/A') . "\n";

if (file_exists($paramsable_file)) {
    $content = file_get_contents($paramsable_file);
    echo "   - First 500 chars:\n";
    echo "   " . str_replace("\n", "\n   ", substr($content, 0, 500)) . "\n";

    // Check if namespace is correct
    if (strpos($content, 'namespace Altum\\Traits;') !== false) {
        echo "\n   [OK] Namespace 'Altum\\Traits' found in file\n";
    } else {
        echo "\n   [ERROR] Namespace 'Altum\\Traits' NOT found in file!\n";
    }

    // Check if trait definition exists
    if (strpos($content, 'trait Paramsable') !== false) {
        echo "   [OK] 'trait Paramsable' found in file\n";
    } else {
        echo "   [ERROR] 'trait Paramsable' NOT found in file!\n";
    }
}

// Check OPcache status
echo "\n5. OPcache Status:\n";
if (function_exists('opcache_get_status')) {
    $status = @opcache_get_status(false);
    if ($status) {
        echo "   - OPcache enabled: " . ($status['opcache_enabled'] ? 'YES' : 'NO') . "\n";
        echo "   - Cached scripts: " . ($status['opcache_statistics']['num_cached_scripts'] ?? 'N/A') . "\n";
        echo "   - Cache full: " . ($status['cache_full'] ? 'YES' : 'NO') . "\n";

        // Try to reset OPcache
        if (function_exists('opcache_reset')) {
            echo "\n   Attempting to reset OPcache...\n";
            $reset = @opcache_reset();
            echo "   Reset result: " . ($reset ? 'SUCCESS' : 'FAILED') . "\n";
        }
    } else {
        echo "   - OPcache not available or disabled\n";
    }
} else {
    echo "   - OPcache extension not loaded\n";
}

// Now test the actual autoloader
echo "\n6. Testing autoloader registration and trait loading:\n";

// Register the autoloader (same as init.php)
spl_autoload_register(function ($class) {
    $namespace_prefix = 'Altum';
    $split = explode('\\', $class);

    if ($split[0] !== $namespace_prefix) {
        return;
    }

    $file = null;

    if (isset($split[1], $split[2]) && in_array($split[1], ['Traits', 'Models', 'Helpers'])) {
        $folder = mb_strtolower($split[1]);
        $file = APP_PATH . $folder . '/' . $split[2] . '.php';
    } elseif (isset($split[1], $split[2]) && $split[1] == 'PaymentGateways') {
        $file = APP_PATH . 'helpers/payment-gateways/' . $split[2] . '.php';
    } elseif (isset($split[1]) && !isset($split[2])) {
        $file = APP_PATH . 'core/' . $split[1] . '.php';
    }

    echo "   Autoloader called for: {$class}\n";
    echo "   Resolved file: " . ($file ?? 'NULL') . "\n";
    echo "   File exists: " . ($file && file_exists($file) ? 'YES' : 'NO') . "\n";

    if ($file && file_exists($file)) {
        require_once $file;
        echo "   [OK] File loaded\n";
    } else {
        echo "   [ERROR] File not found or path is null\n";
    }
});

echo "   Autoloader registered.\n\n";

// Try to check if trait exists
echo "7. Checking if trait exists after autoloader:\n";
echo "   trait_exists('Altum\\Traits\\Paramsable'): ";
try {
    $exists = trait_exists('Altum\\Traits\\Paramsable');
    echo ($exists ? 'YES' : 'NO') . "\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Try to load Controller.php
echo "\n8. Attempting to load Controller.php:\n";
try {
    require_once APP_PATH . 'core/Controller.php';
    echo "   [OK] Controller.php loaded successfully!\n";
    echo "   class_exists('Altum\\Controllers\\Controller'): " . (class_exists('Altum\\Controllers\\Controller') ? 'YES' : 'NO') . "\n";
} catch (Throwable $e) {
    echo "   [ERROR] " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "</pre>";
