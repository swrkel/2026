-- Membership New MEMNEW_023
-- Safe incremental update: Settings status + Added By creator tracking.
-- Run in the active database used by the current business. No database name is hard-coded.
-- The Laravel migration is the recommended deployment method because it checks each
-- table/column before altering it. This SQL is supplied as a manual alternative for
-- database engines that support ALTER TABLE ... ADD COLUMN IF NOT EXISTS.

ALTER TABLE IF EXISTS `mn_regions`
    ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE IF EXISTS `mn_setting_options`
    ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1;

-- Add creator tracking to Membership New data tables where the column is missing.
ALTER TABLE IF EXISTS `mn_members` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_plans` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_payments` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_linked_businesses` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_point_rules` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_point_transactions` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_share_holdings` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_dividend_batches` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_dividend_payments` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_identity_cards` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_customer_maps` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_central_members` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_member_business_maps` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_business_customer_histories` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_duplicate_candidates` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_outlet_transaction_queue` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_merge_requests` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_dividend_payouts` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_audit_logs` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_approval_requests` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_business_access_rules` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_error_logs` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_import_batches` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_regions` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;
ALTER TABLE IF EXISTS `mn_setting_options` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL;

-- Backfill only where an existing column unambiguously identifies the creator/requester.
UPDATE `mn_audit_logs` SET `created_by` = `user_id` WHERE `created_by` IS NULL AND `user_id` IS NOT NULL;
UPDATE `mn_error_logs` SET `created_by` = `user_id` WHERE `created_by` IS NULL AND `user_id` IS NOT NULL;
UPDATE `mn_approval_requests` SET `created_by` = `requested_by` WHERE `created_by` IS NULL AND `requested_by` IS NOT NULL;
