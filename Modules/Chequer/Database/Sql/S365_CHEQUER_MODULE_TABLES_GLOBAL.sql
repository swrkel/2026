-- S365 Chequer Module global SQL, tenant-safe. Run inside each tenant database.
-- Bank accounts are NOT duplicated. Chequer uses existing `accounts` rows where account group is Bank.

CREATE TABLE IF NOT EXISTS `cheq_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `template_name` VARCHAR(191) NOT NULL,
  `bank_name` VARCHAR(191) NULL,
  `paper_width` DECIMAL(10,2) NULL,
  `paper_height` DECIMAL(10,2) NULL,
  `field_map` JSON NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `cheq_templates_business_id_index` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cheq_cheque_books` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `account_id` BIGINT UNSIGNED NOT NULL,
  `book_no` VARCHAR(100) NOT NULL,
  `start_no` BIGINT NOT NULL,
  `end_no` BIGINT NOT NULL,
  `next_no` BIGINT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `cheq_cheque_books_business_id_index` (`business_id`),
  KEY `cheq_cheque_books_account_id_index` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `cheq_cheque_leaves` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `cheq_cheque_book_id` BIGINT UNSIGNED NOT NULL,
  `cheque_no` VARCHAR(100) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'available',
  `cheq_cheque_id` BIGINT UNSIGNED NULL,
  `issued_at` TIMESTAMP NULL,
  `cancelled_at` TIMESTAMP NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cheq_leaves_book_cheque_unique` (`cheq_cheque_book_id`, `cheque_no`),
  KEY `cheq_leaves_business_id_index` (`business_id`),
  KEY `cheq_leaves_status_index` (`status`),
  KEY `cheq_leaves_cheq_cheque_id_index` (`cheq_cheque_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cheq_cheques` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `cheq_cheque_book_id` BIGINT UNSIGNED NOT NULL,
  `cheq_cheque_leaf_id` BIGINT UNSIGNED NULL,
  `cheque_no` VARCHAR(100) NOT NULL,
  `cheque_date` DATE NOT NULL,
  `payee_name` VARCHAR(191) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `payment_type` VARCHAR(50) NOT NULL,
  `memo` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `printed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `cheq_cheques_business_id_index` (`business_id`),
  KEY `cheq_cheques_book_id_index` (`cheq_cheque_book_id`),
  KEY `cheq_cheques_leaf_id_index` (`cheq_cheque_leaf_id`),
  KEY `cheq_cheques_cheque_no_index` (`cheque_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cheq_default_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `default_bank_account_id` BIGINT UNSIGNED NULL COMMENT 'references accounts.id where account group is Bank',
  `default_template_id` BIGINT UNSIGNED NULL,
  `default_currency` VARCHAR(20) NULL,
  `default_font` VARCHAR(191) NULL,
  `default_font_size` DECIMAL(8,2) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cheq_default_settings_business_id_unique` (`business_id`),
  KEY `cheq_default_settings_default_bank_account_id_index` (`default_bank_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cheq_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(191) NOT NULL,
  `entity` VARCHAR(191) NULL,
  `entity_id` BIGINT UNSIGNED NULL,
  `payload` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `cheq_audit_logs_business_id_index` (`business_id`),
  KEY `cheq_audit_logs_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
