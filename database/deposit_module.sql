-- Raw SQL to create tables for the new Deposits Module
-- All tables are prefixed with "deposit_"

-- 1. Table for Deposit Settings
CREATE TABLE IF NOT EXISTS `deposit_settings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `business_id` INT UNSIGNED NOT NULL,
    `settings_key` VARCHAR(100) NOT NULL,
    `settings_value` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `business_key_unique` (`business_id`, `settings_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table for Deposit Types
CREATE TABLE IF NOT EXISTS `deposit_types` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `business_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `period` ENUM('Daily', 'Weekly', 'Monthly', 'Yearly') NOT NULL,
    `period_value` INT NOT NULL,
    `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
    `created_by` INT UNSIGNED NOT NULL,
    `last_edited_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table for Deposit Type Activities Log
CREATE TABLE IF NOT EXISTS `deposit_type_activities` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `deposit_type_id` BIGINT UNSIGNED NOT NULL,
    `original_added_by` VARCHAR(150) NOT NULL,
    `changed_by_user` VARCHAR(150) NOT NULL,
    `details` TEXT NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`deposit_type_id`) REFERENCES `deposit_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table for Deposit Records (Actual Deposits)
CREATE TABLE IF NOT EXISTS `deposit_records` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `business_id` INT UNSIGNED NOT NULL,
    `location_id` INT UNSIGNED NOT NULL,
    `deposit_number` VARCHAR(100) NOT NULL,
    `contact_id` INT UNSIGNED NOT NULL, -- Bank Customer reference
    `current_loan_id` VARCHAR(100) NULL,
    `deposit_type_id` BIGINT UNSIGNED NOT NULL,
    `deposit_period` ENUM('Daily', 'Weekly', 'Monthly', 'Yearly') NOT NULL,
    `deposit_period_value` INT NOT NULL,
    `interest_per` VARCHAR(50) NULL,
    `total_interest` DECIMAL(15, 2) DEFAULT 0.00,
    `currency` VARCHAR(10) NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `payment_method` ENUM('Cash', 'Card', 'Cheque', 'Online Transfer') NOT NULL,
    `card_number` VARCHAR(50) NULL,
    `slip_number` VARCHAR(100) NULL,
    `bank` VARCHAR(150) NULL,
    `cheque_no` VARCHAR(50) NULL,
    `cheque_date` DATE NULL,
    `deposited_bank_id` INT UNSIGNED NULL, -- Account table reference for Online Transfer
    `transaction_reference` VARCHAR(150) NULL, -- Transfer ID
    `attachment` VARCHAR(255) NULL,
    `note` TEXT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`deposit_type_id`) REFERENCES `deposit_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexing for high-performance searches
CREATE INDEX `idx_deposit_settings_business` ON `deposit_settings` (`business_id`);
CREATE INDEX `idx_deposit_types_business` ON `deposit_types` (`business_id`);
CREATE INDEX `idx_deposit_records_business` ON `deposit_records` (`business_id`);
CREATE INDEX `idx_deposit_records_location` ON `deposit_records` (`location_id`);
CREATE INDEX `idx_deposit_records_contact` ON `deposit_records` (`contact_id`);
CREATE INDEX `idx_deposit_records_number` ON `deposit_records` (`deposit_number`);

-- Default Seed for Deposit Types (for default business_id = 1)
-- Saving Deposits
INSERT INTO `deposit_types` (`business_id`, `name`, `period`, `period_value`, `status`, `created_by`) VALUES
(1, 'Saving Deposits', 'Daily', 1, 'Active', 1),
(1, 'Fix Deposits', 'Monthly', 12, 'Active', 1),
(1, 'Demand Deposits', 'Monthly', 1, 'Active', 1);
