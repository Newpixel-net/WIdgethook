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
  `pixel_key` varchar(32) DEFAULT NULL,
  `name` varchar(256) NOT NULL DEFAULT '',
  `domain` varchar(256) NOT NULL DEFAULT '',
  `include_subdomains` int(11) DEFAULT 0,
  `branding` text DEFAULT NULL,
  `is_enabled` tinyint(4) NOT NULL DEFAULT 1,
  `last_datetime` datetime DEFAULT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`campaign_id`),
  KEY `user_id` (`user_id`),
  KEY `campaigns_domain_index` (`domain`),
  KEY `campaigns_pixel_key_index` (`pixel_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

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
('users', '{"email_confirmation":false,"register_is_enabled":true,"auto_delete_inactive_users":0,"user_deletion_reminder":0}'),
('ads', '{"header":"","footer":""}'),
('captcha', '{"type":"basic","recaptcha_public_key":"","recaptcha_private_key":"","hcaptcha_site_key":"","hcaptcha_secret_key":"","login_is_enabled":false,"register_is_enabled":true,"lost_password_is_enabled":true,"resend_activation_is_enabled":true,"contact_is_enabled":"1"}'),
('cron', '{"key":"","cron_datetime":"","reset_date":""}'),
('email_notifications', '{"emails":"","new_user":"0","new_payment":"0"}'),
('facebook', '{"is_enabled":"0","app_id":"","app_secret":""}'),
('google', '{"is_enabled":"0","client_id":"","client_secret":""}'),
('twitter', '{"is_enabled":"0","consumer_api_key":"","consumer_api_secret":""}'),
('discord', '{"is_enabled":"0"}'),
('favicon', ''),
('logo', ''),
('opengraph', ''),
('plan_custom', '{"plan_id":"custom","name":"Custom","status":1}'),
('plan_free', '{"plan_id":"free","name":"Free","days":null,"status":1,"settings":{"no_ads":false,"removable_branding":false,"custom_branding":false,"api_is_enabled":true,"affiliate_is_enabled":false,"campaigns_limit":5,"notifications_limit":25,"notifications_impressions_limit":100000,"enabled_notifications":{"INFORMATIONAL":true,"COUPON":true,"LIVE_COUNTER":true,"EMAIL_COLLECTOR":true,"LATEST_CONVERSION":true,"CONVERSIONS_COUNTER":true,"VIDEO":true,"SOCIAL_SHARE":true,"RANDOM_REVIEW":true,"EMOJI_FEEDBACK":true,"COOKIE_NOTIFICATION":true,"SCORE_FEEDBACK":true,"REQUEST_COLLECTOR":true,"COUNTDOWN_COLLECTOR":true,"INFORMATIONAL_BAR":true,"IMAGE":true,"COLLECTOR_BAR":true,"COUPON_BAR":true,"BUTTON_BAR":true,"COLLECTOR_MODAL":true,"COLLECTOR_TWO_MODAL":true,"BUTTON_MODAL":true,"TEXT_FEEDBACK":true,"ENGAGEMENT_LINKS":true}}}'),
('payment', '{"is_enabled":false,"brand_name":"WidgetHook","currency":"USD"}'),
('paypal', '{"is_enabled":"0","mode":"sandbox","client_id":"","secret":""}'),
('stripe', '{"is_enabled":"0","publishable_key":"","secret_key":"","webhook_secret":""}'),
('offline_payment', '{"is_enabled":"0","instructions":"Your offline payment instructions go here.."}'),
('coinbase', '{"is_enabled":"0"}'),
('payu', '{"is_enabled":"0"}'),
('paystack', '{"is_enabled":"0"}'),
('razorpay', '{"is_enabled":"0"}'),
('mollie', '{"is_enabled":"0"}'),
('yookassa', '{"is_enabled":"0"}'),
('crypto_com', '{"is_enabled":"0"}'),
('smtp', '{"host":"","from":"","from_name":"","encryption":"tls","port":"587","auth":"0","username":"","password":""}'),
('custom', '{"head_js":"","head_css":""}'),
('socials', '{"facebook":"","instagram":"","twitter":"","youtube":""}'),
('announcements', '{"id":"","content":"","show_logged_in":"","show_logged_out":""}'),
('business', '{"brand_name":"WidgetHook","invoice_nr_prefix":"","name":"","address":"","city":"","county":"","zip":"","country":"","email":"","phone":"","tax_type":"","tax_id":"","custom_key_one":"","custom_value_one":"","custom_key_two":"","custom_value_two":""}'),
('webhooks', '{"user_new": "", "user_delete": ""}'),
('notifications', '{"branding":"Powered by WidgetHook","analytics_is_enabled":true,"pixel_cache":0}'),
('cookie_consent', '{}'),
('license', '{"license":"standalone","type":"Extended License"}'),
('product_info', '{"version":"53.0.0", "code":"5300"}');

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
  `campaign_id` int(11) DEFAULT NULL,
  `notification_id` int(11) NOT NULL,
  `type` varchar(32) NOT NULL DEFAULT '',
  `url` varchar(2048) NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `notification_id` (`notification_id`),
  KEY `track_notifications_date_index` (`datetime`),
  KEY `track_notifications_campaign_id_index` (`campaign_id`)
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
  `billing` text DEFAULT NULL,
  `api_key` varchar(32) DEFAULT NULL,
  `token_code` varchar(32) DEFAULT NULL,
  `twofa_secret` varchar(16) DEFAULT NULL,
  `one_time_login_code` varchar(32) DEFAULT NULL,
  `pending_email` varchar(128) DEFAULT NULL,
  `email_activation_code` varchar(32) DEFAULT NULL,
  `lost_password_code` varchar(32) DEFAULT NULL,
  `type` tinyint(4) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 0,
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
