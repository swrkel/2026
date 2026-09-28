-- Petro PD-New 1.0.0
-- Standalone tenant schema. Safe to run repeatedly.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `pdnew_module_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `scope_key` VARCHAR(191) NOT NULL,
  `settlement_prefix` VARCHAR(30) NOT NULL DEFAULT 'PDN-SET-',
  `day_end_prefix` VARCHAR(30) NOT NULL DEFAULT 'PDN-DE-',
  `amount_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 4,
  `quantity_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `require_review` TINYINT(1) NOT NULL DEFAULT 1,
  `require_approval` TINYINT(1) NOT NULL DEFAULT 1,
  `require_zero_variance` TINYINT(1) NOT NULL DEFAULT 1,
  `allow_reopen` TINYINT(1) NOT NULL DEFAULT 1,
  `auto_import_closed_shifts` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `settings` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_settings_scope_uq` (`scope_key`),
  KEY `pdnew_settings_business_idx` (`business_id`,`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_number_sequences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `sequence_type` VARCHAR(50) NOT NULL,
  `scope_key` VARCHAR(191) NOT NULL,
  `prefix` VARCHAR(30) NOT NULL,
  `next_number` BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `padding` TINYINT UNSIGNED NOT NULL DEFAULT 6,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_sequences_scope_uq` (`scope_key`),
  KEY `pdnew_sequences_business_idx` (`business_id`,`sequence_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_operator_mappings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `pone_operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pone_pd_operator_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `display_name` VARCHAR(191) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `settings` LONGTEXT NULL,
  `last_synced_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_operator_profile_uq` (`business_id`,`pone_operator_profile_id`),
  KEY `pdnew_operator_scope_idx` (`business_id`,`location_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_source_imports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `pone_shift_id` BIGINT UNSIGNED NOT NULL,
  `pone_shift_uuid` CHAR(36) NULL,
  `pone_shift_number` VARCHAR(80) NOT NULL,
  `pone_operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pone_pd_operator_id` INT UNSIGNED NOT NULL,
  `source_status` VARCHAR(30) NOT NULL,
  `import_status` VARCHAR(30) NOT NULL DEFAULT 'available',
  `source_hash` CHAR(64) NOT NULL,
  `current_hash` CHAR(64) NULL,
  `snapshot_version` INT UNSIGNED NOT NULL DEFAULT 1,
  `source_closed_at` TIMESTAMP NULL DEFAULT NULL,
  `source_totals` LONGTEXT NULL,
  `settlement_id` BIGINT UNSIGNED NULL,
  `imported_at` TIMESTAMP NULL DEFAULT NULL,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `last_error` TEXT NULL,
  `imported_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_source_shift_uq` (`business_id`,`pone_shift_id`),
  KEY `pdnew_source_status_idx` (`business_id`,`location_id`,`import_status`),
  KEY `pdnew_source_settlement_idx` (`settlement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_source_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `source_import_id` BIGINT UNSIGNED NOT NULL,
  `pone_shift_id` BIGINT UNSIGNED NOT NULL,
  `version` INT UNSIGNED NOT NULL,
  `source_hash` CHAR(64) NOT NULL,
  `snapshot` LONGTEXT NOT NULL,
  `captured_at` TIMESTAMP NOT NULL,
  `captured_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_snapshot_version_uq` (`source_import_id`,`version`),
  KEY `pdnew_snapshot_shift_idx` (`business_id`,`pone_shift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
