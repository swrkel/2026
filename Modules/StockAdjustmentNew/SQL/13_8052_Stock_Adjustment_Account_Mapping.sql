-- 8052 - Stock Adjustment New / Accounting Mapping redesign
-- Run on each tenant database only if the Laravel migration/auto-schema path is not used.
-- Idempotent: safe to run again on MySQL/MariaDB versions that support ADD COLUMN IF NOT EXISTS.

ALTER TABLE `san_stock_adjustment_account_mappings`
  ADD COLUMN IF NOT EXISTS `increase_account_id` BIGINT UNSIGNED NULL AFTER `account_to_link_id`,
  ADD COLUMN IF NOT EXISTS `decrease_account_id` BIGINT UNSIGNED NULL AFTER `increase_account_id`;

-- Existing legacy rows are intentionally left unchanged. Application code
-- reads their original adjustment_type + account_to_link_id contract until a
-- user edits and saves that row in the new two-account format.

SET @san8052_db := DATABASE();

SET @san8052_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@san8052_db AND table_name='san_stock_adjustment_account_mappings' AND index_name='san_mapping_increase_account_idx'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustment_account_mappings` ADD INDEX `san_mapping_increase_account_idx` (`increase_account_id`)'
);
PREPARE san8052_stmt FROM @san8052_sql; EXECUTE san8052_stmt; DEALLOCATE PREPARE san8052_stmt;

SET @san8052_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@san8052_db AND table_name='san_stock_adjustment_account_mappings' AND index_name='san_mapping_decrease_account_idx'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustment_account_mappings` ADD INDEX `san_mapping_decrease_account_idx` (`decrease_account_id`)'
);
PREPARE san8052_stmt FROM @san8052_sql; EXECUTE san8052_stmt; DEALLOCATE PREPARE san8052_stmt;
