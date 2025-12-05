<?php
/**
 * COMPREHENSIVE FIX SCRIPT
 * Fixes ALL missing tables, columns, and settings for WIdgethook
 *
 * Access: /fix_all.php?key=widgethook_reset_2024&fix=1
 */

define('FIX_KEY', 'widgethook_reset_2024');

if (!isset($_GET['key']) || $_GET['key'] !== FIX_KEY) {
    http_response_code(403);
    die("Unauthorized. Usage: fix_all.php?key=widgethook_reset_2024&fix=1\n\nAdd &fix=1 to apply changes.");
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
echo "=== COMPREHENSIVE FIX SCRIPT ===\n";
echo "=================================\n\n";

$dry_run = !isset($_GET['fix']);

if ($dry_run) {
    echo "*** DRY RUN MODE ***\n";
    echo "Add &fix=1 to URL to apply all changes\n\n";
}

$issues_found = 0;
$issues_fixed = 0;

// ================================================
// PART 1: MISSING TABLES
// ================================================
echo "=== PART 1: MISSING TABLES ===\n\n";

$tables = [
    'notification_handlers' => "CREATE TABLE IF NOT EXISTS `notification_handlers` (
        `notification_handler_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `type` varchar(32) DEFAULT NULL,
        `name` varchar(128) DEFAULT NULL,
        `settings` text DEFAULT NULL,
        `is_enabled` tinyint(4) DEFAULT 1,
        `last_datetime` datetime DEFAULT NULL,
        `datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`notification_handler_id`),
        KEY `user_id` (`user_id`),
        KEY `type` (`type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'push_subscribers' => "CREATE TABLE IF NOT EXISTS `push_subscribers` (
        `push_subscriber_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `endpoint` text DEFAULT NULL,
        `keys` text DEFAULT NULL,
        `ip` varchar(64) DEFAULT NULL,
        `city_name` varchar(128) DEFAULT NULL,
        `country_code` varchar(8) DEFAULT NULL,
        `continent_code` varchar(8) DEFAULT NULL,
        `os_name` varchar(64) DEFAULT NULL,
        `browser_name` varchar(64) DEFAULT NULL,
        `browser_language` varchar(32) DEFAULT NULL,
        `device_type` varchar(32) DEFAULT NULL,
        `datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`push_subscriber_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'internal_notifications' => "CREATE TABLE IF NOT EXISTS `internal_notifications` (
        `internal_notification_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `for_who` varchar(16) DEFAULT NULL,
        `from_who` varchar(16) DEFAULT NULL,
        `icon` varchar(64) DEFAULT NULL,
        `title` varchar(128) DEFAULT NULL,
        `description` varchar(1024) DEFAULT NULL,
        `url` varchar(512) DEFAULT NULL,
        `is_read` tinyint(4) DEFAULT 0,
        `read_datetime` datetime DEFAULT NULL,
        `datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`internal_notification_id`),
        KEY `user_id` (`user_id`),
        KEY `for_who` (`for_who`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'domains' => "CREATE TABLE IF NOT EXISTS `domains` (
        `domain_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `scheme` varchar(8) NOT NULL DEFAULT 'https',
        `host` varchar(256) NOT NULL DEFAULT '',
        `custom_index_url` varchar(256) DEFAULT NULL,
        `custom_not_found_url` varchar(256) DEFAULT NULL,
        `is_enabled` tinyint(4) DEFAULT 1,
        `datetime` datetime DEFAULT NULL,
        `last_datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`domain_id`),
        KEY `user_id` (`user_id`),
        KEY `host` (`host`(191)),
        KEY `is_enabled` (`is_enabled`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'users_logs' => "CREATE TABLE IF NOT EXISTS `users_logs` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `type` varchar(64) DEFAULT NULL,
        `ip` varchar(64) DEFAULT NULL,
        `device_type` varchar(16) DEFAULT NULL,
        `os_name` varchar(16) DEFAULT NULL,
        `country_code` varchar(8) DEFAULT NULL,
        `datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `ip` (`ip`),
        KEY `type` (`type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'plans' => "CREATE TABLE IF NOT EXISTS `plans` (
        `plan_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` varchar(256) NOT NULL DEFAULT '',
        `description` varchar(256) NOT NULL DEFAULT '',
        `monthly_price` float DEFAULT NULL,
        `annual_price` float DEFAULT NULL,
        `lifetime_price` float DEFAULT NULL,
        `trial_days` int(11) UNSIGNED DEFAULT 0,
        `settings` text DEFAULT NULL,
        `taxes_ids` text DEFAULT NULL,
        `codes_ids` text DEFAULT NULL,
        `color` varchar(16) DEFAULT NULL,
        `status` tinyint(4) NOT NULL DEFAULT 0,
        `order` int(10) UNSIGNED DEFAULT 0,
        `datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`plan_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'redeemed_codes' => "CREATE TABLE IF NOT EXISTS `redeemed_codes` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `code_id` int(11) DEFAULT NULL,
        `user_id` int(11) DEFAULT NULL,
        `datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `code_id` (`code_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'codes' => "CREATE TABLE IF NOT EXISTS `codes` (
        `code_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` varchar(64) NOT NULL DEFAULT '',
        `type` varchar(16) NOT NULL DEFAULT '',
        `days` int(11) UNSIGNED DEFAULT NULL,
        `code` varchar(32) NOT NULL DEFAULT '',
        `discount` float UNSIGNED NOT NULL DEFAULT 0,
        `quantity` int(11) UNSIGNED NOT NULL DEFAULT 0,
        `redeemed` int(11) UNSIGNED NOT NULL DEFAULT 0,
        `plans_ids` text DEFAULT NULL,
        `datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`code_id`),
        KEY `code` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'taxes' => "CREATE TABLE IF NOT EXISTS `taxes` (
        `tax_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` varchar(64) NOT NULL DEFAULT '',
        `description` varchar(256) DEFAULT '',
        `value` float NOT NULL DEFAULT 0,
        `value_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
        `type` enum('inclusive','exclusive') NOT NULL DEFAULT 'inclusive',
        `billing_type` enum('personal','business','both') NOT NULL DEFAULT 'both',
        `countries` text DEFAULT NULL,
        `datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`tax_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // ========================================
    // ADMIN FEATURE TABLES
    // ========================================

    'broadcasts' => "CREATE TABLE IF NOT EXISTS `broadcasts` (
        `broadcast_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` varchar(64) NOT NULL DEFAULT '',
        `subject` varchar(128) NOT NULL DEFAULT '',
        `content` text DEFAULT NULL,
        `segment` varchar(64) NOT NULL DEFAULT 'all',
        `settings` text DEFAULT NULL,
        `users_ids` text DEFAULT NULL,
        `sent_users_ids` text DEFAULT NULL,
        `sent_emails` int(11) UNSIGNED NOT NULL DEFAULT 0,
        `total_emails` int(11) UNSIGNED NOT NULL DEFAULT 0,
        `views` int(11) UNSIGNED NOT NULL DEFAULT 0,
        `clicks` int(11) UNSIGNED NOT NULL DEFAULT 0,
        `status` varchar(16) NOT NULL DEFAULT 'draft',
        `last_sent_email_datetime` datetime DEFAULT NULL,
        `datetime` datetime DEFAULT NULL,
        `last_datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`broadcast_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'blog_posts_categories' => "CREATE TABLE IF NOT EXISTS `blog_posts_categories` (
        `blog_posts_category_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `url` varchar(256) NOT NULL DEFAULT '',
        `title` varchar(256) NOT NULL DEFAULT '',
        `description` varchar(256) DEFAULT '',
        `language` varchar(32) DEFAULT NULL,
        `order` int(11) NOT NULL DEFAULT 0,
        `datetime` datetime DEFAULT NULL,
        `last_datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`blog_posts_category_id`),
        KEY `url` (`url`(191))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'blog_posts' => "CREATE TABLE IF NOT EXISTS `blog_posts` (
        `blog_post_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `blog_posts_category_id` bigint(20) UNSIGNED DEFAULT NULL,
        `url` varchar(256) NOT NULL DEFAULT '',
        `title` varchar(256) NOT NULL DEFAULT '',
        `description` varchar(512) DEFAULT '',
        `keywords` varchar(256) DEFAULT '',
        `image` varchar(40) DEFAULT NULL,
        `image_description` varchar(256) DEFAULT NULL,
        `editor` varchar(16) DEFAULT 'blocks',
        `content` longtext DEFAULT NULL,
        `language` varchar(32) DEFAULT NULL,
        `total_views` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
        `total_ratings` int(11) UNSIGNED NOT NULL DEFAULT 0,
        `average_rating` float DEFAULT 0,
        `is_published` tinyint(4) NOT NULL DEFAULT 0,
        `datetime` datetime DEFAULT NULL,
        `last_datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`blog_post_id`),
        KEY `blog_posts_category_id` (`blog_posts_category_id`),
        KEY `url` (`url`(191)),
        KEY `is_published` (`is_published`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'blog_posts_ratings' => "CREATE TABLE IF NOT EXISTS `blog_posts_ratings` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `blog_post_id` bigint(20) UNSIGNED NOT NULL,
        `ip_binary` varbinary(16) DEFAULT NULL,
        `rating` tinyint(4) NOT NULL,
        `datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `blog_post_id` (`blog_post_id`),
        KEY `ip_binary` (`ip_binary`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // ========================================
    // TEAMS FEATURE TABLES
    // ========================================

    'teams' => "CREATE TABLE IF NOT EXISTS `teams` (
        `team_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `name` varchar(64) NOT NULL DEFAULT '',
        `datetime` datetime DEFAULT NULL,
        `last_datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`team_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'teams_members' => "CREATE TABLE IF NOT EXISTS `teams_members` (
        `team_member_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `team_id` int(11) UNSIGNED NOT NULL,
        `user_id` int(11) DEFAULT NULL,
        `user_email` varchar(320) NOT NULL DEFAULT '',
        `access` text DEFAULT NULL,
        `status` tinyint(4) NOT NULL DEFAULT 0,
        `datetime` datetime DEFAULT NULL,
        `last_datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`team_member_id`),
        KEY `team_id` (`team_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($tables as $table_name => $create_sql) {
    $check = database()->query("SHOW TABLES LIKE '{$table_name}'");
    
    if ($check->num_rows > 0) {
        echo "[OK] Table '{$table_name}' exists\n";
    } else {
        $issues_found++;
        echo "[MISSING] Table '{$table_name}'";
        
        if (!$dry_run) {
            $result = database()->query($create_sql);
            if ($result) {
                echo " -> CREATED!\n";
                $issues_fixed++;
            } else {
                echo " -> ERROR: " . database()->error . "\n";
            }
        } else {
            echo "\n";
        }
    }
}

// ================================================
// PART 2: MISSING COLUMNS
// ================================================
echo "\n=== PART 2: MISSING COLUMNS ===\n\n";

$columns = [
    // campaigns table
    ['campaigns', 'domain_id', "ALTER TABLE `campaigns` ADD COLUMN `domain_id` int(11) DEFAULT NULL"],
    ['campaigns', 'email_reports', "ALTER TABLE `campaigns` ADD COLUMN `email_reports` text DEFAULT NULL"],
    ['campaigns', 'email_reports_is_enabled', "ALTER TABLE `campaigns` ADD COLUMN `email_reports_is_enabled` tinyint(4) DEFAULT 0"],
    ['campaigns', 'email_reports_last_datetime', "ALTER TABLE `campaigns` ADD COLUMN `email_reports_last_datetime` datetime DEFAULT NULL"],
    ['campaigns', 'email_reports_count', "ALTER TABLE `campaigns` ADD COLUMN `email_reports_count` int(11) DEFAULT 0"],
    
    // users table
    ['users', 'anti_phishing_code', "ALTER TABLE `users` ADD COLUMN `anti_phishing_code` varchar(8) DEFAULT NULL"],
    ['users', 'has_pending_internal_notifications', "ALTER TABLE `users` ADD COLUMN `has_pending_internal_notifications` tinyint(4) DEFAULT 0"],
    ['users', 'avatar', "ALTER TABLE `users` ADD COLUMN `avatar` varchar(40) DEFAULT NULL"],
    ['users', 'is_newsletter_subscribed', "ALTER TABLE `users` ADD COLUMN `is_newsletter_subscribed` tinyint(4) DEFAULT 0"],
    ['users', 'source', "ALTER TABLE `users` ADD COLUMN `source` varchar(32) DEFAULT 'direct'"],
    ['users', 'continent_code', "ALTER TABLE `users` ADD COLUMN `continent_code` varchar(8) DEFAULT NULL"],
    ['users', 'city_name', "ALTER TABLE `users` ADD COLUMN `city_name` varchar(128) DEFAULT NULL"],
    ['users', 'device_type', "ALTER TABLE `users` ADD COLUMN `device_type` varchar(16) DEFAULT NULL"],
    ['users', 'os_name', "ALTER TABLE `users` ADD COLUMN `os_name` varchar(16) DEFAULT NULL"],
    ['users', 'browser_name', "ALTER TABLE `users` ADD COLUMN `browser_name` varchar(32) DEFAULT NULL"],
    ['users', 'browser_language', "ALTER TABLE `users` ADD COLUMN `browser_language` varchar(32) DEFAULT NULL"],
    ['users', 'extra', "ALTER TABLE `users` ADD COLUMN `extra` text DEFAULT NULL"],
    ['users', 'preferences', "ALTER TABLE `users` ADD COLUMN `preferences` text DEFAULT NULL"],
    ['users', 'currency', "ALTER TABLE `users` ADD COLUMN `currency` varchar(4) DEFAULT 'USD'"],
    
    // payments table
    ['payments', 'payer_id', "ALTER TABLE `payments` ADD COLUMN `payer_id` varchar(64) DEFAULT NULL"],
    ['payments', 'subscription_id', "ALTER TABLE `payments` ADD COLUMN `subscription_id` varchar(64) DEFAULT NULL"],
    
    // track_notifications table
    ['track_notifications', 'user_id', "ALTER TABLE `track_notifications` ADD COLUMN `user_id` int(11) DEFAULT NULL"],
];

foreach ($columns as [$table, $column, $alter_sql]) {
    // Check if table exists first
    $table_check = database()->query("SHOW TABLES LIKE '{$table}'");
    if ($table_check->num_rows == 0) {
        echo "[SKIP] {$table}.{$column} - table doesn't exist\n";
        continue;
    }
    
    $col_check = database()->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    
    if ($col_check->num_rows > 0) {
        echo "[OK] {$table}.{$column}\n";
    } else {
        $issues_found++;
        echo "[MISSING] {$table}.{$column}";
        
        if (!$dry_run) {
            $result = @database()->query($alter_sql);
            if ($result) {
                echo " -> ADDED!\n";
                $issues_fixed++;
            } else {
                echo " -> ERROR: " . database()->error . "\n";
            }
        } else {
            echo "\n";
        }
    }
}

// ================================================
// PART 3: MISSING/INCOMPLETE SETTINGS
// ================================================
echo "\n=== PART 3: MISSING SETTINGS ===\n\n";

$required_settings = [
    'notification_handlers' => [
        'email_is_enabled' => true,
        'webhook_is_enabled' => true,
        'slack_is_enabled' => false,
        'discord_is_enabled' => false,
        'telegram_is_enabled' => false,
        'microsoft_teams_is_enabled' => false,
        'twilio_is_enabled' => false,
        'twilio_call_is_enabled' => false,
        'whatsapp_is_enabled' => false,
        'x_is_enabled' => false,
        'google_chat_is_enabled' => false,
        'push_subscriber_id_is_enabled' => false,
        'internal_notification_is_enabled' => true,
        'twilio_sid' => '',
        'twilio_token' => '',
        'twilio_number' => '',
        'whatsapp_number_id' => '',
        'whatsapp_access_token' => ''
    ],
    'linkedin' => [
        'is_enabled' => false,
        'client_id' => '',
        'client_secret' => ''
    ],
    'microsoft' => [
        'is_enabled' => false,
        'client_id' => '',
        'client_secret' => ''
    ],
    'offload' => [
        'assets_url' => '',
        'uploads_url' => '',
        'cdn_assets_url' => '',
        'cdn_uploads_url' => ''
    ],
];

// Settings that need specific properties added/updated
$settings_updates = [
    'notifications' => [
        'domains_is_enabled' => true,
    ],
    'internal_notifications' => [
        'new_newsletter_subscriber' => false,
        'new_domain' => false,
    ],
    'webhooks' => [
        'domain_new' => '',
        'domain_update' => '',
        'domain_delete' => '',
    ],
    'email_notifications' => [
        'new_domain' => false,
    ],
];

// Check existing settings
$existing_settings = [];
$result = database()->query("SELECT `key`, `value` FROM `settings`");
while ($row = $result->fetch_object()) {
    $existing_settings[$row->key] = json_decode($row->value);
}

// Add completely missing settings
foreach ($required_settings as $key => $default_value) {
    if (!isset($existing_settings[$key])) {
        $issues_found++;
        echo "[MISSING] settings.{$key}";
        
        if (!$dry_run) {
            $value = json_encode($default_value);
            $stmt = database()->prepare("INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)");
            $stmt->bind_param('ss', $key, $value);
            if ($stmt->execute()) {
                echo " -> ADDED!\n";
                $issues_fixed++;
            } else {
                echo " -> ERROR: " . database()->error . "\n";
            }
        } else {
            echo "\n";
        }
    } else {
        echo "[OK] settings.{$key}\n";
    }
}

echo "\n=== PART 4: UPDATING INCOMPLETE SETTINGS ===\n\n";

// Update existing settings with missing properties
foreach ($settings_updates as $key => $properties) {
    if (isset($existing_settings[$key])) {
        $current = $existing_settings[$key];
        $needs_update = false;
        $missing_props = [];
        
        foreach ($properties as $prop => $default) {
            if (!isset($current->$prop)) {
                $needs_update = true;
                $missing_props[] = $prop;
                $current->$prop = $default;
            }
        }
        
        if ($needs_update) {
            $issues_found++;
            echo "[UPDATE] settings.{$key} missing: " . implode(', ', $missing_props);
            
            if (!$dry_run) {
                $value = json_encode($current);
                $stmt = database()->prepare("UPDATE `settings` SET `value` = ? WHERE `key` = ?");
                $stmt->bind_param('ss', $value, $key);
                if ($stmt->execute()) {
                    echo " -> UPDATED!\n";
                    $issues_fixed++;
                } else {
                    echo " -> ERROR: " . database()->error . "\n";
                }
            } else {
                echo "\n";
            }
        } else {
            echo "[OK] settings.{$key} - all properties present\n";
        }
    } else {
        echo "[SKIP] settings.{$key} - will be created with defaults\n";
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
        echo "[WARN] Could not clear settings cache: " . $e->getMessage() . "\n";
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
        echo "\n*** IMPORTANT: Clear your browser cache and reload the page! ***\n";
    }
} else {
    echo "\nTo apply all fixes, run:\n";
    echo "fix_all.php?key=widgethook_reset_2024&fix=1\n";
}

echo "\n=== DONE ===\n";
