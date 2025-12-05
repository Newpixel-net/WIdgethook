<?php
/**
 * Fix Plan Settings
 * Adds missing properties to plan_free and user plan_settings
 *
 * Access: /fix_plan_settings.php?key=widgethook_reset_2024&fix=1
 */

define('FIX_KEY', 'widgethook_reset_2024');

if (!isset($_GET['key']) || $_GET['key'] !== FIX_KEY) {
    http_response_code(403);
    die("Unauthorized. Usage: fix_plan_settings.php?key=widgethook_reset_2024&fix=1");
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
echo "=== FIX PLAN SETTINGS ===\n\n";

$dry_run = !isset($_GET['fix']);

if ($dry_run) {
    echo "*** DRY RUN MODE ***\n";
    echo "Add &fix=1 to URL to apply changes\n\n";
}

$issues_found = 0;
$issues_fixed = 0;

// Required plan_settings properties with defaults
$required_plan_settings = [
    'no_ads' => true,
    'removable_branding' => true,
    'custom_branding' => true,
    'api_is_enabled' => true,
    'affiliate_is_enabled' => false,
    'campaigns_limit' => -1,
    'notifications_limit' => -1,
    'notifications_impressions_limit' => -1,
    'notification_handlers_limit' => -1,
    'domains_limit' => -1,
    'teams_limit' => -1,
    'team_members_limit' => -1,
    'email_reports_is_enabled' => true,
    'track_notifications_retention' => -1,

    // Per-type notification handler limits (CRITICAL - these are dynamically accessed)
    'notification_handlers_email_limit' => -1,
    'notification_handlers_webhook_limit' => -1,
    'notification_handlers_slack_limit' => -1,
    'notification_handlers_discord_limit' => -1,
    'notification_handlers_telegram_limit' => -1,
    'notification_handlers_microsoft_teams_limit' => -1,
    'notification_handlers_twilio_limit' => -1,
    'notification_handlers_twilio_call_limit' => -1,
    'notification_handlers_whatsapp_limit' => -1,
    'notification_handlers_x_limit' => -1,
    'notification_handlers_google_chat_limit' => -1,
    'notification_handlers_push_subscriber_id_limit' => -1,
    'notification_handlers_internal_notification_limit' => -1,
    'active_notification_handlers_per_resource_limit' => -1,

    // Export permissions (CRITICAL - accessed by wrapper views)
    'export' => (object)[
        'csv' => true,
        'json' => true,
        'pdf' => true,
    ],

    'enabled_notifications' => [
        // Default notification types (from notifications.php)
        'INFORMATIONAL' => true,
        'COUPON' => true,
        'LIVE_COUNTER' => true,
        'EMAIL_COLLECTOR' => true,
        'CONVERSIONS' => true,           // Correct name (was LATEST_CONVERSION)
        'CONVERSIONS_COUNTER' => true,
        'VIDEO' => true,
        'AUDIO' => true,                 // Added - was missing
        'SOCIAL_SHARE' => true,
        'REVIEWS' => true,               // Correct name (was RANDOM_REVIEW)
        'EMOJI_FEEDBACK' => true,
        'COOKIE_NOTIFICATION' => true,
        'SCORE_FEEDBACK' => true,
        'REQUEST_COLLECTOR' => true,
        'COUNTDOWN_COLLECTOR' => true,
        'CUSTOM_HTML' => true,

        // Pro notification types (from pro-notifications plugin)
        'INFORMATIONAL_BAR' => true,
        'IMAGE' => true,
        'COLLECTOR_BAR' => true,
        'COUPON_BAR' => true,
        'BUTTON_BAR' => true,
        'COLLECTOR_MODAL' => true,
        'COLLECTOR_TWO_MODAL' => true,
        'BUTTON_MODAL' => true,
        'TEXT_FEEDBACK' => true,
        'ENGAGEMENT_LINKS' => true,

        // Additional pro types that may be needed
        'WHATSAPP_CHAT' => true,
        'CONTACT_US' => true,
        'INFORMATIONAL_MINI' => true,
        'INFORMATIONAL_BAR_MINI' => true,
    ]
];

// Required notifications settings properties
$required_notifications_settings = [
    'branding' => 'Powered by WidgetHook',
    'analytics_is_enabled' => true,
    'pixel_cache' => 0,
    'domains_is_enabled' => true,
    'blacklisted_domains' => [],
];

// ================================================
// PART 1: FIX plan_free SETTING
// ================================================
echo "=== PART 1: FIX plan_free SETTING ===\n\n";

$plan_free_result = database()->query("SELECT `value` FROM `settings` WHERE `key` = 'plan_free'");
if ($plan_free_result->num_rows > 0) {
    $plan_free = json_decode($plan_free_result->fetch_object()->value);
    
    if (!isset($plan_free->settings)) {
        $plan_free->settings = new stdClass();
    }
    
    $needs_update = false;
    $missing = [];
    
    foreach ($required_plan_settings as $key => $default) {
        if (!isset($plan_free->settings->$key)) {
            $needs_update = true;
            $missing[] = $key;
            $plan_free->settings->$key = $default;
        }
    }
    
    if ($needs_update) {
        $issues_found++;
        echo "[UPDATE] plan_free.settings missing: " . implode(', ', $missing);
        
        if (!$dry_run) {
            $value = json_encode($plan_free);
            $stmt = database()->prepare("UPDATE `settings` SET `value` = ? WHERE `key` = 'plan_free'");
            $stmt->bind_param('s', $value);
            if ($stmt->execute()) {
                echo " -> UPDATED!\n";
                $issues_fixed++;
            } else {
                echo " -> ERROR\n";
            }
        } else {
            echo "\n";
        }
    } else {
        echo "[OK] plan_free.settings has all required properties\n";
    }
} else {
    echo "[ERROR] plan_free setting not found!\n";
}

// ================================================
// PART 2: FIX plan_custom SETTING
// ================================================
echo "\n=== PART 2: FIX plan_custom SETTING ===\n\n";

$plan_custom_result = database()->query("SELECT `value` FROM `settings` WHERE `key` = 'plan_custom'");
if ($plan_custom_result->num_rows > 0) {
    $plan_custom = json_decode($plan_custom_result->fetch_object()->value);
    
    if (!isset($plan_custom->settings)) {
        $plan_custom->settings = new stdClass();
        $needs_update = true;
        $missing = array_keys($required_plan_settings);
    } else {
        $needs_update = false;
        $missing = [];
    }
    
    foreach ($required_plan_settings as $key => $default) {
        if (!isset($plan_custom->settings->$key)) {
            $needs_update = true;
            $missing[] = $key;
            $plan_custom->settings->$key = $default;
        }
    }
    
    if ($needs_update) {
        $issues_found++;
        echo "[UPDATE] plan_custom.settings missing: " . implode(', ', array_unique($missing));
        
        if (!$dry_run) {
            $value = json_encode($plan_custom);
            $stmt = database()->prepare("UPDATE `settings` SET `value` = ? WHERE `key` = 'plan_custom'");
            $stmt->bind_param('s', $value);
            if ($stmt->execute()) {
                echo " -> UPDATED!\n";
                $issues_fixed++;
            } else {
                echo " -> ERROR\n";
            }
        } else {
            echo "\n";
        }
    } else {
        echo "[OK] plan_custom.settings has all required properties\n";
    }
}

// ================================================
// PART 3: FIX notifications SETTING
// ================================================
echo "\n=== PART 3: FIX notifications SETTING ===\n\n";

$notif_result = database()->query("SELECT `value` FROM `settings` WHERE `key` = 'notifications'");
if ($notif_result->num_rows > 0) {
    $notif = json_decode($notif_result->fetch_object()->value);
    
    $needs_update = false;
    $missing = [];
    
    foreach ($required_notifications_settings as $key => $default) {
        if (!isset($notif->$key)) {
            $needs_update = true;
            $missing[] = $key;
            $notif->$key = $default;
        }
    }
    
    if ($needs_update) {
        $issues_found++;
        echo "[UPDATE] notifications missing: " . implode(', ', $missing);
        
        if (!$dry_run) {
            $value = json_encode($notif);
            $stmt = database()->prepare("UPDATE `settings` SET `value` = ? WHERE `key` = 'notifications'");
            $stmt->bind_param('s', $value);
            if ($stmt->execute()) {
                echo " -> UPDATED!\n";
                $issues_fixed++;
            } else {
                echo " -> ERROR\n";
            }
        } else {
            echo "\n";
        }
    } else {
        echo "[OK] notifications has all required properties\n";
    }
}

// ================================================
// PART 4: FIX ALL USERS' plan_settings
// ================================================
echo "\n=== PART 4: FIX ALL USERS' plan_settings ===\n\n";

$users_result = database()->query("SELECT `user_id`, `plan_settings` FROM `users`");
$users_updated = 0;

while ($user = $users_result->fetch_object()) {
    $plan_settings = json_decode($user->plan_settings ?? '{}');
    
    $needs_update = false;
    $missing = [];
    
    foreach ($required_plan_settings as $key => $default) {
        if (!isset($plan_settings->$key)) {
            $needs_update = true;
            $missing[] = $key;
            $plan_settings->$key = $default;
        }
    }
    
    if ($needs_update) {
        $issues_found++;
        echo "[UPDATE] User #{$user->user_id} missing: " . implode(', ', $missing);
        
        if (!$dry_run) {
            $value = json_encode($plan_settings);
            $stmt = database()->prepare("UPDATE `users` SET `plan_settings` = ? WHERE `user_id` = ?");
            $stmt->bind_param('si', $value, $user->user_id);
            if ($stmt->execute()) {
                echo " -> UPDATED!\n";
                $issues_fixed++;
                $users_updated++;
            } else {
                echo " -> ERROR\n";
            }
        } else {
            echo "\n";
        }
    } else {
        echo "[OK] User #{$user->user_id}\n";
    }
}

// ================================================
// PART 5: CLEAR CACHE
// ================================================
if (!$dry_run && $issues_fixed > 0) {
    echo "\n=== PART 5: CLEARING CACHE ===\n\n";
    
    try {
        cache()->deleteItem('settings');
        echo "[OK] Settings cache cleared\n";
    } catch (Exception $e) {
        echo "[WARN] Could not clear cache: " . $e->getMessage() . "\n";
    }
    
    // Clear file cache
    $cache_dir = __DIR__ . '/uploads/cache';
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '/*');
        $count = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
                $count++;
            }
        }
        echo "[OK] Cleared {$count} cache files\n";
    }
}

// ================================================
// SUMMARY
// ================================================
echo "\n=== SUMMARY ===\n";
echo "================\n";
echo "Issues found: {$issues_found}\n";

if (!$dry_run) {
    echo "Issues fixed: {$issues_fixed}\n";
    
    if ($issues_fixed > 0) {
        echo "\n*** IMPORTANT: Log out and log back in for changes to take effect! ***\n";
    }
} else {
    echo "\nTo apply all fixes, run:\n";
    echo "fix_plan_settings.php?key=widgethook_reset_2024&fix=1\n";
}

echo "\n=== DONE ===\n";
