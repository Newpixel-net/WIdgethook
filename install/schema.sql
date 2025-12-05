-- WidgetHook Database Schema
-- Clean installation schema (no license required)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Table structure for table `campaigns`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `campaigns`;
CREATE TABLE IF NOT EXISTS `campaigns` (
  `campaign_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `domain_id` int(11) DEFAULT NULL,
  `pixel_key` varchar(32) DEFAULT NULL,
  `name` varchar(256) NOT NULL DEFAULT '',
  `domain` varchar(256) NOT NULL DEFAULT '',
  `include_subdomains` int(11) DEFAULT 0,
  `branding` text DEFAULT NULL,
  `email_reports` text DEFAULT NULL,
  `email_reports_is_enabled` tinyint(4) DEFAULT 0,
  `email_reports_last_datetime` datetime DEFAULT NULL,
  `email_reports_count` int(11) DEFAULT 0,
  `is_enabled` tinyint(4) NOT NULL DEFAULT 1,
  `last_datetime` datetime DEFAULT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`campaign_id`),
  KEY `user_id` (`user_id`),
  KEY `domain_id` (`domain_id`),
  KEY `campaigns_domain_index` (`domain`),
  KEY `campaigns_pixel_key_index` (`pixel_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------
-- Table structure for table `codes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `codes`;
CREATE TABLE IF NOT EXISTS `codes` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `domains`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `domains`;
CREATE TABLE IF NOT EXISTS `domains` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `internal_notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `internal_notifications`;
CREATE TABLE IF NOT EXISTS `internal_notifications` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `notification_handlers`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notification_handlers`;
CREATE TABLE IF NOT EXISTS `notification_handlers` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(256) NOT NULL DEFAULT '',
  `type` varchar(64) NOT NULL DEFAULT '',
  `settings` text NOT NULL,
  `last_action_date` datetime DEFAULT NULL COMMENT 'action ex: conversion',
  `notification_key` varchar(32) NOT NULL DEFAULT '' COMMENT 'Used for identifying webhooks',
  `is_enabled` tinyint(4) NOT NULL DEFAULT 0,
  `last_datetime` datetime DEFAULT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `campaign_id` (`campaign_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `pages`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pages`;
CREATE TABLE IF NOT EXISTS `pages` (
  `page_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pages_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `url` varchar(128) NOT NULL,
  `title` varchar(64) NOT NULL DEFAULT '',
  `description` varchar(128) DEFAULT NULL,
  `editor` varchar(16) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `type` varchar(16) DEFAULT '',
  `position` varchar(16) NOT NULL DEFAULT '',
  `open_in_new_tab` tinyint(4) DEFAULT 1,
  `order` int(11) DEFAULT 0,
  `total_views` int(11) DEFAULT 0,
  `datetime` datetime DEFAULT NULL,
  `last_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`page_id`),
  KEY `pages_pages_category_id_index` (`pages_category_id`),
  KEY `pages_url_index` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `pages_categories`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pages_categories`;
CREATE TABLE IF NOT EXISTS `pages_categories` (
  `pages_category_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `url` varchar(256) NOT NULL DEFAULT '',
  `title` varchar(64) NOT NULL DEFAULT '',
  `description` varchar(128) DEFAULT '',
  `icon` varchar(32) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`pages_category_id`),
  KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------
-- Table structure for table `payments`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `payer_id` varchar(64) DEFAULT NULL,
  `subscription_id` varchar(64) DEFAULT NULL,
  `processor` varchar(16) NOT NULL,
  `type` varchar(16) NOT NULL,
  `frequency` varchar(16) DEFAULT NULL,
  `code` varchar(32) DEFAULT NULL,
  `discount_amount` float DEFAULT 0,
  `base_amount` float NOT NULL,
  `email` varchar(256) DEFAULT NULL,
  `name` varchar(256) DEFAULT NULL,
  `billing` text DEFAULT NULL,
  `taxes_ids` text DEFAULT NULL,
  `total_amount` float NOT NULL,
  `currency` varchar(4) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `payments_payment_id_processor` (`processor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `plans`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `plans`;
CREATE TABLE IF NOT EXISTS `plans` (
  `plan_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL DEFAULT '',
  `description` varchar(256) NOT NULL DEFAULT '',
  `monthly_price` float DEFAULT NULL,
  `annual_price` float DEFAULT NULL,
  `lifetime_price` float DEFAULT NULL,
  `trial_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `settings` text NOT NULL,
  `taxes_ids` text DEFAULT NULL,
  `codes_ids` text DEFAULT NULL,
  `color` varchar(16) DEFAULT NULL,
  `status` tinyint(4) NOT NULL,
  `order` int(10) UNSIGNED DEFAULT 0,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `push_subscribers`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `push_subscribers`;
CREATE TABLE IF NOT EXISTS `push_subscribers` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `redeemed_codes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `redeemed_codes`;
CREATE TABLE IF NOT EXISTS `redeemed_codes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `code_id` (`code_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(64) NOT NULL DEFAULT '',
  `value` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `settings`
-- --------------------------------------------------------

INSERT INTO `settings` (`key`, `value`) VALUES
('main', '{"title":"WidgetHook","default_language":"english","default_theme_style":"light","default_timezone":"UTC","index_url":"","terms_and_conditions_url":"","privacy_policy_url":"","not_found_url":"","se_indexing":true,"default_results_per_page":10,"default_order_type":"ASC"}'),
('users', '{"email_confirmation":false,"register_is_enabled":true,"auto_delete_inactive_users":0,"user_deletion_reminder":0,"login_rememberme_checkbox_is_checked":true,"login_rememberme_cookie_days":30,"login_lockout_is_enabled":false,"login_lockout_max_retries":5,"login_lockout_time":15,"blacklisted_domains":[],"blacklisted_countries":[],"blacklisted_ips":[],"welcome_email_is_enabled":false,"register_social_login_require_password":false}'),
('ads', '{"header":"","footer":""}'),
('captcha', '{"type":"basic","recaptcha_public_key":"","recaptcha_private_key":"","hcaptcha_site_key":"","hcaptcha_secret_key":"","turnstile_site_key":"","turnstile_secret_key":"","login_is_enabled":false,"register_is_enabled":true,"lost_password_is_enabled":true,"resend_activation_is_enabled":true,"contact_is_enabled":"1"}'),
('cron', '{"key":"","cron_datetime":"","reset_date":""}'),
('email_notifications', '{"emails":"","new_user":false,"new_payment":false,"new_domain":false}'),
('facebook', '{"is_enabled":false,"app_id":"","app_secret":""}'),
('google', '{"is_enabled":false,"client_id":"","client_secret":""}'),
('twitter', '{"is_enabled":false,"consumer_api_key":"","consumer_api_secret":""}'),
('discord', '{"is_enabled":false,"client_id":"","client_secret":""}'),
('linkedin', '{"is_enabled":false,"client_id":"","client_secret":""}'),
('microsoft', '{"is_enabled":false,"client_id":"","client_secret":""}'),
('favicon', ''),
('logo', ''),
('opengraph', ''),
('plan_custom', '{"plan_id":"custom","name":"Custom","status":1}'),
('plan_free', '{"plan_id":"free","name":"Free","days":null,"status":1,"settings":{"no_ads":false,"removable_branding":false,"custom_branding":false,"api_is_enabled":true,"affiliate_is_enabled":false,"campaigns_limit":5,"notifications_limit":25,"notifications_impressions_limit":100000,"notification_handlers_limit":5,"domains_limit":1,"teams_limit":1,"team_members_limit":1,"email_reports_is_enabled":true,"track_notifications_retention":-1,"notification_handlers_email_limit":-1,"notification_handlers_webhook_limit":-1,"notification_handlers_slack_limit":-1,"notification_handlers_discord_limit":-1,"notification_handlers_telegram_limit":-1,"notification_handlers_microsoft_teams_limit":-1,"notification_handlers_twilio_limit":-1,"notification_handlers_twilio_call_limit":-1,"notification_handlers_whatsapp_limit":-1,"notification_handlers_x_limit":-1,"notification_handlers_google_chat_limit":-1,"notification_handlers_push_subscriber_id_limit":-1,"notification_handlers_internal_notification_limit":-1,"active_notification_handlers_per_resource_limit":-1,"export":{"csv":true,"json":true,"pdf":true},"enabled_notifications":{"INFORMATIONAL":true,"COUPON":true,"LIVE_COUNTER":true,"EMAIL_COLLECTOR":true,"CONVERSIONS":true,"CONVERSIONS_COUNTER":true,"VIDEO":true,"AUDIO":true,"SOCIAL_SHARE":true,"REVIEWS":true,"EMOJI_FEEDBACK":true,"COOKIE_NOTIFICATION":true,"SCORE_FEEDBACK":true,"REQUEST_COLLECTOR":true,"COUNTDOWN_COLLECTOR":true,"CUSTOM_HTML":true,"INFORMATIONAL_BAR":true,"IMAGE":true,"COLLECTOR_BAR":true,"COUPON_BAR":true,"BUTTON_BAR":true,"COLLECTOR_MODAL":true,"COLLECTOR_TWO_MODAL":true,"BUTTON_MODAL":true,"TEXT_FEEDBACK":true,"ENGAGEMENT_LINKS":true,"WHATSAPP_CHAT":true,"CONTACT_US":true,"INFORMATIONAL_MINI":true,"INFORMATIONAL_BAR_MINI":true}}}'),
('payment', '{"is_enabled":false,"brand_name":"WidgetHook","currency":"USD"}'),
('paypal', '{"is_enabled":false,"mode":"sandbox","client_id":"","secret":""}'),
('stripe', '{"is_enabled":false,"publishable_key":"","secret_key":"","webhook_secret":""}'),
('offline_payment', '{"is_enabled":false,"instructions":"Your offline payment instructions go here.."}'),
('coinbase', '{"is_enabled":false}'),
('payu', '{"is_enabled":false}'),
('paystack', '{"is_enabled":false}'),
('razorpay', '{"is_enabled":false}'),
('mollie', '{"is_enabled":false}'),
('yookassa', '{"is_enabled":false}'),
('crypto_com', '{"is_enabled":false}'),
('smtp', '{"host":"","from":"","from_name":"","encryption":"tls","port":"587","auth":"0","username":"","password":""}'),
('custom', '{"head_js":"","head_css":""}'),
('socials', '{"facebook":"","instagram":"","twitter":"","youtube":""}'),
('announcements', '{"id":"","content":"","show_logged_in":"","show_logged_out":""}'),
('business', '{"brand_name":"WidgetHook","invoice_nr_prefix":"","name":"","address":"","city":"","county":"","zip":"","country":"","email":"","phone":"","tax_type":"","tax_id":"","custom_key_one":"","custom_value_one":"","custom_key_two":"","custom_value_two":""}'),
('webhooks', '{"user_new":"","user_delete":"","domain_new":"","domain_update":"","domain_delete":""}'),
('notifications', '{"branding":"Powered by WidgetHook","analytics_is_enabled":true,"pixel_cache":0,"domains_is_enabled":true}'),
('notification_handlers', '{"email_is_enabled":true,"webhook_is_enabled":true,"slack_is_enabled":false,"discord_is_enabled":false,"telegram_is_enabled":false,"microsoft_teams_is_enabled":false,"twilio_is_enabled":false,"twilio_call_is_enabled":false,"whatsapp_is_enabled":false,"x_is_enabled":false,"google_chat_is_enabled":false,"push_subscriber_id_is_enabled":false,"internal_notification_is_enabled":true,"twilio_sid":"","twilio_token":"","twilio_number":"","whatsapp_number_id":"","whatsapp_access_token":""}'),
('internal_notifications', '{"admins_is_enabled":true,"users_is_enabled":true,"new_user":true,"new_payment":true,"new_newsletter_subscriber":false,"new_domain":false}'),
('offload', '{"assets_url":"","uploads_url":"","cdn_assets_url":"","cdn_uploads_url":""}'),
('cookie_consent', '{}'),
('license', '{"license":"standalone","type":"Extended License"}'),
('product_info', '{"version":"53.0.0", "code":"5300"}');

-- --------------------------------------------------------
-- Table structure for table `taxes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `taxes`;
CREATE TABLE IF NOT EXISTS `taxes` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `track_conversions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `track_conversions`;
CREATE TABLE IF NOT EXISTS `track_conversions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `type` varchar(32) NOT NULL DEFAULT '',
  `data` longtext NOT NULL,
  `url` varchar(2048) DEFAULT NULL,
  `location` varchar(512) DEFAULT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `notification_id` (`notification_id`),
  KEY `track_conversions_date_index` (`datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `track_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `track_logs`;
CREATE TABLE IF NOT EXISTS `track_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `domain` varchar(256) NOT NULL,
  `url` varchar(2048) NOT NULL,
  `ip_binary` varbinary(16) DEFAULT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `domain` (`domain`),
  KEY `track_logs_ip_binary_index` (`ip_binary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------
-- Table structure for table `track_notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `track_notifications`;
CREATE TABLE IF NOT EXISTS `track_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `notification_id` int(11) NOT NULL,
  `type` varchar(32) NOT NULL DEFAULT '',
  `url` varchar(2048) NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `notification_id` (`notification_id`),
  KEY `track_notifications_date_index` (`datetime`),
  KEY `track_notifications_campaign_id_index` (`campaign_id`),
  KEY `track_notifications_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(320) NOT NULL,
  `password` varchar(128) DEFAULT NULL,
  `name` varchar(64) NOT NULL,
  `avatar` varchar(40) DEFAULT NULL,
  `billing` text DEFAULT NULL,
  `api_key` varchar(32) DEFAULT NULL,
  `token_code` varchar(32) DEFAULT NULL,
  `twofa_secret` varchar(16) DEFAULT NULL,
  `anti_phishing_code` varchar(8) DEFAULT NULL,
  `one_time_login_code` varchar(32) DEFAULT NULL,
  `pending_email` varchar(128) DEFAULT NULL,
  `email_activation_code` varchar(32) DEFAULT NULL,
  `lost_password_code` varchar(32) DEFAULT NULL,
  `type` tinyint(4) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `is_newsletter_subscribed` tinyint(4) DEFAULT 0,
  `has_pending_internal_notifications` tinyint(4) DEFAULT 0,
  `plan_id` varchar(16) NOT NULL DEFAULT '',
  `plan_expiration_date` datetime DEFAULT NULL,
  `plan_settings` text DEFAULT NULL,
  `plan_trial_done` tinyint(4) DEFAULT 0,
  `plan_expiry_reminder` tinyint(4) DEFAULT 0,
  `payment_subscription_id` varchar(64) DEFAULT NULL,
  `payment_processor` varchar(16) DEFAULT NULL,
  `payment_total_amount` float DEFAULT NULL,
  `payment_currency` varchar(4) DEFAULT NULL,
  `referral_key` varchar(32) DEFAULT NULL,
  `referred_by` varchar(32) DEFAULT NULL,
  `referred_by_has_converted` tinyint(4) DEFAULT 0,
  `current_month_notifications_impressions` int(11) DEFAULT 0,
  `total_notifications_impressions` int(11) DEFAULT 0,
  `language` varchar(32) DEFAULT 'english',
  `timezone` varchar(32) DEFAULT 'UTC',
  `currency` varchar(4) DEFAULT 'USD',
  `source` varchar(32) DEFAULT 'direct',
  `continent_code` varchar(8) DEFAULT NULL,
  `city_name` varchar(128) DEFAULT NULL,
  `device_type` varchar(16) DEFAULT NULL,
  `os_name` varchar(16) DEFAULT NULL,
  `browser_name` varchar(32) DEFAULT NULL,
  `browser_language` varchar(32) DEFAULT NULL,
  `extra` text DEFAULT NULL,
  `preferences` text DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `country` varchar(32) DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `last_user_agent` varchar(256) DEFAULT NULL,
  `total_logins` int(11) DEFAULT 0,
  `user_deletion_reminder` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`user_id`),
  KEY `plan_id` (`plan_id`),
  KEY `api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Default admin user (password: admin)
-- --------------------------------------------------------

INSERT INTO `users` (`user_id`, `email`, `password`, `name`, `billing`, `api_key`, `token_code`, `twofa_secret`, `one_time_login_code`, `pending_email`, `email_activation_code`, `lost_password_code`, `type`, `status`, `plan_id`, `plan_expiration_date`, `plan_settings`, `plan_trial_done`, `plan_expiry_reminder`, `payment_subscription_id`, `payment_processor`, `payment_total_amount`, `payment_currency`, `referral_key`, `referred_by`, `referred_by_has_converted`, `current_month_notifications_impressions`, `total_notifications_impressions`, `language`, `timezone`, `datetime`, `ip`, `country`, `last_activity`, `last_user_agent`, `total_logins`, `user_deletion_reminder`) VALUES
(1, 'admin@example.com', '$2y$10$uFNO0pQKEHSFcus1zSFlveiPCB3EvG9ZlES7XKgJFTAl5JbRGFCWy', 'Admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 'custom', '2030-12-31 23:59:59', '{"no_ads":true,"removable_branding":true,"custom_branding":true,"api_is_enabled":true,"affiliate_is_enabled":false,"campaigns_limit":-1,"notifications_limit":-1,"notifications_impressions_limit":-1,"track_notifications_retention":-1,"enabled_notifications":{"INFORMATIONAL":true,"COUPON":true,"LIVE_COUNTER":true,"EMAIL_COLLECTOR":true,"CONVERSIONS":true,"CONVERSIONS_COUNTER":true,"VIDEO":true,"SOCIAL_SHARE":true,"REVIEWS":true,"EMOJI_FEEDBACK":true,"COOKIE_NOTIFICATION":true,"SCORE_FEEDBACK":true,"REQUEST_COLLECTOR":true,"COUNTDOWN_COLLECTOR":true,"CUSTOM_HTML":true,"INFORMATIONAL_BAR":true,"IMAGE":true,"COLLECTOR_BAR":true,"COUPON_BAR":true,"BUTTON_BAR":true,"COLLECTOR_MODAL":true,"COLLECTOR_TWO_MODAL":true,"BUTTON_MODAL":true,"TEXT_FEEDBACK":true,"ENGAGEMENT_LINKS":true}}', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 'english', 'UTC', NOW(), NULL, NULL, NULL, NULL, 0, 0);

-- --------------------------------------------------------
-- Table structure for table `users_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users_logs`;
CREATE TABLE IF NOT EXISTS `users_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(64) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `device_type` varchar(16) DEFAULT NULL,
  `os_name` varchar(16) DEFAULT NULL,
  `country_code` varchar(8) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `users_logs_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `broadcasts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `broadcasts`;
CREATE TABLE IF NOT EXISTS `broadcasts` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `blog_posts_categories`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `blog_posts_categories`;
CREATE TABLE IF NOT EXISTS `blog_posts_categories` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `blog_posts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `blog_posts`;
CREATE TABLE IF NOT EXISTS `blog_posts` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `blog_posts_ratings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `blog_posts_ratings`;
CREATE TABLE IF NOT EXISTS `blog_posts_ratings` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `blog_post_id` bigint(20) UNSIGNED NOT NULL,
  `ip_binary` varbinary(16) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL,
  `datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `blog_post_id` (`blog_post_id`),
  KEY `ip_binary` (`ip_binary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `teams`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `teams`;
CREATE TABLE IF NOT EXISTS `teams` (
  `team_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `datetime` datetime DEFAULT NULL,
  `last_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`team_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `teams_members`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `teams_members`;
CREATE TABLE IF NOT EXISTS `teams_members` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Foreign key constraints
-- --------------------------------------------------------

ALTER TABLE `campaigns`
  ADD CONSTRAINT `campaigns_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`campaign_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `pages`
  ADD CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`pages_category_id`) REFERENCES `pages_categories` (`pages_category_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `track_conversions`
  ADD CONSTRAINT `track_conversions_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `track_logs`
  ADD CONSTRAINT `track_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `track_notifications`
  ADD CONSTRAINT `track_notifications_campaigns_campaign_id_fk` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`campaign_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `track_notifications_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `users_logs`
  ADD CONSTRAINT `users_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
