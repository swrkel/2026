-- MPCS F15 Daily Report - tenant database SQL
-- Safe to run repeatedly in every tenant database.

CREATE TABLE IF NOT EXISTS `mpcs_f15_daily_reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NOT NULL,
  `report_date` DATE NOT NULL,
  `form_no` VARCHAR(100) NULL,
  `changes_addition` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `changes_deduction` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `damaged` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `others` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `total_return` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `notes` TEXT NULL,
  `prepared_by` VARCHAR(191) NULL,
  `prepared_date` DATE NULL,
  `checked_by` VARCHAR(191) NULL,
  `checked_date` DATE NULL,
  `approved_by` VARCHAR(191) NULL,
  `approved_date` DATE NULL,
  `totals_json` LONGTEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mpcs_f15_daily_business_location_date_unique` (`business_id`,`location_id`,`report_date`),
  KEY `mpcs_f15_daily_business_date_index` (`business_id`,`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
