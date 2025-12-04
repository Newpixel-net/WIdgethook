<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>Tracing Autoloader\n==================\n\n";

const DEBUG = 1;
const MYSQL_DEBUG = 0;
const LOGGING = 0;
const CACHE = 1;
const ALTUMCODE = 66;

define('ROOT_PATH', realpath(__DIR__) . '/');
const APP_PATH = ROOT_PATH . 'app/';
const PLUGINS_PATH = ROOT_PATH . 'plugins/';
const THEME_PATH = ROOT_PATH . 'themes/altum/';
const THEME_URL_PATH = 'themes/altum/';
const ASSETS_PATH = THEME_PATH . 'assets/';
const ASSETS_URL_PATH = THEME_URL_PATH . 'assets/';
const UPLOADS_PATH = ROOT_PATH . 'uploads/';
const UPLOADS_URL_PATH = 'uploads/';
const CACHE_DEFAULT_SECONDS = 2592000;

require_once APP_PATH . 'includes/debug.php';
require_once APP_PATH . 'includes/product.php';
require_once ROOT_PATH . 'config.php';

define('COOKIE_PATH', preg_replace('|https?://[^/]+|i', '', SITE_URL));

// Custom autoloader that traces everything
spl_autoload_register(function ($class) {
    echo "AUTOLOAD: '$class'\n";

    $namespace_prefix = 'Altum';
    $split = explode('\\', $class);

    echo "  Split: " . json_encode($split) . "\n";

    if($split[0] !== $namespace_prefix) {
        echo "  -> Not Altum namespace, skipping\n";
        return;
    }

    // Core check
    if(isset($split[1]) && !isset($split[2])) {
        $file = APP_PATH . 'core/' . $split[1] . '.php';
        echo "  -> Core file: $file\n";
        echo "  -> File exists: " . (file_exists($file) ? 'YES' : 'NO') . "\n";
        if (file_exists($file)) {
            require_once $file;
        } else {
            echo "  -> ERROR: File not found!\n";
            return;
        }
    }

    // Traits, Models, Helpers
    if(isset($split[1], $split[2]) && in_array($split[1], ['Traits', 'Models', 'Helpers'])) {
        $folder = mb_strtolower($split[1]);
        $file = APP_PATH . $folder . '/' . $split[2] . '.php';
        echo "  -> $split[1] file: $file\n";
        echo "  -> File exists: " . (file_exists($file) ? 'YES' : 'NO') . "\n";
        if (file_exists($file)) {
            require_once $file;
        } else {
            echo "  -> ERROR: File not found!\n";
        }
    }

    // Payment Gateways
    if(isset($split[1], $split[2]) && $split[1] == 'PaymentGateways') {
        $file = APP_PATH . 'helpers/payment-gateways/' . $split[2] . '.php';
        echo "  -> PaymentGateway file: $file\n";
        require_once $file;
    }

    echo "\n";
});

echo "Autoloader registered. Now loading Controller.php...\n\n";

try {
    require_once APP_PATH . 'core/Controller.php';
    echo "\nController.php loaded successfully!\n";
} catch (Throwable $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\nDone.</pre>";
