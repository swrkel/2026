-- S716 - REQUIRED SW CORE SHIFT TABLES
-- 12 Sep 2026
--
-- IMPORTANT:
--   sw_shifts is a REQUIRED SW module table. This script CREATES the real table;
--   it does not bypass the table and it does not add controller fallbacks.
--
-- Safe to run on a tenant database. Existing tables/data are left unchanged.

CREATE TABLE IF NOT EXISTS `sw_shifts` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` int(10) UNSIGNED NOT NULL,
  `location_id` int(10) UNSIGNED NOT NULL,
  `sw_shift_no` varchar(60) NOT NULL,
  `shift_date` date NOT NULL,
  `shift_name` varchar(100) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `closed_at` timestamp NULL DEFAULT NULL,
  `closed_by` int(10) UNSIGNED DEFAULT NULL,
  `reopened_at` timestamp NULL DEFAULT NULL,
  `reopened_by` int(10) UNSIGNED DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sw_shifts_unique_no` (`business_id`,`location_id`,`sw_shift_no`),
  KEY `sw_shifts_business_id_index` (`business_id`),
  KEY `sw_shifts_location_id_index` (`location_id`),
  KEY `sw_shifts_shift_date_index` (`shift_date`),
  KEY `sw_shifts_status_index` (`status`),
  KEY `sw_shifts_loc_date_status` (`location_id`,`shift_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sw_shift_operators` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sw_shift_id` bigint(20) UNSIGNED NOT NULL,
  `pump_operator_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sw_shift_operator_unique` (`sw_shift_id`,`pump_operator_id`),
  KEY `sw_shift_operators_sw_shift_id_index` (`sw_shift_id`),
  KEY `sw_shift_operators_pump_operator_id_index` (`pump_operator_id`),
  CONSTRAINT `sw_shift_operators_sw_shift_id_foreign`
    FOREIGN KEY (`sw_shift_id`) REFERENCES `sw_shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sw_number_sequences` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` int(10) UNSIGNED NOT NULL,
  `location_id` int(10) UNSIGNED NOT NULL,
  `document_type` varchar(40) NOT NULL DEFAULT 'shift',
  `prefix` varchar(20) NOT NULL DEFAULT 'SW',
  `next_number` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sw_seq_unique` (`business_id`,`location_id`,`document_type`),
  KEY `sw_number_sequences_business_id_index` (`business_id`),
  KEY `sw_number_sequences_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verification: all three rows should return 1.
SELECT
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'sw_shifts') AS sw_shifts_exists,
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'sw_shift_operators') AS sw_shift_operators_exists,
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'sw_number_sequences') AS sw_number_sequences_exists;
