CREATE TABLE IF NOT EXISTS `disnew_production_exceptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `exception_type` VARCHAR(80) NOT NULL,
  `reference_no` VARCHAR(191) NULL,
  `message` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `assigned_to` INT UNSIGNED NULL,
  `resolution_note` TEXT NULL,
  `resolved_at` TIMESTAMP NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_disnew_pe_business_status` (`business_id`,`status`),
  KEY `idx_disnew_pe_type` (`exception_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_production_audits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `entity_type` VARCHAR(80) NOT NULL,
  `entity_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(80) NOT NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_disnew_pa_business_entity` (`business_id`,`entity_type`,`entity_id`),
  KEY `idx_disnew_pa_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_super_admin_monitors` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `metric_key` VARCHAR(80) NOT NULL,
  `metric_value` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `snapshot_date` DATE NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_disnew_sam_business_metric_date` (`business_id`,`metric_key`,`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
