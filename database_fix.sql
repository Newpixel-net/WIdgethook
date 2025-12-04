-- WidgetHook Database Fix Script
-- Run this in phpMyAdmin to add missing tables and settings

-- --------------------------------------------------------
-- Add missing payments table
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `processor` varchar(32) DEFAULT NULL,
  `type` varchar(32) DEFAULT NULL,
  `frequency` varchar(32) DEFAULT NULL,
  `code` varchar(32) DEFAULT NULL,
  `discount_amount` float DEFAULT NULL,
  `base_amount` float DEFAULT NULL,
  `email` varchar(256) DEFAULT NULL,
  `payment_id` varchar(128) DEFAULT NULL,
  `name` varchar(256) DEFAULT NULL,
  `plan` text DEFAULT NULL,
  `billing` text DEFAULT NULL,
  `business` text DEFAULT NULL,
  `taxes_ids` text DEFAULT NULL,
  `total_amount` float DEFAULT NULL,
  `total_amount_default_currency` float DEFAULT NULL,
  `currency` varchar(4) DEFAULT NULL,
  `payment_proof` varchar(40) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_user_id_index` (`user_id`),
  KEY `payments_plan_id_index` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Add missing internal_notifications table
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `internal_notifications` (
  `internal_notification_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `for_who` varchar(16) DEFAULT NULL,
  `from_who` varchar(16) DEFAULT NULL,
  `icon` varchar(64) DEFAULT NULL,
  `title` varchar(128) DEFAULT NULL,
  `description` varchar(512) DEFAULT NULL,
  `url` varchar(512) DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `read_datetime` datetime DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`internal_notification_id`),
  KEY `internal_notifications_user_id_index` (`user_id`),
  KEY `internal_notifications_for_who_index` (`for_who`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Add missing codes table
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `codes` (
  `code_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `type` varchar(16) DEFAULT NULL,
  `days` int(11) DEFAULT NULL,
  `code` varchar(32) DEFAULT NULL,
  `discount` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `redeemed` int(11) DEFAULT 0,
  `plans_ids` text DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`code_id`),
  KEY `type` (`type`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Add missing redeemed_codes table
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `redeemed_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `code_id` (`code_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Add missing taxes table
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `taxes` (
  `tax_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `description` varchar(256) DEFAULT NULL,
  `value` int(11) DEFAULT NULL,
  `value_type` varchar(16) DEFAULT NULL,
  `type` varchar(16) DEFAULT NULL,
  `billing_type` varchar(16) DEFAULT NULL,
  `countries` text DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`tax_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Add missing domains table
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `domains` (
  `domain_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `scheme` varchar(8) NOT NULL DEFAULT '',
  `host` varchar(256) NOT NULL DEFAULT '',
  `custom_index_url` varchar(256) DEFAULT NULL,
  `custom_not_found_url` varchar(256) DEFAULT NULL,
  `is_enabled` tinyint(4) DEFAULT 0,
  `datetime` datetime DEFAULT NULL,
  `last_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`domain_id`),
  KEY `user_id` (`user_id`),
  KEY `host` (`host`),
  KEY `is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Add missing notification_handlers table
-- --------------------------------------------------------

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
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Add missing email_reports table
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `email_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `campaign_id` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Add missing settings
-- --------------------------------------------------------

INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
('internal_notifications', '{"admins_is_enabled":true,"users_is_enabled":true,"new_user":true,"new_payment":true}');

-- --------------------------------------------------------
-- Add a default plan if none exist
-- --------------------------------------------------------

INSERT INTO `plans` (`plan_id`, `name`, `description`, `monthly_price`, `annual_price`, `lifetime_price`, `trial_days`, `settings`, `taxes_ids`, `codes_ids`, `color`, `status`, `order`, `datetime`)
SELECT 1, 'Pro', 'Full access to all features', 9.99, 99.99, 299.99, 0,
'{"no_ads":true,"removable_branding":true,"custom_branding":true,"api_is_enabled":true,"affiliate_is_enabled":false,"campaigns_limit":-1,"notifications_limit":-1,"notifications_impressions_limit":-1,"track_notifications_retention":-1,"enabled_notifications":{"INFORMATIONAL":true,"COUPON":true,"LIVE_COUNTER":true,"EMAIL_COLLECTOR":true,"CONVERSIONS":true,"CONVERSIONS_COUNTER":true,"VIDEO":true,"SOCIAL_SHARE":true,"REVIEWS":true,"EMOJI_FEEDBACK":true,"COOKIE_NOTIFICATION":true,"SCORE_FEEDBACK":true,"REQUEST_COLLECTOR":true,"COUNTDOWN_COLLECTOR":true,"CUSTOM_HTML":true,"INFORMATIONAL_BAR":true,"IMAGE":true,"COLLECTOR_BAR":true,"COUPON_BAR":true,"BUTTON_BAR":true,"COLLECTOR_MODAL":true,"COLLECTOR_TWO_MODAL":true,"BUTTON_MODAL":true,"TEXT_FEEDBACK":true,"ENGAGEMENT_LINKS":true}}',
NULL, NULL, '#3b82f6', 1, 0, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `plans` LIMIT 1);

-- --------------------------------------------------------
-- Done!
-- --------------------------------------------------------
