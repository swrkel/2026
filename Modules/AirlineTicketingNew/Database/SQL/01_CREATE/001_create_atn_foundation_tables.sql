-- ATN-001 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `key` VARCHAR(100) NOT NULL,
  `value_text` TEXT NULL,
  `value_json` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_settings_scope_key_unique` (`business_id`,`business_location_id`,`store_id`,`key`),
  KEY `atn_settings_business_active_idx` (`business_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_sequences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `document_type` VARCHAR(50) NOT NULL,
  `prefix` VARCHAR(20) NULL,
  `next_number` BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `padding` INT UNSIGNED NOT NULL DEFAULT 6,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_sequences_scope_type_unique` (`business_id`,`business_location_id`,`store_id`,`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `event` VARCHAR(80) NOT NULL,
  `auditable_type` VARCHAR(190) NULL,
  `auditable_id` BIGINT UNSIGNED NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `atn_audit_lookup_idx` (`business_id`,`auditable_type`,`auditable_id`),
  KEY `atn_audit_business_date_idx` (`business_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
