-- Fix payment settings that are missing or incomplete
-- Run this in phpMyAdmin

-- First, let's see the current payment settings
-- SELECT `key`, `value` FROM `settings` WHERE `key` = 'payment';

-- Update payment settings with proper defaults
UPDATE `settings`
SET `value` = JSON_SET(
    COALESCE(`value`, '{}'),
    '$.is_enabled', true,
    '$.default_currency', 'USD',
    '$.currencies', JSON_ARRAY('USD'),
    '$.taxes_and_billing_is_enabled', false,
    '$.invoice_is_enabled', false,
    '$.codes_is_enabled', true,
    '$.type', 'both'
)
WHERE `key` = 'payment';

-- If the payment setting doesn't exist at all, insert it
INSERT IGNORE INTO `settings` (`key`, `value`)
VALUES ('payment', '{"is_enabled":true,"default_currency":"USD","currencies":["USD"],"taxes_and_billing_is_enabled":false,"invoice_is_enabled":false,"codes_is_enabled":true,"type":"both"}');

-- Verify the fix
SELECT `key`, `value` FROM `settings` WHERE `key` = 'payment';
