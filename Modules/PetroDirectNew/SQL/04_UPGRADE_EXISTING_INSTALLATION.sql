-- Petro Direct-New idempotent upgrade for earlier preview installations.
-- Run in each tenant database after taking a database backup.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `pdirectnew_pumper_day_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `operator_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `entry_date` date NOT NULL,
  `entry_type` varchar(50) NOT NULL DEFAULT 'general',
  `reference_no` varchar(190) DEFAULT NULL,
  `amount` decimal(22,4) NOT NULL DEFAULT 0,
  `quantity` decimal(22,3) NOT NULL DEFAULT 0,
  `note` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_day_entry_scope_idx` (`business_id`,`location_id`,`entry_date`),
  KEY `pdn_day_entry_operator_idx` (`operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_unload_stocks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `unload_no` varchar(80) NOT NULL,
  `operator_id` bigint unsigned DEFAULT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `reference_no` varchar(190) DEFAULT NULL,
  `unload_date` date NOT NULL,
  `total_qty` decimal(22,3) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'completed',
  `note` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdn_unload_no_uq` (`business_id`,`unload_no`),
  KEY `pdn_unload_scope_idx` (`business_id`,`location_id`,`unload_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_unload_stock_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `unload_stock_id` bigint unsigned NOT NULL,
  `tank_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(22,3) NOT NULL,
  `unit_cost` decimal(22,4) NOT NULL DEFAULT 0,
  `line_total` decimal(22,4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_unload_line_parent_idx` (`unload_stock_id`),
  KEY `pdn_unload_line_tank_idx` (`tank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MySQL/MariaDB portable conditional column addition.
SET @pdn_sql := IF(
    EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_adjustments')
    AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_adjustments' AND column_name = 'operator_id'),
    'ALTER TABLE `pdirectnew_adjustments` ADD COLUMN `operator_id` bigint unsigned NULL AFTER `settlement_id`, ADD KEY `pdn_adjustment_operator_idx` (`operator_id`)',
    'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;


-- BEGIN 2026-08-03 OPERATOR WORKSPACE UPGRADE
-- Petro Direct-New Pump Operator workspace upgrade
-- Safe to rerun in every tenant database.
SET NAMES utf8mb4;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'source_operator_id'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `source_operator_id` bigint unsigned NULL AFTER `user_id`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'address'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `address` text NULL AFTER `name`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'landline'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `landline` varchar(50) NULL AFTER `mobile`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'dob'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `dob` date NULL AFTER `landline`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'email'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `email` varchar(190) NULL AFTER `nic`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'username'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `username` varchar(100) NULL AFTER `email`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'passcode_hash'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `passcode_hash` varchar(255) NULL AFTER `username`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'opening_balance'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `opening_balance` decimal(22,4) NOT NULL DEFAULT 0 AFTER `passcode_hash`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'commission_type'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `commission_type` varchar(30) NOT NULL DEFAULT ''none'' AFTER `opening_balance`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'commission_value'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `commission_value` decimal(22,4) NOT NULL DEFAULT 0 AFTER `commission_type`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'short_amount'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `short_amount` decimal(22,4) NOT NULL DEFAULT 0 AFTER `commission_value`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'excess_amount'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `excess_amount` decimal(22,4) NOT NULL DEFAULT 0 AFTER `short_amount`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'transaction_date'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `transaction_date` date NULL AFTER `excess_amount`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'is_default'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `is_default` tinyint(1) NOT NULL DEFAULT 0 AFTER `transaction_date`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'can_fullscreen'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `can_fullscreen` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_default`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'hide_in_direct_settlement_if_pending_shifts'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `hide_in_direct_settlement_if_pending_shifts` tinyint(1) NOT NULL DEFAULT 0 AFTER `can_fullscreen`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'source_updated_at'),
  'ALTER TABLE `pdirectnew_operators` ADD COLUMN `source_updated_at` datetime NULL AFTER `is_active`',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND index_name = 'pdn_operator_source_uq'),
  'ALTER TABLE `pdirectnew_operators` ADD UNIQUE INDEX `pdn_operator_source_uq` (`business_id`,`source_operator_id`)',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND index_name = 'pdn_operator_list_idx'),
  'ALTER TABLE `pdirectnew_operators` ADD INDEX `pdn_operator_list_idx` (`business_id`,`location_id`,`is_active`,`name`)',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

-- Keep pre-existing records compatible with the richer operator workspace.
SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators'),
  'UPDATE `pdirectnew_operators` SET `commission_type` = ''none'' WHERE `commission_type` IS NULL OR `commission_type` = ''''',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;

SET @pdn_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators'),
  'UPDATE `pdirectnew_operators` SET `status` = IF(`is_active` = 1, ''active'', ''inactive'') WHERE `status` IS NULL OR `status` = ''''',
  'SELECT 1'
);
PREPARE pdn_stmt FROM @pdn_sql;
EXECUTE pdn_stmt;
DEALLOCATE PREPARE pdn_stmt;
-- END 2026-08-03 OPERATOR WORKSPACE UPGRADE
