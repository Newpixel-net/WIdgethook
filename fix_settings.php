<?php
/**
 * Fix Missing Settings
 * Adds missing settings entries to the database
 *
 * Access: /fix_settings.php?key=widgethook_reset_2024&fix=1
 */

define('FIX_KEY', 'widgethook_reset_2024');

if (!isset($_GET['key']) || $_GET['key'] !== FIX_KEY) {
    http_response_code(403);
    die('Unauthorized. Usage: fix_settings.php?key=widgethook_reset_2024&fix=1');
}

ob_start();

const DEBUG = 1;
const MYSQL_DEBUG = 0;
const LOGGING = 0;
const CACHE = 1;
const ALTUMCODE = 66;

require_once __DIR__ . '/app/init.php';

ob_end_clean();

header('Content-Type: text/plain');
echo "=== Fix Missing Settings ===\n\n";

$dry_run = !isset($_GET['fix']);

if ($dry_run) {
    echo "DRY RUN MODE - Add &fix=1 to actually make changes\n\n";
}

// Required settings with default values
$required_settings = [
    'linkedin' => json_encode([
        'is_enabled' => false,
        'client_id' => '',
        'client_secret' => ''
    ]),
    'microsoft' => json_encode([
        'is_enabled' => false,
        'client_id' => '',
        'client_secret' => ''
    ]),
    'facebook' => json_encode([
        'is_enabled' => false,
        'app_id' => '',
        'app_secret' => ''
    ]),
    'google' => json_encode([
        'is_enabled' => false,
        'client_id' => '',
        'client_secret' => ''
    ]),
    'twitter' => json_encode([
        'is_enabled' => false,
        'consumer_api_key' => '',
        'consumer_api_secret' => ''
    ]),
    'discord' => json_encode([
        'is_enabled' => false,
        'client_id' => '',
        'client_secret' => ''
    ]),
    'captcha' => json_encode([
        'type' => 'basic',
        'login_is_enabled' => false,
        'register_is_enabled' => false,
        'lost_password_is_enabled' => false,
        'resend_activation_is_enabled' => false,
        'recaptcha_public_key' => '',
        'recaptcha_private_key' => '',
        'hcaptcha_site_key' => '',
        'hcaptcha_secret_key' => '',
        'turnstile_site_key' => '',
        'turnstile_secret_key' => ''
    ]),
    'users' => json_encode([
        'register_is_enabled' => true,
        'email_confirmation' => false,
        'welcome_email_is_enabled' => false,
        'login_rememberme_checkbox_is_checked' => true,
        'login_rememberme_cookie_days' => 30,
        'login_lockout_is_enabled' => false,
        'login_lockout_max_retries' => 5,
        'login_lockout_time' => 15,
        'blacklisted_domains' => [],
        'blacklisted_countries' => [],
        'blacklisted_ips' => [],
        'register_social_login_require_password' => false
    ]),
    'email_notifications' => json_encode([
        'new_user' => false,
        'new_payment' => false,
        'emails' => ''
    ]),
    'webhooks' => json_encode([
        'user_new' => '',
        'user_delete' => ''
    ]),
    'internal_notifications' => json_encode([
        'users_is_enabled' => true,
        'admins_is_enabled' => true,
        'new_user' => false,
        'new_payment' => false
    ]),
    'offload' => json_encode([
        'assets_url' => '',
        'uploads_url' => '',
        'cdn_assets_url' => '',
        'cdn_uploads_url' => ''
    ])
];

$issues_found = 0;
$issues_fixed = 0;

// Get existing settings
$existing_keys = [];
$result = database()->query("SELECT `key` FROM `settings`");
while ($row = $result->fetch_object()) {
    $existing_keys[] = $row->key;
}

echo "Existing settings keys: " . implode(', ', $existing_keys) . "\n\n";

foreach ($required_settings as $key => $value) {
    echo "Checking setting: {$key}\n";

    if (in_array($key, $existing_keys)) {
        echo "   EXISTS - OK\n\n";
    } else {
        $issues_found++;
        echo "   MISSING";

        if (!$dry_run) {
            $stmt = database()->prepare("INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)");
            $stmt->bind_param('ss', $key, $value);
            $result = $stmt->execute();

            if ($result) {
                echo " -> ADDED!\n\n";
                $issues_fixed++;
            } else {
                echo " -> ERROR: " . database()->error . "\n\n";
            }
        } else {
            echo "\n\n";
        }
    }
}

// Clear settings cache
if (!$dry_run && $issues_fixed > 0) {
    echo "Clearing settings cache...\n";
    try {
        $cache_instance = cache()->getItem('settings');
        cache()->deleteItem('settings');
        echo "   Cache cleared!\n\n";
    } catch (Exception $e) {
        echo "   Cache clear error: " . $e->getMessage() . "\n\n";
    }
}

echo "=== Summary ===\n";
echo "Issues found: {$issues_found}\n";
if (!$dry_run) {
    echo "Issues fixed: {$issues_fixed}\n";
}

if ($dry_run && $issues_found > 0) {
    echo "\nTo fix these issues, run:\n";
    echo "fix_settings.php?key=widgethook_reset_2024&fix=1\n";
}

echo "\n=== Done ===\n";
