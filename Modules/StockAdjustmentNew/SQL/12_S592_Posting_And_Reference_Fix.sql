-- S592 - Stock Adjustment New posting, per-product Increase/Decrease and reference fix
-- Run on every tenant database. Safe to run repeatedly.
SET @san_db := DATABASE();

-- Preserve the original document Type (Quantity / Value / Damage / Expiry).
-- Direction is stored separately on the document and on every product line.
SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustments' AND column_name='stock_adjustment_type'),
  'ALTER TABLE `san_stock_adjustments` ADD COLUMN `stock_adjustment_type` VARCHAR(20) NOT NULL DEFAULT ''increase''',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines' AND column_name='stock_adjustment_type'),
  'ALTER TABLE `san_stock_adjustment_lines` ADD COLUMN `stock_adjustment_type` VARCHAR(20) NOT NULL DEFAULT ''increase''',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustments' AND column_name='host_transaction_id'),
  'ALTER TABLE `san_stock_adjustments` ADD COLUMN `host_transaction_id` BIGINT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustments' AND column_name='posting_summary'),
  'ALTER TABLE `san_stock_adjustments` ADD COLUMN `posting_summary` JSON NULL',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@san_db AND table_name='san_stock_adjustments' AND index_name='san_adj_stock_type_idx'),
  'ALTER TABLE `san_stock_adjustments` ADD INDEX `san_adj_stock_type_idx` (`stock_adjustment_type`)',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines')
  AND NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines' AND index_name='san_line_stock_type_idx'),
  'ALTER TABLE `san_stock_adjustment_lines` ADD INDEX `san_line_stock_type_idx` (`stock_adjustment_type`)',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@san_db AND table_name='san_stock_adjustments' AND index_name='san_adj_host_transaction_idx'),
  'ALTER TABLE `san_stock_adjustments` ADD INDEX `san_adj_host_transaction_idx` (`host_transaction_id`)',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

-- Existing line directions are derived from the already-calculated quantity difference.
SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines' AND column_name='stock_adjustment_type')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines' AND column_name='adjustment_qty'),
  'UPDATE `san_stock_adjustment_lines` SET `stock_adjustment_type`=CASE WHEN COALESCE(`adjustment_qty`,0)<0 THEN ''decrease'' ELSE ''increase'' END WHERE `stock_adjustment_type` IS NULL OR `stock_adjustment_type` NOT IN (''increase'',''decrease'') OR (`stock_adjustment_type`=''increase'' AND COALESCE(`adjustment_qty`,0)<0) OR (`stock_adjustment_type`=''decrease'' AND COALESCE(`adjustment_qty`,0)>0)',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

-- Header direction becomes Mixed when the same document contains both directions.
SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustments')
  AND EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustments' AND column_name='stock_adjustment_type')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines' AND column_name='adjustment_qty'),
  'UPDATE `san_stock_adjustments` a LEFT JOIN (SELECT `adjustment_id`, CASE WHEN MAX(CASE WHEN `adjustment_qty`>0 THEN 1 ELSE 0 END)=1 AND MAX(CASE WHEN `adjustment_qty`<0 THEN 1 ELSE 0 END)=1 THEN ''mixed'' WHEN MAX(CASE WHEN `adjustment_qty`<0 THEN 1 ELSE 0 END)=1 THEN ''decrease'' ELSE ''increase'' END AS `derived_direction` FROM `san_stock_adjustment_lines` GROUP BY `adjustment_id`) d ON d.`adjustment_id`=a.`id` SET a.`stock_adjustment_type`=COALESCE(d.`derived_direction`,CASE WHEN COALESCE(a.`total_qty`,0)<0 THEN ''decrease'' ELSE ''increase'' END) WHERE NOT (a.`stock_adjustment_type` <=> COALESCE(d.`derived_direction`,CASE WHEN COALESCE(a.`total_qty`,0)<0 THEN ''decrease'' ELSE ''increase'' END))',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

-- Keep the original document Type contract and repair only invalid settings.
SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustments' AND column_name='adjustment_type'),
  'ALTER TABLE `san_stock_adjustments` MODIFY COLUMN `adjustment_type` VARCHAR(50) NOT NULL DEFAULT ''quantity''',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustment_settings' AND column_name='default_adjustment_type'),
  'ALTER TABLE `san_stock_adjustment_settings` MODIFY COLUMN `default_adjustment_type` VARCHAR(30) NOT NULL DEFAULT ''quantity''',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustment_settings' AND column_name='default_adjustment_type'),
  'UPDATE `san_stock_adjustment_settings` SET `default_adjustment_type`=''quantity'' WHERE `default_adjustment_type` IS NULL OR `default_adjustment_type` NOT IN (''quantity'',''value'',''damage'',''expiry'')',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

-- Posting must always be scoped to a real business location.
SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustment_settings' AND column_name='require_location'),
  'UPDATE `san_stock_adjustment_settings` SET `require_location`=1 WHERE `require_location`<>1 OR `require_location` IS NULL',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;
