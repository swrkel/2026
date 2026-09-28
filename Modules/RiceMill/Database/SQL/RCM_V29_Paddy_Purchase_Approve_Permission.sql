-- Rice Mill v29 - User Management New permission
-- Run inside the required TENANT database only when the artisan sync command
-- cannot be used. Safe to run more than once.
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'rice_mill.paddy_purchase.approve', 'web', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions`
    WHERE `name` = 'rice_mill.paddy_purchase.approve'
      AND `guard_name` = 'web'
);
