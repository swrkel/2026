-- IS1777 - Suppliers Module - Supplier Product Mapping schema correction
-- Run this file separately in every tenant database.
-- It is idempotent and safely skips columns/indexes that already exist.

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'supplier_product_mappings'
       AND COLUMN_NAME = 'business_id') = 0,
    'ALTER TABLE `supplier_product_mappings` ADD COLUMN `business_id` BIGINT UNSIGNED NULL AFTER `id`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'supplier_product_mappings'
       AND COLUMN_NAME = 'supplier_sku') = 0,
    'ALTER TABLE `supplier_product_mappings` ADD COLUMN `supplier_sku` VARCHAR(191) NULL AFTER `product_id`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `supplier_product_mappings` AS `spm`
INNER JOIN `contacts` AS `c` ON `c`.`id` = `spm`.`supplier_id`
SET `spm`.`business_id` = `c`.`business_id`
WHERE `spm`.`business_id` IS NULL OR `spm`.`business_id` = 0;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'supplier_product_mappings'
       AND INDEX_NAME = 'spm_business_id_index') = 0,
    'ALTER TABLE `supplier_product_mappings` ADD INDEX `spm_business_id_index` (`business_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'supplier_product_mappings'
       AND INDEX_NAME = 'spm_business_supplier_product_index') = 0,
    'ALTER TABLE `supplier_product_mappings` ADD INDEX `spm_business_supplier_product_index` (`business_id`, `supplier_id`, `product_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
