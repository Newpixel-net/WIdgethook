<?php
/**
 * Cache Reset Script
 * Clears OPcache and file cache to ensure latest code is loaded
 *
 * Access: /reset_cache.php?key=YOUR_SECRET_KEY
 *
 * IMPORTANT: Delete this file after debugging, or set a strong RESET_KEY
 */

// Simple security - set your own secret key
define('RESET_KEY', 'widgethook_reset_2024');

// Check authorization
if (!isset($_GET['key']) || $_GET['key'] !== RESET_KEY) {
    http_response_code(403);
    die('Unauthorized. Usage: reset_cache.php?key=YOUR_SECRET_KEY');
}

header('Content-Type: text/plain');
echo "=== Cache Reset Script ===\n\n";

// 1. Reset OPcache
echo "1. OPcache Status:\n";
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    if ($status) {
        echo "   - OPcache enabled: Yes\n";
        echo "   - Cached scripts: " . ($status['opcache_statistics']['num_cached_scripts'] ?? 'N/A') . "\n";
        echo "   - Memory used: " . round(($status['memory_usage']['used_memory'] ?? 0) / 1024 / 1024, 2) . " MB\n";

        // Reset OPcache
        if (function_exists('opcache_reset')) {
            $result = opcache_reset();
            echo "   - opcache_reset(): " . ($result ? "SUCCESS" : "FAILED") . "\n";
        }
    } else {
        echo "   - OPcache not active or restricted\n";
    }
} else {
    echo "   - OPcache extension not loaded\n";
}

// 2. Invalidate specific critical files
echo "\n2. Invalidating critical files:\n";
$critical_files = [
    __DIR__ . '/app/init.php',
    __DIR__ . '/app/core/Controller.php',
    __DIR__ . '/app/core/Model.php',
    __DIR__ . '/app/core/View.php',
    __DIR__ . '/app/traits/Paramsable.php',
    __DIR__ . '/app/traits/Apiable.php',
    __DIR__ . '/index.php',
];

foreach ($critical_files as $file) {
    if (file_exists($file) && function_exists('opcache_invalidate')) {
        $result = opcache_invalidate($file, true);
        $relative = str_replace(__DIR__, '', $file);
        echo "   - {$relative}: " . ($result ? "invalidated" : "not cached/failed") . "\n";
    }
}

// 3. Clear file cache (uploads/cache directory)
echo "\n3. File Cache:\n";
$cache_dir = __DIR__ . '/uploads/cache';
if (is_dir($cache_dir)) {
    $files = glob($cache_dir . '/*');
    $count = 0;
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== '.gitkeep') {
            unlink($file);
            $count++;
        }
    }
    echo "   - Cleared {$count} cache files from uploads/cache\n";
} else {
    echo "   - Cache directory not found\n";
}

// 4. Verify autoloader can find trait files
echo "\n4. File Verification:\n";
$verify_files = [
    'app/traits/Paramsable.php' => __DIR__ . '/app/traits/Paramsable.php',
    'app/traits/Apiable.php' => __DIR__ . '/app/traits/Apiable.php',
    'app/core/Controller.php' => __DIR__ . '/app/core/Controller.php',
];

foreach ($verify_files as $name => $path) {
    $exists = file_exists($path);
    $readable = is_readable($path);
    echo "   - {$name}: " . ($exists ? "exists" : "MISSING") . ", " . ($readable ? "readable" : "NOT READABLE") . "\n";
}

// 5. Test autoloader
echo "\n5. Autoloader Test:\n";
const ALTUMCODE = 66;
const DEBUG = 1;
const MYSQL_DEBUG = 0;
const LOGGING = 0;
const CACHE = 1;

// Register a test autoloader to trace what's being loaded
$autoload_log = [];
spl_autoload_register(function($class) use (&$autoload_log) {
    $autoload_log[] = $class;
}, true, true); // prepend to log before actual loading

try {
    require_once __DIR__ . '/app/init.php';
    echo "   - init.php loaded: OK\n";
    echo "   - Classes autoloaded during init:\n";
    foreach ($autoload_log as $class) {
        echo "     * {$class}\n";
    }
} catch (Throwable $e) {
    echo "   - ERROR: " . $e->getMessage() . "\n";
    echo "   - File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Cache Reset Complete ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
