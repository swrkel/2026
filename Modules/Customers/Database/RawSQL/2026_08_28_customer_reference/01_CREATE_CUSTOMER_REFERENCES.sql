-- Task 8046 Customer Reference - table creation
-- phpMyAdmin / MariaDB compatible. Run in each tenant database.
--
-- Safe to re-run: CREATE TABLE IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS `customer_references` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `reference_datetime` DATETIME NULL,
  `is_vehicle` TINYINT(1) NOT NULL DEFAULT 0,
  `reference_no` VARCHAR(191) NOT NULL,
  `fuel_type_id` INT UNSIGNED NULL,
  `fuel_type_name` VARCHAR(191) NULL,
  `qr_payload` TEXT NULL,
  `qr_token` VARCHAR(64) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_references_qr_token_unique` (`qr_token`),
  KEY `customer_references_business_id_index` (`business_id`),
  KEY `customer_references_customer_id_index` (`customer_id`),
  KEY `customer_references_fuel_type_id_index` (`fuel_type_id`),
  KEY `customer_references_created_by_index` (`created_by`),
  KEY `cus_ref_biz_customer_idx` (`business_id`,`customer_id`),
  KEY `cus_ref_biz_datetime_idx` (`business_id`,`reference_datetime`),
  KEY `cus_ref_biz_refno_idx` (`business_id`,`reference_no`),
  KEY `cus_ref_biz_active_idx` (`business_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
