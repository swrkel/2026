-- ============================================================================
-- Products New - SAFE one-time stock balance correction
-- Product : 2T Loose Oil - 210Ltrs
-- Expected current Stock Centre balance : -6541.200
-- Required corrected balance            : 58.800
-- Prepared : 2026-09-14
--
-- SAFETY RULES
-- 1) Matches by exact normalised product name.
-- 2) Matches only a location whose COMBINED core qty is exactly -6541.200
--    (0.0005 tolerance for decimal storage).
-- 3) Makes a change ONLY when exactly ONE product/location match is found.
-- 4) Applies the difference (+6600.000) to one existing canonical VLD row;
--    it does NOT delete transaction history or old variations.
-- 5) Writes a Products New inventory adjustment audit row when that table exists
--    in the normal Products New schema.
--
-- Run this only in the affected TENANT database.
-- ============================================================================

START TRANSACTION;

SET @pn_product_name   := '2T Loose Oil - 210Ltrs';
SET @pn_expected_qty   := -6541.200;
SET @pn_required_qty   := 58.800;
SET @pn_reference      := 'PN-STOCK-REPAIR-20260914-2T-210L';

DROP TEMPORARY TABLE IF EXISTS tmp_pn_2t_stock_fix;

CREATE TEMPORARY TABLE tmp_pn_2t_stock_fix AS
SELECT
    p.id AS product_id,
    p.business_id AS business_id,
    vld.location_id AS location_id,
    MIN(vld.id) AS canonical_vld_id,
    SUM(COALESCE(vld.qty_available, 0)) AS current_qty,
    @pn_required_qty - SUM(COALESCE(vld.qty_available, 0)) AS adjustment_qty
FROM products p
INNER JOIN variations v
    ON v.product_id = p.id
INNER JOIN variation_location_details vld
    ON vld.variation_id = v.id
WHERE LOWER(TRIM(p.name)) = LOWER(TRIM(@pn_product_name))
GROUP BY p.id, p.business_id, vld.location_id
HAVING ABS(SUM(COALESCE(vld.qty_available, 0)) - @pn_expected_qty) < 0.0005;

SET @pn_match_count := (SELECT COUNT(*) FROM tmp_pn_2t_stock_fix);

-- PRE-CHECK: this MUST show match_count = 1 for the correction to run.
SELECT
    @pn_match_count AS match_count,
    MIN(product_id) AS product_id,
    MIN(business_id) AS business_id,
    MIN(location_id) AS location_id,
    MIN(current_qty) AS current_qty,
    MIN(adjustment_qty) AS adjustment_qty,
    @pn_required_qty AS required_qty
FROM tmp_pn_2t_stock_fix;

-- Correct the real core stock balance.  The COUNT(*) guard makes this a no-op
-- if the target is absent or ambiguous.
UPDATE variation_location_details vld
INNER JOIN tmp_pn_2t_stock_fix t
    ON t.canonical_vld_id = vld.id
SET
    vld.qty_available = COALESCE(vld.qty_available, 0) + t.adjustment_qty,
    vld.updated_at = NOW()
WHERE @pn_match_count = 1;

-- Add an auditable adjustment movement.  This table/columns are part of the
-- Products New stock-history schema in the supplied module baseline.
INSERT INTO products_new_inventory_movements
(
    business_id,
    product_id,
    variation_id,
    location_id,
    movement_type,
    movement_date,
    qty,
    unit_cost,
    total_cost,
    reference_no,
    notes,
    created_by,
    created_at,
    updated_at
)
SELECT
    t.business_id,
    t.product_id,
    vld.variation_id,
    t.location_id,
    CASE WHEN t.adjustment_qty >= 0 THEN 'adjustment_in' ELSE 'adjustment_out' END,
    NOW(),
    ABS(t.adjustment_qty),
    0,
    0,
    @pn_reference,
    CONCAT(
        'One-time verified stock correction for ', @pn_product_name,
        ': ', FORMAT(t.current_qty, 3), ' -> ', FORMAT(@pn_required_qty, 3),
        '. Correct quantity supplied by management.'
    ),
    NULL,
    NOW(),
    NOW()
FROM tmp_pn_2t_stock_fix t
INNER JOIN variation_location_details vld
    ON vld.id = t.canonical_vld_id
WHERE @pn_match_count = 1
  AND ABS(t.adjustment_qty) > 0.0005;

-- POST-CHECK: after a successful run this should show 58.800 for the target row.
SELECT
    p.id AS product_id,
    p.name AS product_name,
    p.sku,
    vld.location_id,
    ROUND(SUM(COALESCE(vld.qty_available, 0)), 3) AS corrected_qty
FROM products p
INNER JOIN variations v
    ON v.product_id = p.id
INNER JOIN variation_location_details vld
    ON vld.variation_id = v.id
WHERE LOWER(TRIM(p.name)) = LOWER(TRIM(@pn_product_name))
GROUP BY p.id, p.name, p.sku, vld.location_id
ORDER BY p.id, vld.location_id;

COMMIT;

DROP TEMPORARY TABLE IF EXISTS tmp_pn_2t_stock_fix;
