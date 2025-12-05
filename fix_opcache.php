<?php
/**
 * OPcache Fix Script
 * This script clears OPcache and verifies the Paramsable trait can be loaded
 *
 * Access: /fix_opcache.php?key=widgethook_fix_2024
 * DELETE THIS FILE AFTER USE
 */

define('FIX_KEY', 'widgethook_fix_2024');

if (!isset($_GET['key']) || $_GET['key'] !== FIX_KEY) {
    http_response_code(403);
    die("Unauthorized. Usage: fix_opcache.php?key=widgethook_fix_2024");
}

header('Content-Type: text/plain');
echo "=== OPCACHE FIX SCRIPT ===\n\n";

// Step 1: Check OPcache status
echo "[1] Checking OPcache status...\n";
if (function_exists('opcache_get_status')) {
    $status = @opcache_get_status(false);
    if ($status && isset($status['opcache_enabled']) && $status['opcache_enabled']) {
        echo "    OPcache is ENABLED\n";
        echo "    Cached scripts: " . ($status['opcache_statistics']['num_cached_scripts'] ?? 'N/A') . "\n";

        // Step 2: Reset OPcache
        echo "\n[2] Resetting OPcache...\n";
        if (function_exists('opcache_reset')) {
            $reset = @opcache_reset();
            echo "    opcache_reset(): " . ($reset ? "SUCCESS" : "FAILED") . "\n";
        }

        // Step 3: Invalidate specific files
        echo "\n[3] Invalidating specific files...\n";
        $files_to_invalidate = [
            __DIR__ . '/app/traits/Paramsable.php',
            __DIR__ . '/app/traits/Apiable.php',
            __DIR__ . '/app/core/Controller.php',
            __DIR__ . '/app/core/Model.php',
            __DIR__ . '/app/init.php',
            __DIR__ . '/index.php',
        ];

        foreach ($files_to_invalidate as $file) {
            if (file_exists($file) && function_exists('opcache_invalidate')) {
                $result = @opcache_invalidate($file, true);
                echo "    " . basename($file) . ": " . ($result ? "INVALIDATED" : "skipped") . "\n";
            }
        }
    } else {
        echo "    OPcache is DISABLED or not available\n";
    }
} else {
    echo "    OPcache extension not loaded\n";
}

// Step 4: Define constants and test autoloader
echo "\n[4] Testing autoloader...\n";
define('ALTUMCODE', 66);
define('ROOT_PATH', realpath(__DIR__) . '/');
define('APP_PATH', ROOT_PATH . 'app/');

// Register autoloader (same as init.php)
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

    if ($file && file_exists($file)) {
        require_once $file;
    }
});

echo "    Autoloader registered\n";

// Step 5: Test trait loading
echo "\n[5] Testing trait loading...\n";
try {
    $trait_file = APP_PATH . 'traits/Paramsable.php';
    echo "    File path: " . $trait_file . "\n";
    echo "    File exists: " . (file_exists($trait_file) ? 'YES' : 'NO') . "\n";
    echo "    File size: " . (file_exists($trait_file) ? filesize($trait_file) . " bytes" : 'N/A') . "\n";

    // Check file content for namespace
    if (file_exists($trait_file)) {
        $content = file_get_contents($trait_file);
        $has_namespace = strpos($content, 'namespace Altum\\Traits;') !== false;
        $has_trait = strpos($content, 'trait Paramsable') !== false;
        echo "    Has correct namespace: " . ($has_namespace ? 'YES' : 'NO') . "\n";
        echo "    Has trait definition: " . ($has_trait ? 'YES' : 'NO') . "\n";
    }

    // Try to trigger autoload
    $exists = trait_exists('Altum\\Traits\\Paramsable');
    echo "    trait_exists('Altum\\Traits\\Paramsable'): " . ($exists ? 'YES' : 'NO') . "\n";
} catch (Throwable $e) {
    echo "    ERROR: " . $e->getMessage() . "\n";
}

// Step 6: Test Controller loading
echo "\n[6] Testing Controller loading...\n";
try {
    require_once APP_PATH . 'core/Controller.php';
    echo "    [SUCCESS] Controller.php loaded successfully!\n";
    echo "    class_exists('Altum\\Controllers\\Controller'): " .
         (class_exists('Altum\\Controllers\\Controller') ? 'YES' : 'NO') . "\n";
} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . "\n";
    echo "    File: " . $e->getFile() . "\n";
    echo "    Line: " . $e->getLine() . "\n";
}

echo "\n=== FIX COMPLETE ===\n";
echo "\nIf all tests passed, try loading your site again.\n";
echo "If issues persist, add this to your .htaccess file:\n";
echo "    php_flag opcache.enable Off\n";
echo "\n*** DELETE THIS FILE (fix_opcache.php) WHEN DONE ***\n";
