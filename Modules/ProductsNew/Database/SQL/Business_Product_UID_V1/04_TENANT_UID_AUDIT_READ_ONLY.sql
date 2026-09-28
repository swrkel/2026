-- ============================================================================
-- PRODUCTS NEW - BUSINESS PRODUCT UID V1
-- STEP 04: READ-ONLY AUDIT
-- Safe to run on a UID-enabled tenant database at any time.
-- ============================================================================

SELECT
    DATABASE() AS tenant_database,
    COUNT(*) AS total_products,
    SUM(product_uid IS NULL OR product_uid = '') AS products_without_uid,
    COUNT(DISTINCT CASE WHEN product_uid IS NOT NULL AND product_uid <> '' THEN product_uid END) AS distinct_product_uids
FROM products;

SELECT
    business_id,
    COUNT(*) AS products,
    COUNT(DISTINCT product_uid) AS distinct_product_uids,
    SUM(product_uid IS NULL OR product_uid = '') AS products_without_uid
FROM products
GROUP BY business_id
ORDER BY business_id;

-- This should return ZERO rows.
SELECT product_uid, COUNT(*) AS duplicate_count
FROM products
WHERE product_uid IS NOT NULL AND product_uid <> ''
GROUP BY product_uid
HAVING COUNT(*) > 1;

-- This should return ZERO rows.
SELECT variation_uid, COUNT(*) AS duplicate_count
FROM variations
WHERE variation_uid IS NOT NULL AND variation_uid <> ''
GROUP BY variation_uid
HAVING COUNT(*) > 1;
