-- S365 - Chequer Module: use Finance `accounts` table as Bank Account source.
-- Run inside each tenant database. No hard-coded database name.

SET @db_name := DATABASE();

-- Add account_id to cheq_cheque_books if missing. This references accounts.id where the account group is Bank.
SET @column_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'cheq_cheque_books'
      AND COLUMN_NAME = 'account_id'
);
SET @sql := IF(@column_exists = 0,
    'ALTER TABLE `cheq_cheque_books` ADD COLUMN `account_id` BIGINT UNSIGNED NULL AFTER `business_id`',
    'SELECT "cheq_cheque_books.account_id already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill only when older cheq_bank_account_id exists and matches an existing Finance account id.
SET @old_column_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'cheq_cheque_books'
      AND COLUMN_NAME = 'cheq_bank_account_id'
);
SET @sql := IF(@old_column_exists > 0,
    'UPDATE `cheq_cheque_books` cb SET cb.`account_id` = cb.`cheq_bank_account_id` WHERE cb.`account_id` IS NULL AND cb.`cheq_bank_account_id` IS NOT NULL AND EXISTS (SELECT 1 FROM `accounts` a WHERE a.`id` = cb.`cheq_bank_account_id`)',
    'SELECT "No old cheq_bank_account_id column to backfill" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add account_id index if missing.
SET @index_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'cheq_cheque_books'
      AND INDEX_NAME = 'cheq_cheque_books_account_id_index'
);
SET @sql := IF(@index_exists = 0,
    'ALTER TABLE `cheq_cheque_books` ADD INDEX `cheq_cheque_books_account_id_index` (`account_id`)',
    'SELECT "cheq_cheque_books_account_id_index already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add default bank account setting index if missing.
SET @index_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'cheq_default_settings'
      AND INDEX_NAME = 'cheq_default_settings_default_bank_account_id_index'
);
SET @sql := IF(@index_exists = 0,
    'ALTER TABLE `cheq_default_settings` ADD INDEX `cheq_default_settings_default_bank_account_id_index` (`default_bank_account_id`)',
    'SELECT "cheq_default_settings_default_bank_account_id_index already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
