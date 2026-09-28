-- Rice Mill v32 - Receive Paddy snapshot limit + Rice Product/Paddy mapping
-- Run inside each EXISTING tenant database. Safe to run more than once.
-- No stored procedure, DECLARE block or ALTER ... IF NOT EXISTS syntax is used.

SET @rcm_sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rcm_products' AND COLUMN_NAME = 'paddy_variety_id'
        ),
        'SELECT 1',
        'ALTER TABLE `rcm_products` ADD COLUMN `paddy_variety_id` BIGINT UNSIGNED NULL AFTER `rice_type`'
    )
);
PREPARE rcm_stmt FROM @rcm_sql;
EXECUTE rcm_stmt;
DEALLOCATE PREPARE rcm_stmt;

SET @rcm_sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rcm_products' AND INDEX_NAME = 'rcm_products_business_paddy_variety_idx'
        ),
        'SELECT 1',
        'ALTER TABLE `rcm_products` ADD INDEX `rcm_products_business_paddy_variety_idx` (`business_id`, `paddy_variety_id`)'
    )
);
PREPARE rcm_stmt FROM @rcm_sql;
EXECUTE rcm_stmt;
DEALLOCATE PREPARE rcm_stmt;

SET @rcm_sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rcm_paddy_receipts' AND COLUMN_NAME = 'foreign_matter_limit_percent'
        ),
        'SELECT 1',
        'ALTER TABLE `rcm_paddy_receipts` ADD COLUMN `foreign_matter_limit_percent` DECIMAL(8,3) NULL AFTER `foreign_matter_percent`'
    )
);
PREPARE rcm_stmt FROM @rcm_sql;
EXECUTE rcm_stmt;
DEALLOCATE PREPARE rcm_stmt;
