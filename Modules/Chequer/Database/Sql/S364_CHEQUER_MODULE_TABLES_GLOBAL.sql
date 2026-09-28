-- S364 Chequer Module v1.0 Foundation Tables
-- Global SQL: run inside each tenant database. No database name is hard-coded.

CREATE TABLE IF NOT EXISTS `cheq_bank_accounts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `bank_name` VARCHAR(191) NOT NULL,
  `account_name` VARCHAR(191) NOT NULL,
  `account_number` VARCHAR(100) NULL,
  `branch` VARCHAR(191) NULL,
  `currency` VARCHAR(20) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cheq_bank_accounts_business_id_index` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cheq_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `template_name` VARCHAR(191) NOT NULL,
  `bank_name` VARCHAR(191) NULL,
  `paper_width` DECIMAL(10,2) NULL,
  `paper_height` DECIMAL(10,2) NULL,
  `field_map` JSON NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cheq_templates_business_id_index` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cheq_cheque_books` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `cheq_bank_account_id` BIGINT UNSIGNED NOT NULL,
  `book_no` VARCHAR(100) NOT NULL,
  `start_no` BIGINT NOT NULL,
  `end_no` BIGINT NOT NULL,
  `next_no` BIGINT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cheq_cheque_books_business_id_index` (`business_id`),
  KEY `cheq_cheque_books_account_index` (`cheq_bank_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cheq_cheques` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `cheq_cheque_book_id` BIGINT UNSIGNED NOT NULL,
  `cheque_no` VARCHAR(100) NOT NULL,
  `cheque_date` DATE NOT NULL,
  `payee_name` VARCHAR(191) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `payment_type` VARCHAR(50) NOT NULL,
  `memo` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `printed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cheq_cheques_business_id_index` (`business_id`),
  KEY `cheq_cheques_book_id_index` (`cheq_cheque_book_id`),
  KEY `cheq_cheques_cheque_no_index` (`cheque_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cheq_default_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `default_bank_account_id` BIGINT UNSIGNED NULL,
  `default_template_id` BIGINT UNSIGNED NULL,
  `default_currency` VARCHAR(20) NULL,
  `default_font` VARCHAR(191) NULL,
  `default_font_size` DECIMAL(8,2) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cheq_default_settings_business_id_unique` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cheq_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(191) NOT NULL,
  `entity` VARCHAR(191) NULL,
  `entity_id` BIGINT UNSIGNED NULL,
  `payload` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cheq_audit_logs_business_id_index` (`business_id`),
  KEY `cheq_audit_logs_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
