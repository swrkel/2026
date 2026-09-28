-- ============================================================================
-- PRODUCTS NEW - BUSINESS PRODUCT UID V1
-- STEP 01: TENANT UID FOUNDATION (SAFE / ADDITIVE / IDEMPOTENT)
-- Date: 15 Sep 2026
--
-- PURPOSE
--   Adds nullable UID columns only. Existing products.id / variations.id and all
--   stock/transaction relations remain unchanged.
--
-- IMPORTANT
--   * Run on ONE TENANT DATABASE at a time.
--   * This script DOES NOT backfill existing rows.
--   * It DOES NOT compare names, SKU or barcode.
--   * It DOES NOT merge products across businesses or tenants.
--   * Product UID is business-product identity; locations share the same product.
-- ============================================================================

SET @db := DATABASE();

-- --------------------------------------------------------------------------
-- products.product_uid
-- --------------------------------------------------------------------------
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = @db
              AND table_name = 'products'
              AND column_name = 'product_uid'
        ),
        'SELECT ''products.product_uid already exists'' AS info',
        'ALTER TABLE `products` ADD COLUMN `product_uid` CHAR(36) NULL AFTER `id`'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------------------------
-- variations.variation_uid
-- --------------------------------------------------------------------------
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = @db
              AND table_name = 'variations'
              AND column_name = 'variation_uid'
        ),
        'SELECT ''variations.variation_uid already exists'' AS info',
        'ALTER TABLE `variations` ADD COLUMN `variation_uid` CHAR(36) NULL AFTER `id`'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------------------------
-- Unique index: products.product_uid
-- MySQL allows multiple NULL values under UNIQUE, so legacy rows remain valid.
-- --------------------------------------------------------------------------
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = @db
              AND table_name = 'products'
              AND index_name = 'products_product_uid_unique'
        ),
        'SELECT ''products_product_uid_unique already exists'' AS info',
        'ALTER TABLE `products` ADD UNIQUE KEY `products_product_uid_unique` (`product_uid`)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------------------------
-- Unique index: variations.variation_uid
-- --------------------------------------------------------------------------
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = @db
              AND table_name = 'variations'
              AND index_name = 'variations_variation_uid_unique'
        ),
        'SELECT ''variations_variation_uid_unique already exists'' AS info',
        'ALTER TABLE `variations` ADD UNIQUE KEY `variations_variation_uid_unique` (`variation_uid`)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------------------------
-- READ-ONLY VERIFICATION
-- --------------------------------------------------------------------------
SELECT
    DATABASE() AS tenant_database,
    (SELECT COUNT(*) FROM products) AS total_products,
    (SELECT COUNT(*) FROM products WHERE product_uid IS NULL OR product_uid = '') AS products_without_uid,
    (SELECT COUNT(*) FROM variations) AS total_variations,
    (SELECT COUNT(*) FROM variations WHERE variation_uid IS NULL OR variation_uid = '') AS variations_without_uid;
