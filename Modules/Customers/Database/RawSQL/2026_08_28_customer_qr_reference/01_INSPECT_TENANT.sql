-- ============================================================================
-- Task 8046 Customer Reference (QR)
-- STEP 1 of 2 - INSPECT THIS TENANT
--
-- Read-only. Creates nothing, changes nothing. Run this FIRST in each tenant
-- database and read the results before running the create script.
--
-- Tenants in this system are at different schema levels, so each one is
-- checked rather than assumed.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- CHECK 1: Does the NEW table already exist here?
--
-- Expected on a fresh tenant: ALREADY INSTALLED = 0
-- If it returns 1, this tenant is already done. Skip step 2.
-- ----------------------------------------------------------------------------
SELECT
    COUNT(*) AS `already_installed_expect_0`
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'customer_qr_references';


-- ----------------------------------------------------------------------------
-- CHECK 2: Confirm the SHARED legacy table is present and untouched.
--
-- `customer_references` is the legacy "Vehicle No" table, shared by Petro,
-- PetroPD, PetroDirect, PetroGeneral, Vat, SettlementSW, PumperDashboard,
-- EVCharging, EzyInvoice and DailyCollectionSW.
--
-- Task 8046 does NOT read, write, alter or drop it. This check exists purely
-- so you can confirm afterwards that it was left alone.
--
-- Expected: 1 (it exists). Note the row count for comparison later.
-- ----------------------------------------------------------------------------
SELECT
    COUNT(*) AS `shared_legacy_table_exists_expect_1`
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'customer_references';


-- ----------------------------------------------------------------------------
-- CHECK 3: Row count in the shared legacy table.
--
-- Write this number down. After step 2, run this again - it MUST be identical.
-- If it changed, something touched the shared table and should be investigated
-- before going any further.
-- ----------------------------------------------------------------------------
SELECT COUNT(*) AS `legacy_customer_references_rows_BEFORE`
FROM `customer_references`;


-- ----------------------------------------------------------------------------
-- CHECK 4: Do the tables this feature READS from exist?
--
-- `contacts` is required - the page cannot work without it.
-- `users`    is required for the "Added By" column.
-- `categories` is optional - without it the Fuel Type dropdown simply shows
--              only "Not Known", which is a supported state, not an error.
--
-- Expected: contacts = 1, users = 1. categories may be 0 or 1.
-- ----------------------------------------------------------------------------
SELECT
    TABLE_NAME AS `dependency_table_found`
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('contacts', 'users', 'categories')
ORDER BY TABLE_NAME;


-- ----------------------------------------------------------------------------
-- CHECK 5: Is there a Fuel product category on this tenant?
--
-- Informational. Tells you in advance whether the Fuel Type dropdown will be
-- populated here, or will show only "Not Known".
--
-- If this returns nothing but you know fuel categories exist under another
-- name, note the real name/id - it goes in config('customers.fuel_category_id').
--
-- Harmless if `categories` does not exist; the query simply errors and you can
-- move on to step 2.
-- ----------------------------------------------------------------------------
SELECT
    c.id            AS `fuel_category_id`,
    c.name          AS `fuel_category_name`,
    c.business_id   AS `business_id`,
    (SELECT COUNT(*) FROM `categories` s WHERE s.parent_id = c.id) AS `sub_category_count`
FROM `categories` c
WHERE LOWER(c.name) IN ('fuel', 'fuels', 'fuel products')
  AND (c.parent_id IS NULL OR c.parent_id = 0)
ORDER BY c.business_id;
