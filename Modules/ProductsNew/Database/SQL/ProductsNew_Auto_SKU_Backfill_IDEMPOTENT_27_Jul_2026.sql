-- Products New - Automatic SKU repair/backfill
-- Date: 27 Jul 2026
-- Safe to run repeatedly in every tenant database.
--
-- IMPORTANT:
-- 1. No CREATE TABLE or ALTER TABLE is required for this issue.
-- 2. The application fix generates an SKU automatically before the product save completes.
-- 3. This SQL only repairs older rows whose SKU is NULL or blank.
-- 4. Existing non-blank SKUs are never changed.
-- 5. Duplicate candidate SKUs are not created.

SET @current_database := DATABASE();

SET @has_products_sku := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @current_database
      AND table_name = 'products'
      AND column_name = 'sku'
);

SET @has_products_business_id := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @current_database
      AND table_name = 'products'
      AND column_name = 'business_id'
);

SET @has_business_sku_prefix := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @current_database
      AND table_name = 'business'
      AND column_name = 'sku_prefix'
);

-- First preference: use the same business SKU prefix + zero-padded product ID
-- pattern used by the main product system.
SET @sql := IF(
    @has_products_sku = 1
    AND @has_products_business_id = 1
    AND @has_business_sku_prefix = 1,
    'UPDATE `products` AS `p`
     LEFT JOIN `business` AS `b`
       ON `b`.`id` = `p`.`business_id`
     LEFT JOIN `products` AS `duplicate_product`
       ON `duplicate_product`.`business_id` = `p`.`business_id`
      AND `duplicate_product`.`id` <> `p`.`id`
      AND `duplicate_product`.`sku` = CONCAT(COALESCE(TRIM(`b`.`sku_prefix`), ''''), LPAD(`p`.`id`, 4, ''0''))
     SET `p`.`sku` = CONCAT(COALESCE(TRIM(`b`.`sku_prefix`), ''''), LPAD(`p`.`id`, 4, ''0''))
     WHERE (`p`.`sku` IS NULL OR TRIM(`p`.`sku`) = '''')
       AND `duplicate_product`.`id` IS NULL',
    'SELECT ''Skipped prefix-based SKU backfill: required table/columns are not available.'' AS `message`'
);

PREPARE products_new_auto_sku_stmt FROM @sql;
EXECUTE products_new_auto_sku_stmt;
DEALLOCATE PREPARE products_new_auto_sku_stmt;

-- Fallback: repair any still-blank SKU with a tenant-safe Products New value.
-- The WHERE condition and duplicate self-join make this rerunnable and duplicate-safe.
SET @sql := IF(
    @has_products_sku = 1
    AND @has_products_business_id = 1,
    'UPDATE `products` AS `p`
     LEFT JOIN `products` AS `duplicate_product`
       ON `duplicate_product`.`business_id` = `p`.`business_id`
      AND `duplicate_product`.`id` <> `p`.`id`
      AND `duplicate_product`.`sku` = CONCAT(''PN-'', `p`.`business_id`, ''-'', LPAD(`p`.`id`, 4, ''0''))
     SET `p`.`sku` = CONCAT(''PN-'', `p`.`business_id`, ''-'', LPAD(`p`.`id`, 4, ''0''))
     WHERE (`p`.`sku` IS NULL OR TRIM(`p`.`sku`) = '''')
       AND `duplicate_product`.`id` IS NULL',
    'SELECT ''Skipped fallback SKU backfill: products.sku or products.business_id is unavailable.'' AS `message`'
);

PREPARE products_new_auto_sku_fallback_stmt FROM @sql;
EXECUTE products_new_auto_sku_fallback_stmt;
DEALLOCATE PREPARE products_new_auto_sku_fallback_stmt;

-- Verification: this should return zero rows after a successful backfill.
SET @sql := IF(
    @has_products_sku = 1,
    'SELECT `id`, `business_id`, `name`, `sku`
       FROM `products`
      WHERE `sku` IS NULL OR TRIM(`sku`) = ''''
      ORDER BY `business_id`, `id`',
    'SELECT ''Verification skipped: products.sku is unavailable.'' AS `message`'
);

PREPARE products_new_auto_sku_verify_stmt FROM @sql;
EXECUTE products_new_auto_sku_verify_stmt;
DEALLOCATE PREPARE products_new_auto_sku_verify_stmt;
