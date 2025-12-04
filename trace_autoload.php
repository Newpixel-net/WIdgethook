<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>Full Init Trace\n===============\n\n";

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

    if($split[0] !== $namespace_prefix) {
        return;
    }

    // Core check
    if(isset($split[1]) && !isset($split[2])) {
        $file = APP_PATH . 'core/' . $split[1] . '.php';
        echo "  -> Core: $file (" . (file_exists($file) ? 'OK' : 'MISSING') . ")\n";
        if (file_exists($file)) {
            require_once $file;
        }
    }

    // Traits, Models, Helpers
    if(isset($split[1], $split[2]) && in_array($split[1], ['Traits', 'Models', 'Helpers'])) {
        $folder = mb_strtolower($split[1]);
        $file = APP_PATH . $folder . '/' . $split[2] . '.php';
        echo "  -> $split[1]: $file (" . (file_exists($file) ? 'OK' : 'MISSING') . ")\n";
        if (file_exists($file)) {
            require_once $file;
        }
    }

    // Payment Gateways
    if(isset($split[1], $split[2]) && $split[1] == 'PaymentGateways') {
        $file = APP_PATH . 'helpers/payment-gateways/' . $split[2] . '.php';
        echo "  -> PaymentGateway: $file\n";
        require_once $file;
    }
});

echo "Loading Controller.php...\n";
require_once APP_PATH . 'core/Controller.php';
echo "OK\n\n";

echo "Loading Model.php...\n";
require_once APP_PATH . 'core/Model.php';
echo "OK\n\n";

echo "Loading helpers...\n";
require_once APP_PATH . 'helpers/links.php';
require_once APP_PATH . 'helpers/strings.php';
require_once APP_PATH . 'helpers/email.php';
require_once APP_PATH . 'helpers/others.php';
require_once APP_PATH . 'helpers/core.php';
require_once APP_PATH . 'helpers/sessions.php';
require_once APP_PATH . 'helpers/socialproofo.php';
echo "OK\n\n";

echo "Loading vendor autoload...\n";
require_once ROOT_PATH . 'vendor/autoload.php';
echo "OK\n\n";

echo "Starting session...\n";
session_set_cookie_params([
    'lifetime' => null,
    'path' => COOKIE_PATH,
    'samesite' => 'Lax',
    'secure' => str_starts_with(SITE_URL, 'https://'),
]);
session_start();
echo "OK\n\n";

echo "Initializing Database...\n";
\Altum\Database::initialize();
echo "OK\n\n";

echo "Initializing Settings...\n";
\Altum\Settings::initialize();
echo "OK\n\n";

echo "Initializing Language...\n";
\Altum\Language::initialize();
echo "OK\n\n";

echo "\n=== ALL INIT COMPLETE ===\n\n";

echo "Testing settings:\n";
echo "main->chart_days: " . (settings()->main->chart_days ?? 'NOT SET') . "\n";
echo "main->chart_cache: " . (settings()->main->chart_cache ?? 'NOT SET') . "\n";

if (isset($_SESSION['user_id'])) {
    echo "\nUser logged in: " . $_SESSION['user_id'] . "\n";
} else {
    echo "\nNot logged in\n";
}

echo "\nDONE</pre>";
