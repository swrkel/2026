INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('restaurantnew.gift_vouchers.view', 'web', NOW(), NOW()),
('restaurantnew.gift_vouchers.issue', 'web', NOW(), NOW()),
('restaurantnew.gift_vouchers.redeem', 'web', NOW(), NOW()),
('restaurantnew.gift_vouchers.top_up', 'web', NOW(), NOW()),
('restaurantnew.gift_vouchers.cancel', 'web', NOW(), NOW()),
('restaurantnew.gift_vouchers.reports', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = VALUES(`updated_at`);
