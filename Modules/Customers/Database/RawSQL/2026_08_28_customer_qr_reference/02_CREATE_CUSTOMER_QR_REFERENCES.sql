-- ============================================================================
-- Task 8046 Customer Reference (QR)
-- STEP 2 of 2 - CREATE THE NEW TABLE
--
-- Run AFTER 01_INSPECT_TENANT.sql, in each tenant database.
-- phpMyAdmin / MariaDB compatible. Safe to re-run.
--
-- WHAT THIS DOES
--     Creates ONE new table: customer_qr_references.
--
-- WHAT THIS DOES NOT DO
--     It does not touch `customer_references`. That table is shared by a dozen
--     other modules and is not part of this feature. The name appears in this
--     file only inside these comments - there is no statement that reads,
--     writes, alters or drops it.
--
--     There is deliberately no DROP script in this folder. To undo, drop
--     customer_qr_references by hand:
--         DROP TABLE IF EXISTS `customer_qr_references`;
--     Nothing else reads that table, so dropping it affects only this feature.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `customer_qr_references` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id`        INT UNSIGNED NOT NULL,

  -- contacts.id
  `customer_id`        INT UNSIGNED NOT NULL,

  `reference_datetime` DATETIME NULL,

  `is_vehicle`         TINYINT(1) NOT NULL DEFAULT 0,
  `reference_no`       VARCHAR(191) NOT NULL,

  -- categories.id (product sub-category). NULL = the system default
  -- "Not Known", which stays selectable even with no Fuel category present.
  `fuel_type_id`       INT UNSIGNED NULL,

  -- Name snapshot, so a later category rename cannot change what an
  -- already-printed QR code says.
  `fuel_type_name`     VARCHAR(191) NULL,

  -- The text encoded into the QR. The image itself is rendered on demand and
  -- is not stored.
  `qr_payload`         TEXT NULL,

  -- Opaque handle used in URLs so sequential row ids are not exposed.
  `qr_token`           VARCHAR(64) NULL,

  `is_active`          TINYINT(1) NOT NULL DEFAULT 1,

  `created_by`         INT UNSIGNED NULL,
  `updated_by`         INT UNSIGNED NULL,

  `created_at`         TIMESTAMP NULL,
  `updated_at`         TIMESTAMP NULL,
  `deleted_at`         TIMESTAMP NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_qr_references_qr_token_unique` (`qr_token`),
  KEY `customer_qr_references_business_id_index` (`business_id`),
  KEY `customer_qr_references_customer_id_index` (`customer_id`),
  KEY `customer_qr_references_fuel_type_id_index` (`fuel_type_id`),
  KEY `customer_qr_references_created_by_index` (`created_by`),
  KEY `cus_qr_ref_biz_customer_idx` (`business_id`,`customer_id`),
  KEY `cus_qr_ref_biz_datetime_idx` (`business_id`,`reference_datetime`),
  KEY `cus_qr_ref_biz_refno_idx` (`business_id`,`reference_no`),
  KEY `cus_qr_ref_biz_active_idx` (`business_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- No foreign keys are declared, matching the other Customers module tables.
-- customer_id points at contacts.id and fuel_type_id at categories.id, but
-- both are enforced in application code, so installing this table cannot fail
-- on a tenant carrying legacy orphan rows.
-- ----------------------------------------------------------------------------


-- ============================================================================
-- VERIFY - run these after the CREATE above.
-- ============================================================================

-- Expected: 1
SELECT COUNT(*) AS `new_table_created_expect_1`
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'customer_qr_references';

-- Expected: 19 columns
SELECT COUNT(*) AS `column_count_expect_19`
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'customer_qr_references';

-- MUST match the number recorded in step 1, CHECK 3.
-- If it differs, stop and investigate before continuing.
SELECT COUNT(*) AS `legacy_customer_references_rows_AFTER`
FROM `customer_references`;
