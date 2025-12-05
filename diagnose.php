<?php
/**
 * COMPREHENSIVE DIAGNOSTIC SCRIPT
 * Identifies where the 500 error is occurring
 *
 * Access: /diagnose.php?key=widgethook_diagnose_2024
 * Full test: /diagnose.php?key=widgethook_diagnose_2024&full=1
 *
 * DELETE THIS FILE AFTER DEBUGGING
 */

define('DIAG_KEY', 'widgethook_diagnose_2024');

if (!isset($_GET['key']) || $_GET['key'] !== DIAG_KEY) {
    http_response_code(403);
    die("Unauthorized. Usage: diagnose.php?key=widgethook_diagnose_2024");
}

// Enable full error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');
echo "=== COMPREHENSIVE DIAGNOSTIC SCRIPT ===\n";
echo "======================================\n\n";
echo "PHP Version: " . PHP_VERSION . "\n\n";

// Step 1: Define constants
echo "[1] Defining constants...\n";
try {
    define('ALTUMCODE', 66);
    define('DEBUG', 1);
    define('MYSQL_DEBUG', 0);
    define('LOGGING', 0);
    define('CACHE', 0);
    define('ROOT_PATH', realpath(__DIR__) . '/');
    define('APP_PATH', ROOT_PATH . 'app/');
    define('PLUGINS_PATH', ROOT_PATH . 'plugins/');
    define('THEME_PATH', ROOT_PATH . 'themes/altum/');
    define('THEME_URL_PATH', 'themes/altum/');
    define('ASSETS_PATH', THEME_PATH . 'assets/');
    define('ASSETS_URL_PATH', THEME_URL_PATH . 'assets/');
    define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
    define('UPLOADS_URL_PATH', 'uploads/');
    define('CACHE_DEFAULT_SECONDS', 2592000);
    echo "    [OK] Constants defined\n";
} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    die();
}

// Step 2: Load config
echo "\n[2] Loading config.php...\n";
try {
    if (!file_exists(ROOT_PATH . 'config.php')) {
        echo "    [ERROR] config.php does not exist!\n";
        die();
    }
    require_once ROOT_PATH . 'config.php';
    echo "    [OK] Config loaded\n";
    echo "    DATABASE_SERVER: " . (defined('DATABASE_SERVER') ? DATABASE_SERVER : 'NOT DEFINED') . "\n";
    echo "    DATABASE_NAME: " . (defined('DATABASE_NAME') ? DATABASE_NAME : 'NOT DEFINED') . "\n";
    echo "    SITE_URL: " . (defined('SITE_URL') ? SITE_URL : 'NOT DEFINED') . "\n";
} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    die();
}

// Step 3: Load product info
echo "\n[3] Loading product.php...\n";
try {
    require_once APP_PATH . 'includes/product.php';
    echo "    [OK] Product info loaded\n";
} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    die();
}

// Step 4: Set session params
echo "\n[4] Setting session params...\n";
try {
    define('COOKIE_PATH', preg_replace('|https?://[^/]+|i', '', SITE_URL));
    echo "    [OK] COOKIE_PATH: " . COOKIE_PATH . "\n";
} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    die();
}

// Step 5: Load helpers
echo "\n[5] Loading helper files...\n";
$helpers = [
    'links.php',
    'strings.php',
    'email.php',
    'others.php',
    'core.php',
    'sessions.php',
    'socialproofo.php'
];
foreach ($helpers as $helper) {
    try {
        require_once APP_PATH . 'helpers/' . $helper;
        echo "    [OK] Loaded {$helper}\n";
    } catch (Throwable $e) {
        echo "    [ERROR] {$helper}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}

// Step 6: Register custom autoloader FIRST (before loading Controller.php!)
echo "\n[6] Registering custom autoloader...\n";
try {
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
    echo "    [OK] Autoloader registered\n";
} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Step 7: Check if traits directory and files exist
echo "\n[7] Checking traits directory...\n";
$traits_dir = APP_PATH . 'traits/';
if (is_dir($traits_dir)) {
    echo "    [OK] Traits directory exists: {$traits_dir}\n";
    $trait_files = glob($traits_dir . '*.php');
    foreach ($trait_files as $tf) {
        echo "    [OK] Found: " . basename($tf) . "\n";
    }

    // Specifically check Paramsable.php
    if (file_exists($traits_dir . 'Paramsable.php')) {
        echo "    [OK] Paramsable.php exists\n";
    } else {
        echo "    [ERROR] Paramsable.php NOT FOUND - THIS IS THE PROBLEM!\n";
    }
} else {
    echo "    [ERROR] Traits directory NOT FOUND: {$traits_dir}\n";
    echo "    THIS IS LIKELY THE CAUSE OF THE 500 ERROR!\n";
}

// Step 8: Load core classes
echo "\n[8] Loading core classes...\n";
try {
    require_once APP_PATH . 'core/Controller.php';
    echo "    [OK] Controller.php\n";
    require_once APP_PATH . 'core/Model.php';
    echo "    [OK] Model.php\n";
} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Step 9: Load vendor autoloader
echo "\n[9] Loading vendor autoloader...\n";
try {
    require_once ROOT_PATH . 'vendor/autoload.php';
    echo "    [OK] Vendor autoloader loaded\n";
} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    die();
}

// Step 10: Test database connection
echo "\n[10] Testing database connection...\n";
try {
    $mysqli = new mysqli(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if ($mysqli->connect_error) {
        echo "    [ERROR] Connection failed: " . $mysqli->connect_error . "\n";
        die();
    }
    echo "    [OK] Database connected\n";

    // Check settings table
    $result = $mysqli->query("SELECT `key` FROM settings");
    echo "    [OK] Settings table has " . $result->num_rows . " rows\n";

    // List all settings
    $settings_keys = [];
    while ($row = $result->fetch_assoc()) {
        $settings_keys[] = $row['key'];
    }
    echo "    Settings keys: " . implode(', ', $settings_keys) . "\n";

    $mysqli->close();
} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Step 11: Initialize Cache
echo "\n[10] Initializing Cache...\n";
try {
    \Altum\Cache::initialize();
    echo "    [OK] Cache initialized with driver: " . \Altum\Cache::$driver . "\n";
} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "    Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Step 12: Initialize Plugin system
echo "\n[11] Initializing Plugin system...\n";
try {
    \Altum\Plugin::initialize();
    echo "    [OK] Plugin system initialized\n";
    echo "    Active plugins: " . implode(', ', array_keys(\Altum\Plugin::$plugins)) . "\n";
} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "    Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Step 13: Test settings() function
echo "\n[12] Testing settings() function...\n";
try {
    $settings = settings();
    echo "    [OK] settings() returned\n";

    // List all settings keys
    $keys = [];
    foreach ($settings as $key => $value) {
        $keys[] = $key;
    }
    echo "    Settings keys from DB: " . implode(', ', $keys) . "\n";

    // Check critical settings
    echo "\n    Checking critical settings:\n";

    // main settings
    if (isset($settings->main)) {
        echo "    [OK] settings->main exists\n";
        $main_props = ['title', 'default_language', 'default_theme_style', 'admin_spotlight_is_enabled', 'user_spotlight_is_enabled', 'white_labeling_is_enabled'];
        foreach ($main_props as $prop) {
            $status = isset($settings->main->$prop) ? "[OK]" : "[MISSING]";
            $value = isset($settings->main->$prop) ? json_encode($settings->main->$prop) : 'NOT SET';
            echo "        {$status} main->{$prop}: {$value}\n";
        }
    } else {
        echo "    [ERROR] settings->main NOT FOUND\n";
    }

    // plan_free settings
    if (isset($settings->plan_free)) {
        echo "    [OK] settings->plan_free exists\n";
        if (isset($settings->plan_free->settings)) {
            echo "    [OK] settings->plan_free->settings exists\n";
            if (isset($settings->plan_free->settings->export)) {
                echo "    [OK] settings->plan_free->settings->export exists\n";
                echo "        export: " . json_encode($settings->plan_free->settings->export) . "\n";
            } else {
                echo "    [MISSING] settings->plan_free->settings->export\n";
            }
        } else {
            echo "    [MISSING] settings->plan_free->settings\n";
        }
    } else {
        echo "    [MISSING] settings->plan_free\n";
    }

    // content settings (critical for blog/pages)
    if (isset($settings->content)) {
        echo "    [OK] settings->content exists\n";
    } else {
        echo "    [MISSING] settings->content - THIS WILL BREAK BLOG/PAGES!\n";
    }

    // languages settings
    if (isset($settings->languages)) {
        echo "    [OK] settings->languages exists\n";
    } else {
        echo "    [MISSING] settings->languages\n";
    }

} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "    Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Step 14: Initialize Language
echo "\n[13] Initializing Language system...\n";
try {
    \Altum\Language::initialize();
    echo "    [OK] Language initialized\n";
    echo "    Available languages: " . implode(', ', array_keys(\Altum\Language::$languages)) . "\n";
    echo "    Active languages: " . implode(', ', array_keys(\Altum\Language::$active_languages)) . "\n";
} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "    Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Step 15: Check users in database
echo "\n[14] Checking users in database...\n";
try {
    $mysqli = new mysqli(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    $result = $mysqli->query("SELECT user_id, plan_settings, preferences FROM users LIMIT 5");
    echo "    Found " . $result->num_rows . " users\n";
    while ($row = $result->fetch_assoc()) {
        $plan_settings = json_decode($row['plan_settings']);
        $preferences = json_decode($row['preferences']);
        $has_export = isset($plan_settings->export);
        $has_pdf = isset($plan_settings->export->pdf);
        $has_prefs = $preferences !== null;
        echo "    User #{$row['user_id']}: export=" . ($has_export ? 'YES' : 'NO') .
             ", pdf=" . ($has_pdf ? 'YES' : 'NO') .
             ", preferences=" . ($has_prefs ? 'YES' : 'NO') . "\n";
    }
    $mysqli->close();
} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . "\n";
}

// Step 16: Test Router parsing
echo "\n[15] Testing Router...\n";
try {
    $_GET['altum'] = '';  // Simulate homepage
    \Altum\Router::parse_url();
    echo "    [OK] Router::parse_url() completed\n";
    echo "    Path: " . \Altum\Router::$path . "\n";
    echo "    Controller: " . \Altum\Router::$controller . "\n";
} catch (Throwable $e) {
    echo "    [ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "    Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
echo "\nIf all steps passed, try the full test with:\n";
echo "diagnose.php?key=widgethook_diagnose_2024&full=1\n";

// Full test if requested
if (isset($_GET['full'])) {
    echo "\n\n=== FULL APP TEST ===\n";
    echo "Attempting to create App instance...\n\n";

    // Start session
    session_set_cookie_params([
        'lifetime' => null,
        'path' => COOKIE_PATH,
        'samesite' => 'Lax',
        'secure' => str_starts_with(SITE_URL, 'https://'),
    ]);

    try {
        // This simulates what index.php does
        $_GET['altum'] = '';  // Simulate homepage request

        new \Altum\App();

        echo "\n[OK] App completed without fatal error\n";
    } catch (Throwable $e) {
        echo "\n[ERROR] " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    }
}

echo "\n\n*** DELETE THIS FILE (diagnose.php) WHEN DONE ***\n";
