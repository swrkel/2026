-- Reports - Other / Tenant schema
-- Prefix: reo_
-- Compatible with MySQL 5.7+ / MariaDB versions that support InnoDB + JSON.

CREATE TABLE IF NOT EXISTS `reo_sources` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `scope_key` VARCHAR(120) NOT NULL,
  `source_name` VARCHAR(120) NOT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_by_name` VARCHAR(190) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reo_sources_scope_idx` (`business_id`,`location_id`,`store_id`),
  KEY `reo_sources_name_idx` (`scope_key`,`source_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reo_source_mappings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_id` BIGINT UNSIGNED NOT NULL,
  `item_type` VARCHAR(30) NOT NULL,
  `item_id` BIGINT UNSIGNED NOT NULL,
  `item_name_snapshot` VARCHAR(190) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reo_src_map_unique` (`source_id`,`item_type`,`item_id`),
  KEY `reo_src_map_item_idx` (`item_type`,`item_id`),
  CONSTRAINT `reo_src_map_source_fk` FOREIGN KEY (`source_id`) REFERENCES `reo_sources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reo_number_sequences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `scope_key` VARCHAR(120) NOT NULL,
  `document_key` VARCHAR(60) NOT NULL,
  `prefix` VARCHAR(30) NULL,
  `next_number` BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reo_number_sequence_scope_unique` (`scope_key`,`document_key`),
  KEY `reo_number_sequence_scope_idx` (`business_id`,`location_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reo_share_links` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `token` VARCHAR(64) NOT NULL,
  `channel` VARCHAR(20) NOT NULL DEFAULT 'link',
  `report_key` VARCHAR(100) NOT NULL,
  `payload` JSON NULL,
  `relative_path` VARCHAR(255) NOT NULL,
  `download_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(120) NOT NULL DEFAULT 'application/octet-stream',
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `downloads` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_downloaded_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reo_share_links_token_unique` (`token`),
  KEY `reo_share_report_idx` (`business_id`,`report_key`),
  KEY `reo_share_expires_idx` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8063 Reports - Other / Cash Receipt transaction tables
CREATE TABLE IF NOT EXISTS `reo_receipts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `scope_key` VARCHAR(120) NOT NULL,
  `receipt_date` DATE NOT NULL,
  `receipt_no` VARCHAR(80) NOT NULL,
  `source_id` BIGINT UNSIGNED NOT NULL,
  `source_name` VARCHAR(120) NOT NULL,
  `membership_no` VARCHAR(120) NULL,
  `membership_is_manual` TINYINT(1) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(22,8) NOT NULL DEFAULT 0,
  `amount_in_words` TEXT NULL,
  `entered_by` BIGINT UNSIGNED NULL,
  `entered_by_name` VARCHAR(190) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reo_receipt_scope_no_unique` (`scope_key`,`receipt_no`),
  UNIQUE KEY `reo_receipt_source_date_unique` (`scope_key`,`receipt_date`,`source_id`),
  KEY `reo_receipt_scope_date_idx` (`business_id`,`location_id`,`store_id`,`receipt_date`),
  KEY `reo_receipt_source_idx` (`source_id`),
  CONSTRAINT `reo_receipt_source_fk` FOREIGN KEY (`source_id`) REFERENCES `reo_sources` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reo_receipt_details` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `receipt_id` BIGINT UNSIGNED NOT NULL,
  `item_type` VARCHAR(30) NOT NULL,
  `item_id` BIGINT UNSIGNED NOT NULL,
  `source_detail` VARCHAR(190) NOT NULL,
  `amount` DECIMAL(22,8) NOT NULL DEFAULT 0,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reo_receipt_details_order_idx` (`receipt_id`,`sort_order`),
  CONSTRAINT `reo_receipt_details_receipt_fk` FOREIGN KEY (`receipt_id`) REFERENCES `reo_receipts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reo_receipt_cheques` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `receipt_id` BIGINT UNSIGNED NOT NULL,
  `external_payment_id` VARCHAR(100) NULL,
  `cheque_number` VARCHAR(190) NULL,
  `bank_name` VARCHAR(190) NULL,
  `cheque_date` DATE NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reo_receipt_cheques_receipt_idx` (`receipt_id`),
  CONSTRAINT `reo_receipt_cheques_receipt_fk` FOREIGN KEY (`receipt_id`) REFERENCES `reo_receipts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reo_receipt_audits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `receipt_id` BIGINT UNSIGNED NOT NULL,
  `field_name` VARCHAR(80) NOT NULL,
  `field_label` VARCHAR(120) NOT NULL,
  `old_value` TEXT NULL,
  `new_value` TEXT NULL,
  `edited_by` BIGINT UNSIGNED NULL,
  `edited_by_name` VARCHAR(190) NULL,
  `edited_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  KEY `reo_receipt_audits_receipt_idx` (`receipt_id`,`edited_at`),
  CONSTRAINT `reo_receipt_audits_receipt_fk` FOREIGN KEY (`receipt_id`) REFERENCES `reo_receipts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
