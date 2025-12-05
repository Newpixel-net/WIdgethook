<?php
/**
 * Debug Login Page
 */
ob_start();

const DEBUG = 1;
const MYSQL_DEBUG = 0;
const LOGGING = 0;
const CACHE = 1;
const ALTUMCODE = 66;

$errors = [];
set_error_handler(function($severity, $message, $file, $line) use (&$errors) {
    $errors[] = "[$severity] $message in $file:$line";
    return false;
});

register_shutdown_function(function() use (&$errors) {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: text/plain');
        echo "=== FATAL ERROR ===\n\n";
        echo "Type: " . $error['type'] . "\n";
        echo "Message: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";

        if (!empty($GLOBALS['errors'])) {
            echo "\n=== Previous Errors ===\n";
            foreach ($GLOBALS['errors'] as $e) {
                echo "  $e\n";
            }
        }
    }
});
$GLOBALS['errors'] = &$errors;

require_once __DIR__ . '/app/init.php';

ob_end_clean();

header('Content-Type: text/plain');
echo "=== Login Page Debug ===\n\n";

// Check settings
echo "1. Settings check:\n";
$settings_to_check = [
    'users' => ['login_rememberme_checkbox_is_checked', 'login_lockout_is_enabled', 'login_lockout_time', 'login_lockout_max_retries', 'welcome_email_is_enabled', 'blacklisted_domains', 'blacklisted_countries', 'blacklisted_ips'],
    'captcha' => ['login_is_enabled'],
    'facebook' => ['is_enabled'],
    'google' => ['is_enabled'],
    'twitter' => ['is_enabled'],
    'discord' => ['is_enabled'],
    'linkedin' => ['is_enabled'],
    'microsoft' => ['is_enabled'],
];

foreach ($settings_to_check as $category => $keys) {
    echo "   settings()->{$category}:\n";
    try {
        $cat = settings()->{$category};
        if ($cat) {
            foreach ($keys as $key) {
                $val = $cat->{$key} ?? 'NOT SET';
                echo "      {$key}: " . (is_bool($val) ? ($val ? 'true' : 'false') : (is_array($val) ? json_encode($val) : $val)) . "\n";
            }
        } else {
            echo "      CATEGORY IS NULL!\n";
        }
    } catch (Throwable $e) {
        echo "      ERROR: " . $e->getMessage() . "\n";
    }
}

// Check Captcha class
echo "\n2. Captcha class check:\n";
try {
    $captcha = new \Altum\Captcha();
    echo "   Captcha created: OK\n";
} catch (Throwable $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Check view file
echo "\n3. View file check:\n";
$view_path = THEME_PATH . 'views/login/index.php';
echo "   Path: {$view_path}\n";
echo "   Exists: " . (file_exists($view_path) ? "YES" : "NO") . "\n";

// Check wrapper view
echo "\n4. Wrapper view check:\n";
$wrapper_path = THEME_PATH . 'views/wrapper.php';
echo "   Path: {$wrapper_path}\n";
echo "   Exists: " . (file_exists($wrapper_path) ? "YES" : "NO") . "\n";

// Check users_logs table (used for lockout)
echo "\n5. users_logs table check:\n";
$table_check = database()->query("SHOW TABLES LIKE 'users_logs'");
if ($table_check->num_rows > 0) {
    echo "   Table exists: YES\n";
    $cols = database()->query("SHOW COLUMNS FROM `users_logs`");
    $col_names = [];
    while ($col = $cols->fetch_object()) {
        $col_names[] = $col->Field;
    }
    echo "   Columns: " . implode(', ', $col_names) . "\n";
} else {
    echo "   Table exists: NO\n";
}

// Check internal_notifications table
echo "\n6. internal_notifications table check:\n";
$int_notif_check = database()->query("SHOW TABLES LIKE 'internal_notifications'");
if ($int_notif_check->num_rows > 0) {
    echo "   Table exists: YES\n";
} else {
    echo "   Table exists: NO - This may cause issues\n";
}

// Check if any errors occurred
if (!empty($errors)) {
    echo "\n=== Errors During Execution ===\n";
    foreach ($errors as $err) {
        echo "  {$err}\n";
    }
}

echo "\n=== Done ===\n";
