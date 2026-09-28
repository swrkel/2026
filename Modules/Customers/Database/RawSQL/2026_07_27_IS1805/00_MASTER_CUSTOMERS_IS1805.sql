-- IS1805 Customers Module - Master Data tables
-- phpMyAdmin / MariaDB compatible. Run in each tenant database.

CREATE TABLE IF NOT EXISTS `customer_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `customer_types_business_id_index` (`business_id`),
  KEY `customer_types_biz_name_idx` (`business_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `customer_categories_business_id_index` (`business_id`),
  KEY `customer_categories_biz_name_idx` (`business_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_classifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `customer_classifications_business_id_index` (`business_id`),
  KEY `customer_classifications_biz_name_idx` (`business_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_custom_fields` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `customer_custom_fields_business_id_index` (`business_id`),
  KEY `customer_custom_fields_biz_name_idx` (`business_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_module_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `value` TEXT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `customer_module_settings_business_id_index` (`business_id`),
  KEY `cus_module_settings_biz_name_idx` (`business_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_opening_balance_adjustments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `reference_no` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `transaction_date` DATE NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `customer_opening_balance_adjustments_business_id_index` (`business_id`),
  KEY `cus_opening_biz_date_idx` (`business_id`,`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- IS1805 Customers Module - Workflow tables
-- phpMyAdmin / MariaDB compatible. Run in each tenant database.

CREATE TABLE IF NOT EXISTS `customer_workflow_approvals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `contact_id` INT UNSIGNED NOT NULL,
  `workflow_type` VARCHAR(50) NOT NULL DEFAULT 'customer_approval',
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `current_value` VARCHAR(191) NULL,
  `requested_value` VARCHAR(191) NULL,
  `reason` TEXT NULL,
  `remarks` TEXT NULL,
  `requested_by` INT UNSIGNED NULL,
  `approved_by` INT UNSIGNED NULL,
  `approved_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `customer_workflow_approvals_business_id_index` (`business_id`),
  KEY `customer_workflow_approvals_contact_id_index` (`contact_id`),
  KEY `cus_workflow_biz_type_status_idx` (`business_id`,`workflow_type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_workflow_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `contact_id` INT UNSIGNED NOT NULL,
  `workflow_type` VARCHAR(50) NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `old_value` VARCHAR(191) NULL,
  `new_value` VARCHAR(191) NULL,
  `remarks` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `customer_workflow_histories_business_id_index` (`business_id`),
  KEY `customer_workflow_histories_contact_id_index` (`contact_id`),
  KEY `cus_history_biz_type_idx` (`business_id`,`workflow_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
