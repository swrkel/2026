-- Reconcile product stock to tank totals
--
--
-- WHAT THIS IS FOR
--
-- Stock Center and the tank list disagree. On the reported tenant:
--
--     Lanka Auto Diesel   Stock Center 262,843.000   tanks   3,215.00
--     Lanka Petrol 92     Stock Center 407,534.000   tanks   1,565.00
--
-- Fuel exists ONLY in tanks, so the tank total is the truth and any difference
-- is drift.
--
--
-- WHERE THE DRIFT CAME FROM
--
-- Dip resets used to correct the tank balance ONLY. The comment that stood in
-- DipManagementController said so explicitly - the reset "must not touch product
-- stock". So fuel_tanks.current_balance was set to the counted quantity while
-- variation_location_details.qty_available kept the old number, and the two
-- moved further apart with every reset.
--
-- IS1970 fixed that: a reset now applies its adjustment through the same path a
-- manual stock adjustment uses, so the two stay together FROM NOW ON.
--
-- But that fix does not repair history. Every reset performed before it left a
-- difference behind, and those do not self-correct: the next reset adjusts by
-- the new difference, not back to reality. This script clears the accumulated
-- gap once, so the corrected behaviour starts from a correct figure.
--
--
-- WHAT IT CHANGES
--
-- For every product held in a tank, at every location, it sets
-- variation_location_details.qty_available to the SUM of that product's tank
-- balances at that location.
--
-- The sum matters: one product can sit in several tanks, so no single tank is
-- the answer.
--
-- Products NOT held in any tank are untouched. Only rows that actually differ
-- are written, so re-running changes nothing.
--
--
-- BEFORE YOU RUN IT
--
-- TAKE A BACKUP. This rewrites stock quantities.
--
--     mysqldump -u USER -p DBNAME variation_location_details > vld_backup.sql
--
-- RUN ONCE PER TENANT DATABASE.


-- ---------------------------------------------------------------------------
-- STEP 1 - see what will change, before changing anything
-- ---------------------------------------------------------------------------
--
-- Run this on its own first. Every row it returns is a product whose recorded
-- stock does not match its tanks. Check a few against the tank list on screen
-- before going further - if the "tank_total" column does not match what the
-- Dip screen shows for that product, STOP and tell me.

SELECT
    p.id                AS product_id,
    p.name              AS product_name,
    bl.name             AS location_name,
    t.location_id,
    ROUND(t.tank_total, 3)      AS tank_total,
    ROUND(vld.qty_available, 3) AS current_stock,
    ROUND(t.tank_total - vld.qty_available, 3) AS difference
FROM (
        SELECT `product_id`, `location_id`, SUM(`current_balance`) AS tank_total
          FROM `fuel_tanks`
         WHERE `product_id` IS NOT NULL
           AND `location_id` IS NOT NULL
         GROUP BY `product_id`, `location_id`
     ) t
JOIN `products` p                     ON p.`id` = t.`product_id`
JOIN `variations` v                   ON v.`product_id` = p.`id`
JOIN `variation_location_details` vld ON vld.`variation_id` = v.`id`
                                     AND vld.`location_id` = t.`location_id`
LEFT JOIN `business_locations` bl     ON bl.`id` = t.`location_id`
WHERE ABS(t.tank_total - vld.qty_available) > 0.0005
ORDER BY ABS(t.tank_total - vld.qty_available) DESC;


-- ---------------------------------------------------------------------------
-- STEP 2 - apply the correction
-- ---------------------------------------------------------------------------
--
-- Only run this once Step 1 has been reviewed and the tank totals look right.
--
-- A note on the single-variation assumption: fuel products have one variation,
-- so the tank total maps to one variation_location_details row. If a fuel
-- product ever had several variations at one location, this would write the
-- full tank total to EACH of them. Step 3 below detects that case - run it
-- first if you are unsure.

UPDATE `variation_location_details` vld
  JOIN `variations` v ON v.`id` = vld.`variation_id`
  JOIN (
        SELECT `product_id`, `location_id`, SUM(`current_balance`) AS tank_total
          FROM `fuel_tanks`
         WHERE `product_id` IS NOT NULL
           AND `location_id` IS NOT NULL
         GROUP BY `product_id`, `location_id`
       ) t
    ON t.`product_id` = v.`product_id`
   AND t.`location_id` = vld.`location_id`
   SET vld.`qty_available` = t.tank_total
 WHERE ABS(t.tank_total - vld.`qty_available`) > 0.0005;


-- ---------------------------------------------------------------------------
-- STEP 3 - safety check for multi-variation fuel products
-- ---------------------------------------------------------------------------
--
-- Expected: ZERO rows. Any row returned is a product held in a tank that has
-- more than one variation, where Step 2 would write the same total to each.
-- If this returns anything, do NOT run Step 2 - send me the output instead.

SELECT p.id, p.name, COUNT(DISTINCT v.id) AS variation_count
  FROM `products` p
  JOIN `variations` v ON v.`product_id` = p.`id`
 WHERE p.`id` IN (SELECT DISTINCT `product_id` FROM `fuel_tanks` WHERE `product_id` IS NOT NULL)
 GROUP BY p.`id`, p.`name`
HAVING COUNT(DISTINCT v.id) > 1;


-- ---------------------------------------------------------------------------
-- STEP 4 - verify
-- ---------------------------------------------------------------------------
--
-- Re-run STEP 1. It should now return no rows: every tank-held product matches
-- its tank total at every location.
--
-- Then open Stock Center and the Dip screen and confirm the same product shows
-- the same quantity on both.
