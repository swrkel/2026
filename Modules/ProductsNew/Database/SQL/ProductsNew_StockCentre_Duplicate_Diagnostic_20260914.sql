-- Products New / Stock Centre duplicate diagnostic
-- Date: 14 Sep 2026
-- READ ONLY: this file performs SELECT statements only.
-- Reported product: 2T Loose Oil - 210Ltrs

SET @pn_product_name := '2T Loose Oil - 210Ltrs';

-- 1) Product master rows with the exact reported name.
SELECT
    p.id AS product_id,
    p.business_id,
    p.name,
    p.sku,
    p.type,
    p.enable_stock
FROM products p
WHERE p.name = @pn_product_name
ORDER BY p.business_id, p.id;

-- 2) Core variations underneath the reported product.
SELECT
    p.id AS product_id,
    p.name AS product_name,
    p.sku AS product_sku,
    p.type AS product_type,
    v.id AS variation_id,
    v.product_variation_id,
    v.name AS variation_name,
    v.sub_sku
FROM products p
JOIN variations v ON v.product_id = p.id
WHERE p.name = @pn_product_name
ORDER BY p.id, v.id;

-- 3) Raw location-stock rows. More than one row for the same
--    variation_id + location_id is a physical duplicate stock shape.
SELECT
    p.id AS product_id,
    p.name AS product_name,
    p.sku AS product_sku,
    p.type AS product_type,
    v.id AS variation_id,
    v.name AS variation_name,
    v.sub_sku,
    vld.id AS vld_id,
    vld.location_id,
    bl.name AS location_name,
    vld.qty_available
FROM products p
JOIN variations v ON v.product_id = p.id
JOIN variation_location_details vld ON vld.variation_id = v.id
LEFT JOIN business_locations bl ON bl.id = vld.location_id
WHERE p.name = @pn_product_name
ORDER BY p.id, vld.location_id, v.id, vld.id;

-- 4) Logical Stock Centre result used by the corrected code.
--    A Single product is one stock identity per product/location.
--    Variable/Combo products remain variation-specific.
SELECT
    p.id AS product_id,
    p.name AS product_name,
    p.sku AS product_sku,
    p.type AS product_type,
    vld.location_id,
    bl.name AS location_name,
    CASE
        WHEN LOWER(COALESCE(p.type, 'single')) = 'single' THEN 0
        ELSE v.id
    END AS stock_variation_key,
    MIN(v.id) AS representative_variation_id,
    COUNT(DISTINCT v.id) AS internal_variation_count,
    COUNT(*) AS physical_stock_row_count,
    SUM(COALESCE(vld.qty_available, 0)) AS corrected_available_qty
FROM products p
JOIN variations v ON v.product_id = p.id
JOIN variation_location_details vld ON vld.variation_id = v.id
LEFT JOIN business_locations bl ON bl.id = vld.location_id
WHERE p.name = @pn_product_name
GROUP BY
    p.id,
    p.name,
    p.sku,
    p.type,
    vld.location_id,
    bl.name,
    CASE
        WHEN LOWER(COALESCE(p.type, 'single')) = 'single' THEN 0
        ELSE v.id
    END
ORDER BY p.id, vld.location_id, stock_variation_key;
