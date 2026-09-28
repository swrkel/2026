-- Rice Mill - Products New LIVE Master Correction
-- 18 Sep 2026
-- Run in EACH tenant database that uses Rice Mill.
-- Safe to re-run.
--
-- IMPORTANT - verified against the Products New module code:
--   Products New categories are stored in `categories`.
--   Products New products are stored in `products`.
--   rcm_paddy_varieties.paddy_product_id = products.id
--   rcm_products.products_new_product_id = products.id
--
-- This patch also repairs IDs written by the previous interim Rice Mill build
-- that incorrectly treated products_new_products as the live Products New table.

SET @rcm_db := DATABASE();

-- Ensure Paddy Variety link column exists.
SELECT COUNT(*) INTO @rcm_has_paddy_col
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=@rcm_db AND TABLE_NAME='rcm_paddy_varieties' AND COLUMN_NAME='paddy_product_id';
SET @rcm_sql := IF(@rcm_has_paddy_col=0,
 'ALTER TABLE `rcm_paddy_varieties` ADD COLUMN `paddy_product_id` BIGINT UNSIGNED NULL AFTER `business_id`',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

SELECT COUNT(*) INTO @rcm_has_paddy_idx
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@rcm_db AND TABLE_NAME='rcm_paddy_varieties' AND INDEX_NAME='rcm_paddy_variety_product_idx';
SET @rcm_sql := IF(@rcm_has_paddy_idx=0,
 'ALTER TABLE `rcm_paddy_varieties` ADD INDEX `rcm_paddy_variety_product_idx` (`paddy_product_id`)',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

SELECT COUNT(*) INTO @rcm_has_paddy_uq
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@rcm_db AND TABLE_NAME='rcm_paddy_varieties' AND INDEX_NAME='rcm_paddy_variety_business_product_unique';
SET @rcm_sql := IF(@rcm_has_paddy_uq=0,
 'ALTER TABLE `rcm_paddy_varieties` ADD UNIQUE INDEX `rcm_paddy_variety_business_product_unique` (`business_id`,`paddy_product_id`)',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

-- Ensure Rice Product live Products New link column exists.
SELECT COUNT(*) INTO @rcm_has_rice_col
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=@rcm_db AND TABLE_NAME='rcm_products' AND COLUMN_NAME='products_new_product_id';
SET @rcm_sql := IF(@rcm_has_rice_col=0,
 'ALTER TABLE `rcm_products` ADD COLUMN `products_new_product_id` BIGINT UNSIGNED NULL AFTER `business_id`',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

SELECT COUNT(*) INTO @rcm_has_rice_idx
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@rcm_db AND TABLE_NAME='rcm_products' AND INDEX_NAME='rcm_products_products_new_product_idx';
SET @rcm_sql := IF(@rcm_has_rice_idx=0,
 'ALTER TABLE `rcm_products` ADD INDEX `rcm_products_products_new_product_idx` (`products_new_product_id`)',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

SELECT COUNT(*) INTO @rcm_has_rice_uq
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@rcm_db AND TABLE_NAME='rcm_products' AND INDEX_NAME='rcm_products_business_products_new_unique';
SET @rcm_sql := IF(@rcm_has_rice_uq=0,
 'ALTER TABLE `rcm_products` ADD UNIQUE INDEX `rcm_products_business_products_new_unique` (`business_id`,`products_new_product_id`)',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

SELECT COUNT(*) INTO @rcm_has_products
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=@rcm_db AND TABLE_NAME='products';

-- Clear any Paddy link that is not the same Products New live product by
-- ID + Business + Name + SKU, then safely backfill from products.id.
SET @rcm_sql := IF(@rcm_has_products>0,
 'UPDATE `rcm_paddy_varieties` pv
  LEFT JOIN `products` p
    ON p.`id`=pv.`paddy_product_id`
   AND p.`business_id`=pv.`business_id`
   AND TRIM(p.`name`)=TRIM(pv.`name`)
   AND TRIM(COALESCE(p.`sku`,CHAR(0)))=TRIM(COALESCE(pv.`code`,CHAR(0)))
  SET pv.`paddy_product_id`=NULL
  WHERE pv.`paddy_product_id` IS NOT NULL AND p.`id` IS NULL',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

SET @rcm_sql := IF(@rcm_has_products>0,
 'UPDATE `rcm_paddy_varieties` pv
  INNER JOIN `products` p
    ON p.`business_id`=pv.`business_id`
   AND TRIM(p.`name`)=TRIM(pv.`name`)
   AND TRIM(COALESCE(p.`sku`,CHAR(0)))=TRIM(COALESCE(pv.`code`,CHAR(0)))
  LEFT JOIN `rcm_paddy_varieties` duplicate_link
    ON duplicate_link.`business_id`=pv.`business_id`
   AND duplicate_link.`paddy_product_id`=p.`id`
   AND duplicate_link.`id`<>pv.`id`
  SET pv.`paddy_product_id`=p.`id`
  WHERE pv.`paddy_product_id` IS NULL AND duplicate_link.`id` IS NULL',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

-- Repair/backfill Rice Product live Products New IDs the same way.
SET @rcm_sql := IF(@rcm_has_products>0,
 'UPDATE `rcm_products` rp
  LEFT JOIN `products` p
    ON p.`id`=rp.`products_new_product_id`
   AND p.`business_id`=rp.`business_id`
   AND TRIM(p.`name`)=TRIM(rp.`name`)
   AND TRIM(COALESCE(p.`sku`,CHAR(0)))=TRIM(COALESCE(rp.`code`,CHAR(0)))
  SET rp.`products_new_product_id`=NULL
  WHERE rp.`products_new_product_id` IS NOT NULL AND p.`id` IS NULL',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

SET @rcm_sql := IF(@rcm_has_products>0,
 'UPDATE `rcm_products` rp
  INNER JOIN `products` p
    ON p.`business_id`=rp.`business_id`
   AND TRIM(p.`name`)=TRIM(rp.`name`)
   AND TRIM(COALESCE(p.`sku`,CHAR(0)))=TRIM(COALESCE(rp.`code`,CHAR(0)))
  LEFT JOIN `rcm_products` duplicate_link
    ON duplicate_link.`business_id`=rp.`business_id`
   AND duplicate_link.`products_new_product_id`=p.`id`
   AND duplicate_link.`id`<>rp.`id`
  SET rp.`products_new_product_id`=p.`id`
  WHERE rp.`products_new_product_id` IS NULL AND duplicate_link.`id` IS NULL',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

-- Verification
SELECT
 (SELECT COUNT(*) FROM `categories`) AS products_new_live_categories,
 (SELECT COUNT(*) FROM `products`) AS products_new_live_products,
 (SELECT COUNT(*) FROM `rcm_paddy_varieties` WHERE `paddy_product_id` IS NOT NULL) AS linked_paddy_varieties,
 (SELECT COUNT(*) FROM `rcm_products` WHERE `products_new_product_id` IS NOT NULL) AS linked_rice_products;
