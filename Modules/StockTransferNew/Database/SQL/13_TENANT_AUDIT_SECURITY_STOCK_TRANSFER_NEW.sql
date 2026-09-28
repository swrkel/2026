-- STN_012 Tenant SQL - Audit and Security Hardening
CREATE TABLE IF NOT EXISTS `stn_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NULL,
  `store_id` INT UNSIGNED NULL,
  `event` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(100) NOT NULL,
  `entity_id` BIGINT UNSIGNED NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `meta` JSON NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(64) NULL,
  `user_agent` VARCHAR(500) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `stn_audit_business_event_idx` (`business_id`,`event`),
  KEY `stn_audit_entity_idx` (`entity_type`,`entity_id`),
  KEY `stn_audit_user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stn_transfer_locks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `transfer_id` BIGINT UNSIGNED NOT NULL,
  `lock_type` VARCHAR(60) NOT NULL,
  `reason` VARCHAR(255) NULL,
  `locked_by` BIGINT UNSIGNED NULL,
  `locked_at` DATETIME NULL,
  `released_by` BIGINT UNSIGNED NULL,
  `released_at` DATETIME NULL,
  `release_reason` VARCHAR(255) NULL,
  `meta` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `stn_lock_transfer_idx` (`transfer_id`),
  KEY `stn_lock_active_idx` (`business_id`,`released_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stn_duplicate_keys` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `duplicate_key` VARCHAR(191) NOT NULL,
  `payload_hash` VARCHAR(191) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `expires_at` DATETIME NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `meta` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stn_duplicate_unique` (`business_id`,`action`,`duplicate_key`,`payload_hash`),
  KEY `stn_duplicate_status_idx` (`status`,`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stn_integrity_checks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `checked_by` BIGINT UNSIGNED NULL,
  `checked_at` DATETIME NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'pending',
  `total_findings` INT UNSIGNED NOT NULL DEFAULT 0,
  `findings` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `stn_integrity_business_idx` (`business_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'stocktransfernew.audit', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stocktransfernew.audit');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'stocktransfernew.security', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stocktransfernew.security');
