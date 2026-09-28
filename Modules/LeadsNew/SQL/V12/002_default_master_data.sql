-- Leads-New V12 default master data
-- Run on the ACTIVE TENANT DATABASE only.

INSERT INTO `leads_new_sources` (`business_id`, `name`, `color`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, 'Walk-in', '#10b981', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `leads_new_sources` WHERE `business_id` IS NULL AND `name` = 'Walk-in');
INSERT INTO `leads_new_sources` (`business_id`, `name`, `color`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, 'Website', '#3b82f6', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `leads_new_sources` WHERE `business_id` IS NULL AND `name` = 'Website');
INSERT INTO `leads_new_sources` (`business_id`, `name`, `color`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, 'Referral', '#8b5cf6', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `leads_new_sources` WHERE `business_id` IS NULL AND `name` = 'Referral');

INSERT INTO `leads_new_statuses` (`business_id`, `name`, `color`, `is_final`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, 'New', '#3b82f6', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `leads_new_statuses` WHERE `business_id` IS NULL AND `name` = 'New');
INSERT INTO `leads_new_statuses` (`business_id`, `name`, `color`, `is_final`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, 'In Progress', '#f59e0b', 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `leads_new_statuses` WHERE `business_id` IS NULL AND `name` = 'In Progress');
INSERT INTO `leads_new_statuses` (`business_id`, `name`, `color`, `is_final`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, 'Converted', '#22c55e', 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `leads_new_statuses` WHERE `business_id` IS NULL AND `name` = 'Converted');
INSERT INTO `leads_new_statuses` (`business_id`, `name`, `color`, `is_final`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, 'Lost', '#ef4444', 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `leads_new_statuses` WHERE `business_id` IS NULL AND `name` = 'Lost');

INSERT INTO `leads_new_priorities` (`business_id`, `name`, `color`, `sort_order`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, 'Low', '#64748b', 10, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `leads_new_priorities` WHERE `business_id` IS NULL AND `name` = 'Low');
INSERT INTO `leads_new_priorities` (`business_id`, `name`, `color`, `sort_order`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, 'Medium', '#f59e0b', 20, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `leads_new_priorities` WHERE `business_id` IS NULL AND `name` = 'Medium');
INSERT INTO `leads_new_priorities` (`business_id`, `name`, `color`, `sort_order`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, 'High', '#ef4444', 30, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `leads_new_priorities` WHERE `business_id` IS NULL AND `name` = 'High');
