-- ============================================================================
-- Expenses New - Core Column Repair (idempotent)
-- Run AFTER 00_EXPENSES_NEW_CORE_SCHEMA_IDEMPOTENT.sql on each tenant database.
-- Adds missing expnew_expenses columns without removing or overwriting data.
-- ============================================================================

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'business_id'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'location_id'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `location_id` BIGINT UNSIGNED NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'expense_no'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `expense_no` VARCHAR(100) NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'expense_date'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `expense_date` DATE NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'category_id'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `category_id` BIGINT UNSIGNED NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'payee_id'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `payee_id` BIGINT UNSIGNED NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'expense_account_id'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `expense_account_id` BIGINT UNSIGNED NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;


SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'accounting_module'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `accounting_module` VARCHAR(50) NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'total_amount'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'paid_amount'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'due_amount'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `due_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'balance_amount'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `balance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'payment_status'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `payment_status` VARCHAR(30) NOT NULL DEFAULT ''due'''
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'payment_method'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `payment_method` VARCHAR(40) NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'reference_no'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `reference_no` VARCHAR(191) NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'cheque_no'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `cheque_no` VARCHAR(191) NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'bank_account_id'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `bank_account_id` BIGINT UNSIGNED NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'card_no'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `card_no` VARCHAR(191) NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'notes'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `notes` TEXT NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'status'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `status` VARCHAR(40) NOT NULL DEFAULT ''active'''
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'created_by'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `created_by` BIGINT UNSIGNED NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'updated_by'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `updated_by` BIGINT UNSIGNED NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'created_at'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

SET @expnew_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expnew_expenses' AND COLUMN_NAME = 'updated_at'),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL'
);
PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;

UPDATE `expnew_expenses`
SET `balance_amount` = `due_amount`
WHERE `balance_amount` <> `due_amount` OR `balance_amount` IS NULL;
