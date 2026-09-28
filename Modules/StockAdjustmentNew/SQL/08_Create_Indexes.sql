-- Indexes are created by 01_Create_Tables.sql for fresh installations.
-- This file safely adds them to older/partial installations.
SET @db := DATABASE();

SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db AND table_name='san_stock_adjustments' AND index_name='san_adj_business_status_date_idx'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustments` ADD INDEX `san_adj_business_status_date_idx` (`business_id`,`status`,`adjustment_date`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db AND table_name='san_stock_adjustment_lines' AND index_name='san_line_product_batch_expiry_idx'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustment_lines` ADD INDEX `san_line_product_batch_expiry_idx` (`product_id`,`batch_no`,`expiry_date`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db AND table_name='san_stock_adjustment_movements' AND index_name='san_mov_scope_date_idx'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustment_movements` ADD INDEX `san_mov_scope_date_idx` (`business_id`,`location_id`,`store_id`,`movement_date`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db AND table_name='san_stock_adjustment_settings' AND index_name='san_settings_business_unique'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustment_settings` ADD UNIQUE INDEX `san_settings_business_unique` (`business_id`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db AND table_name='san_stock_adjustment_account_mappings' AND index_name='san_mapping_lookup_idx'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustment_account_mappings` ADD INDEX `san_mapping_lookup_idx` (`business_id`,`adjustment_type`,`category_id`,`sub_category_id`,`is_active`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db AND table_name='san_stock_adjustment_account_mappings' AND index_name='san_mapping_increase_account_idx')
  OR NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='san_stock_adjustment_account_mappings' AND column_name='increase_account_id'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustment_account_mappings` ADD INDEX `san_mapping_increase_account_idx` (`increase_account_id`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db AND table_name='san_stock_adjustment_account_mappings' AND index_name='san_mapping_decrease_account_idx')
  OR NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='san_stock_adjustment_account_mappings' AND column_name='decrease_account_id'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustment_account_mappings` ADD INDEX `san_mapping_decrease_account_idx` (`decrease_account_id`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
