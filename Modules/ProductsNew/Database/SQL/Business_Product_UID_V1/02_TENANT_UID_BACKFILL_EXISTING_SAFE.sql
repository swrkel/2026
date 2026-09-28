-- ============================================================================
-- PRODUCTS NEW - BUSINESS PRODUCT UID V1
-- STEP 02: CONTROLLED EXISTING-ROW BACKFILL
-- Date: 15 Sep 2026
--
-- Run ONLY AFTER Step 01 on the selected tenant database.
--
-- GUARANTEE
--   Every existing product row receives its OWN new UUID.
--   Every existing variation row receives its OWN new UUID.
--   No name/SKU/barcode matching occurs. No rows are merged or re-ID'd.
-- ============================================================================

START TRANSACTION;

-- Abort naturally if Step 01 has not been applied (column missing).
UPDATE `products`
SET `product_uid` = LOWER(UUID())
WHERE `product_uid` IS NULL OR TRIM(`product_uid`) = '';

UPDATE `variations`
SET `variation_uid` = LOWER(UUID())
WHERE `variation_uid` IS NULL OR TRIM(`variation_uid`) = '';

-- Verification inside the same transaction.
SELECT
    DATABASE() AS tenant_database,
    (SELECT COUNT(*) FROM products) AS total_products,
    (SELECT COUNT(*) FROM products WHERE product_uid IS NULL OR product_uid = '') AS products_without_uid,
    (SELECT COUNT(DISTINCT product_uid) FROM products WHERE product_uid IS NOT NULL AND product_uid <> '') AS distinct_product_uids,
    (SELECT COUNT(*) FROM variations) AS total_variations,
    (SELECT COUNT(*) FROM variations WHERE variation_uid IS NULL OR variation_uid = '') AS variations_without_uid,
    (SELECT COUNT(DISTINCT variation_uid) FROM variations WHERE variation_uid IS NOT NULL AND variation_uid <> '') AS distinct_variation_uids;

COMMIT;
