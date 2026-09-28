-- Rice Mill - Paddy Variety Product Link (safe tenant patch)
-- 18 Sep 2026
-- Run in each tenant database that uses Rice Mill.
-- Safe to re-run. No data is deleted or changed.

SET @rcm_db := DATABASE();

SELECT COUNT(*) INTO @rcm_has_col
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @rcm_db
  AND TABLE_NAME = 'rcm_paddy_varieties'
  AND COLUMN_NAME = 'paddy_product_id';

SET @rcm_sql := IF(
    @rcm_has_col = 0,
    'ALTER TABLE `rcm_paddy_varieties` ADD COLUMN `paddy_product_id` BIGINT UNSIGNED NULL AFTER `business_id`',
    'SELECT 1'
);
PREPARE rcm_stmt FROM @rcm_sql;
EXECUTE rcm_stmt;
DEALLOCATE PREPARE rcm_stmt;

SELECT COUNT(*) INTO @rcm_has_idx
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @rcm_db
  AND TABLE_NAME = 'rcm_paddy_varieties'
  AND INDEX_NAME = 'rcm_paddy_variety_product_idx';

SET @rcm_sql := IF(
    @rcm_has_idx = 0,
    'ALTER TABLE `rcm_paddy_varieties` ADD INDEX `rcm_paddy_variety_product_idx` (`paddy_product_id`)',
    'SELECT 1'
);
PREPARE rcm_stmt FROM @rcm_sql;
EXECUTE rcm_stmt;
DEALLOCATE PREPARE rcm_stmt;

SELECT COUNT(*) INTO @rcm_has_uq
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @rcm_db
  AND TABLE_NAME = 'rcm_paddy_varieties'
  AND INDEX_NAME = 'rcm_paddy_variety_business_product_unique';

SET @rcm_sql := IF(
    @rcm_has_uq = 0,
    'ALTER TABLE `rcm_paddy_varieties` ADD UNIQUE INDEX `rcm_paddy_variety_business_product_unique` (`business_id`,`paddy_product_id`)',
    'SELECT 1'
);
PREPARE rcm_stmt FROM @rcm_sql;
EXECUTE rcm_stmt;
DEALLOCATE PREPARE rcm_stmt;
