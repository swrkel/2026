-- HOTELMGT_035_SQL.sql
-- Hotel Management Parcel 035: Tax & Compliance
-- Global SQL only. Execute inside each tenant database as needed. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_tax_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `tax_code` VARCHAR(60) NOT NULL,
  `tax_name` VARCHAR(160) NOT NULL,
  `tax_type` VARCHAR(40) NOT NULL DEFAULT 'percentage',
  `applies_to` VARCHAR(60) NOT NULL DEFAULT 'room',
  `rate` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `inclusive_type` VARCHAR(40) NOT NULL DEFAULT 'exclusive',
  `effective_from` DATE NULL,
  `effective_to` DATE NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_tax_rules_code_unique` (`business_id`,`tax_code`),
  KEY `hm_tax_rules_scope_idx` (`business_id`,`business_location_id`,`applies_to`,`is_active`),
  KEY `hm_tax_rules_effective_idx` (`effective_from`,`effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_service_charge_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `rule_code` VARCHAR(60) NOT NULL,
  `rule_name` VARCHAR(160) NOT NULL,
  `applies_to` VARCHAR(60) NOT NULL DEFAULT 'all',
  `rate` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `distribution_method` VARCHAR(80) NULL,
  `effective_from` DATE NULL,
  `effective_to` DATE NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_service_charge_rules_code_unique` (`business_id`,`rule_code`),
  KEY `hm_service_charge_rules_scope_idx` (`business_id`,`business_location_id`,`applies_to`,`is_active`),
  KEY `hm_service_charge_rules_effective_idx` (`effective_from`,`effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_tax_invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `invoice_no` VARCHAR(60) NOT NULL,
  `source_type` VARCHAR(60) NULL,
  `source_id` BIGINT UNSIGNED NULL,
  `guest_name` VARCHAR(160) NULL,
  `invoice_date` DATE NULL,
  `net_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_rate` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `service_charge_rate` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `service_charge_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `gross_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'posted',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_tax_invoices_no_unique` (`business_id`,`invoice_no`),
  KEY `hm_tax_invoices_source_idx` (`source_type`,`source_id`),
  KEY `hm_tax_invoices_scope_idx` (`business_id`,`business_location_id`,`invoice_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_tax_period_summaries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `summary_no` VARCHAR(60) NOT NULL,
  `period_from` DATE NOT NULL,
  `period_to` DATE NOT NULL,
  `room_revenue` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `fb_revenue` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `other_revenue` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `service_charge_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'draft',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_tax_period_summaries_no_unique` (`business_id`,`summary_no`),
  KEY `hm_tax_period_summaries_scope_idx` (`business_id`,`business_location_id`,`period_from`,`period_to`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
