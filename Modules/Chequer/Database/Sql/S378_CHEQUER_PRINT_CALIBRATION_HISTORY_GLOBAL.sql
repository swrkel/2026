-- S378 Chequer Module - Print Calibration + Print History global SQL
-- Run against each tenant database. No database name is hard-coded.

CREATE TABLE IF NOT EXISTS `cheq_print_calibrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `bank_account_id` INT UNSIGNED NULL,
  `cheq_template_id` BIGINT UNSIGNED NULL,
  `profile_name` VARCHAR(191) NOT NULL,
  `printer_name` VARCHAR(191) NULL,
  `x_offset_mm` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `y_offset_mm` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `date_x_offset_mm` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `date_y_offset_mm` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payee_x_offset_mm` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payee_y_offset_mm` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount_x_offset_mm` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount_y_offset_mm` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `words_x_offset_mm` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `words_y_offset_mm` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `signature_x_offset_mm` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `signature_y_offset_mm` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `scale_percent` DECIMAL(10,2) NOT NULL DEFAULT 100.00,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `cheq_print_calibrations_business_id_index` (`business_id`),
  KEY `cheq_print_calibrations_bank_account_id_index` (`bank_account_id`),
  KEY `cheq_print_calibrations_template_id_index` (`cheq_template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cheq_print_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `cheq_cheque_id` BIGINT UNSIGNED NULL,
  `print_action` VARCHAR(80) NULL,
  `printer_name` VARCHAR(191) NULL,
  `template_name` VARCHAR(191) NULL,
  `calibration_profile` VARCHAR(191) NULL,
  `reprint_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `printed_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `cheq_print_history_business_id_index` (`business_id`),
  KEY `cheq_print_history_cheq_cheque_id_index` (`cheq_cheque_id`),
  KEY `cheq_print_history_printed_by_index` (`printed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
