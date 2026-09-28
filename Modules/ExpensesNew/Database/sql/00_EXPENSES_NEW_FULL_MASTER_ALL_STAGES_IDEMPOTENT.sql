-- ============================================================================
-- Expenses New - Complete Tenant Master SQL (IS1795 / EXPNEW_014)
-- Core plus EXPNEW_005 through EXPNEW_011. Run on EACH tenant database.
-- Safe to re-run. Standalone CREATE INDEX statements from older parcels are
-- intentionally omitted because MySQL has no portable CREATE INDEX IF NOT EXISTS.
-- ============================================================================

-- ============================================================================
-- Expenses New - Core Tenant Schema (IS1795 / EXPNEW_014)
-- Run this file on EACH tenant database.
-- Safe to re-run: every CREATE uses IF NOT EXISTS and every INSERT uses
-- a NOT EXISTS guard.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `expnew_schema_versions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` VARCHAR(100) NOT NULL,
  `installed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expnew_schema_version_unique` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_expense_accounts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `external_account_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_accounts_business_idx` (`business_id`),
  KEY `expnew_accounts_active_idx` (`business_id`,`is_active`),
  KEY `expnew_accounts_code_idx` (`business_id`,`code`),
  KEY `expnew_accounts_external_idx` (`business_id`,`external_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_payees` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `external_payee_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `mobile` VARCHAR(50) NULL,
  `email` VARCHAR(191) NULL,
  `address` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_payees_business_idx` (`business_id`),
  KEY `expnew_payees_active_idx` (`business_id`,`is_active`),
  KEY `expnew_payees_name_idx` (`business_id`,`name`),
  KEY `expnew_payees_external_idx` (`business_id`,`external_payee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `expense_account_id` BIGINT UNSIGNED NULL,
  `default_payee_id` BIGINT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_categories_business_idx` (`business_id`),
  KEY `expnew_categories_active_idx` (`business_id`,`is_active`),
  KEY `expnew_categories_code_idx` (`business_id`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_category_codes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expnew_cat_codes_unique` (`business_id`,`code`),
  KEY `expnew_cat_codes_business_idx` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expnew_settings_unique` (`business_id`,`key`),
  KEY `expnew_settings_business_idx` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_expenses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `expense_no` VARCHAR(100) NOT NULL,
  `expense_date` DATE NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `payee_id` BIGINT UNSIGNED NULL,
  `expense_account_id` BIGINT UNSIGNED NULL,
  `accounting_module` VARCHAR(50) NULL,
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `due_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `balance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `payment_status` VARCHAR(30) NOT NULL DEFAULT 'due',
  `payment_method` VARCHAR(40) NULL,
  `reference_no` VARCHAR(191) NULL,
  `cheque_no` VARCHAR(191) NULL,
  `bank_account_id` BIGINT UNSIGNED NULL,
  `card_no` VARCHAR(191) NULL,
  `notes` TEXT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expnew_expense_no_unique` (`business_id`,`expense_no`),
  KEY `expnew_expenses_business_idx` (`business_id`),
  KEY `expnew_expenses_date_idx` (`business_id`,`expense_date`),
  KEY `expnew_expenses_status_idx` (`business_id`,`status`),
  KEY `expnew_expenses_payment_idx` (`business_id`,`payment_status`),
  KEY `expnew_expenses_category_idx` (`category_id`),
  KEY `expnew_expenses_payee_idx` (`payee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_expense_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `expense_id` BIGINT UNSIGNED NOT NULL,
  `payment_date` DATE NULL,
  `method` VARCHAR(40) NOT NULL DEFAULT 'cash',
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reference_no` VARCHAR(191) NULL,
  `cheque_no` VARCHAR(191) NULL,
  `bank_account_id` BIGINT UNSIGNED NULL,
  `card_no` VARCHAR(191) NULL,
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_payments_business_idx` (`business_id`),
  KEY `expnew_payments_expense_idx` (`expense_id`),
  KEY `expnew_payments_date_idx` (`business_id`,`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_expense_attachments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `expense_id` BIGINT UNSIGNED NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_type` VARCHAR(191) NULL,
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_attach_business_idx` (`business_id`),
  KEY `expnew_attach_expense_idx` (`expense_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_status_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `expense_id` BIGINT UNSIGNED NOT NULL,
  `old_status` VARCHAR(40) NULL,
  `new_status` VARCHAR(40) NOT NULL,
  `note` TEXT NULL,
  `changed_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_status_business_idx` (`business_id`),
  KEY `expnew_status_expense_idx` (`expense_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `subject_type` VARCHAR(191) NULL,
  `subject_id` BIGINT UNSIGNED NULL,
  `event` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `before_json` JSON NULL,
  `after_json` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_activity_business_idx` (`business_id`),
  KEY `expnew_activity_subject_idx` (`subject_type`,`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_approval_workflows` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(100) NULL,
  `rules_json` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_workflow_business_idx` (`business_id`),
  KEY `expnew_workflow_active_idx` (`business_id`,`location_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_approval_levels` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `workflow_id` BIGINT UNSIGNED NOT NULL,
  `level_no` INT UNSIGNED NOT NULL DEFAULT 1,
  `name` VARCHAR(191) NULL,
  `min_amount` DECIMAL(22,4) NULL,
  `max_amount` DECIMAL(22,4) NULL,
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  `approver_user_ids` JSON NULL,
  `approver_role_ids` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expnew_levels_unique` (`workflow_id`,`level_no`),
  KEY `expnew_levels_workflow_idx` (`workflow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_approval_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `expense_id` BIGINT UNSIGNED NOT NULL,
  `workflow_id` BIGINT UNSIGNED NULL,
  `level_no` INT UNSIGNED NULL,
  `action` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `note` TEXT NULL,
  `action_by` BIGINT UNSIGNED NULL,
  `meta_json` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_appr_logs_business_idx` (`business_id`),
  KEY `expnew_appr_logs_expense_idx` (`expense_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_departments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `code` VARCHAR(100) NULL,
  `name` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_departments_business_idx` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_cost_centers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `code` VARCHAR(100) NULL,
  `name` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_cost_centers_business_idx` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_projects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `code` VARCHAR(100) NULL,
  `name` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_projects_business_idx` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_command_widgets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `widget_key` VARCHAR(100) NOT NULL,
  `widget_title` VARCHAR(191) NOT NULL,
  `widget_type` VARCHAR(50) NOT NULL DEFAULT 'kpi',
  `settings` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expnew_cmd_widget_unique` (`business_id`,`widget_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_command_preferences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `business_id` BIGINT UNSIGNED NULL,
  `workspace_key` VARCHAR(100) NOT NULL,
  `preferences` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expnew_cmd_pref_unique` (`user_id`,`business_id`,`workspace_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_operation_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` BIGINT UNSIGNED NULL,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `source_type` VARCHAR(100) NULL,
  `source_id` BIGINT UNSIGNED NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `event_title` VARCHAR(191) NOT NULL,
  `event_message` TEXT NULL,
  `event_time` DATETIME NOT NULL,
  `severity` VARCHAR(30) NOT NULL DEFAULT 'info',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_ops_event_time` (`event_time`),
  KEY `expnew_ops_business` (`business_id`,`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_approval_queues` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `expense_id` BIGINT UNSIGNED NULL,
  `expense_no` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `current_level` INT NOT NULL DEFAULT 1,
  `assigned_to` BIGINT UNSIGNED NULL,
  `assigned_role` VARCHAR(100) NULL,
  `priority` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `last_action` VARCHAR(50) NULL,
  `last_action_by` BIGINT UNSIGNED NULL,
  `last_action_at` DATETIME NULL,
  `last_comments` TEXT NULL,
  `due_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_appr_status` (`status`,`priority`),
  KEY `expnew_appr_business` (`business_id`,`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_command_alerts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `alert_type` VARCHAR(100) NOT NULL,
  `alert_title` VARCHAR(191) NOT NULL,
  `alert_message` TEXT NULL,
  `source_type` VARCHAR(100) NULL,
  `source_id` BIGINT UNSIGNED NULL,
  `severity` VARCHAR(30) NOT NULL DEFAULT 'warning',
  `is_resolved` TINYINT(1) NOT NULL DEFAULT 0,
  `resolved_by` BIGINT UNSIGNED NULL,
  `resolved_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_alert_active` (`alert_type`,`is_resolved`),
  KEY `expnew_alert_business` (`business_id`,`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_saved_filters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `filter_name` VARCHAR(191) NOT NULL,
  `filter_context` VARCHAR(100) NOT NULL,
  `filter_payload` JSON NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Idempotent global command-centre defaults.
INSERT INTO `expnew_command_widgets`
(`business_id`,`widget_key`,`widget_title`,`widget_type`,`settings`,`is_active`,`sort_order`,`created_at`,`updated_at`)
SELECT NULL,'today_expenses','Today Expenses','kpi',NULL,1,10,NOW(),NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `expnew_command_widgets`
  WHERE `business_id` IS NULL AND `widget_key`='today_expenses'
);

INSERT INTO `expnew_command_widgets`
(`business_id`,`widget_key`,`widget_title`,`widget_type`,`settings`,`is_active`,`sort_order`,`created_at`,`updated_at`)
SELECT NULL,'pending_approvals','Pending Approvals','kpi',NULL,1,20,NOW(),NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `expnew_command_widgets`
  WHERE `business_id` IS NULL AND `widget_key`='pending_approvals'
);

INSERT INTO `expnew_command_widgets`
(`business_id`,`widget_key`,`widget_title`,`widget_type`,`settings`,`is_active`,`sort_order`,`created_at`,`updated_at`)
SELECT NULL,'pending_payments','Pending Payments','kpi',NULL,1,30,NOW(),NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `expnew_command_widgets`
  WHERE `business_id` IS NULL AND `widget_key`='pending_payments'
);

INSERT INTO `expnew_command_widgets`
(`business_id`,`widget_key`,`widget_title`,`widget_type`,`settings`,`is_active`,`sort_order`,`created_at`,`updated_at`)
SELECT NULL,'budget_alerts','Budget Alerts','alert',NULL,1,40,NOW(),NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `expnew_command_widgets`
  WHERE `business_id` IS NULL AND `widget_key`='budget_alerts'
);

-- The application schema-readiness middleware records the installed version
-- only after all required table/column checks complete successfully.

-- Existing partial tables are repaired by the module's schema-readiness middleware.

SET FOREIGN_KEY_CHECKS = 1;


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


-- ============================================================================
-- BEGIN MASTER_SQL_EXPENSES_NEW_UP_TO_005.sql
-- ============================================================================
-- Expenses-New EXPNEW_005 Enterprise Financial Control Platform SQL
-- Run in every tenant database. Tables use expnew_ prefix only.

CREATE TABLE IF NOT EXISTS expnew_budgets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NULL,
  budget_code VARCHAR(50) NOT NULL,
  budget_name VARCHAR(191) NOT NULL,
  budget_type VARCHAR(50) DEFAULT 'monthly',
  period_start DATE NULL,
  period_end DATE NULL,
  original_amount DECIMAL(22,4) DEFAULT 0,
  revised_amount DECIMAL(22,4) DEFAULT 0,
  committed_amount DECIMAL(22,4) DEFAULT 0,
  actual_amount DECIMAL(22,4) DEFAULT 0,
  status VARCHAR(30) DEFAULT 'active',
  metadata JSON NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_budgets_code_business (business_id, budget_code)
);

CREATE TABLE IF NOT EXISTS expnew_budget_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  budget_id BIGINT UNSIGNED NOT NULL,
  department_id BIGINT UNSIGNED NULL,
  cost_center_id BIGINT UNSIGNED NULL,
  project_id BIGINT UNSIGNED NULL,
  category_id BIGINT UNSIGNED NULL,
  month_no TINYINT UNSIGNED NULL,
  original_amount DECIMAL(22,4) DEFAULT 0,
  revised_amount DECIMAL(22,4) DEFAULT 0,
  committed_amount DECIMAL(22,4) DEFAULT 0,
  actual_amount DECIMAL(22,4) DEFAULT 0,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_budget_lines_budget (budget_id)
);

CREATE TABLE IF NOT EXISTS expnew_budget_revisions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  budget_id BIGINT UNSIGNED NOT NULL,
  revision_no INT UNSIGNED DEFAULT 1,
  old_amount DECIMAL(22,4) DEFAULT 0,
  new_amount DECIMAL(22,4) DEFAULT 0,
  reason TEXT NULL,
  approved_by BIGINT UNSIGNED NULL,
  approved_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_budget_revisions_budget (budget_id)
);

CREATE TABLE IF NOT EXISTS expnew_budget_consumptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  budget_id BIGINT UNSIGNED NULL,
  budget_line_id BIGINT UNSIGNED NULL,
  expense_id BIGINT UNSIGNED NULL,
  source_type VARCHAR(80) NULL,
  source_id BIGINT UNSIGNED NULL,
  reserved_amount DECIMAL(22,4) DEFAULT 0,
  consumed_amount DECIMAL(22,4) DEFAULT 0,
  released_amount DECIMAL(22,4) DEFAULT 0,
  status VARCHAR(30) DEFAULT 'reserved',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_budget_consumptions_expense (expense_id),
  INDEX expnew_budget_consumptions_budget (budget_id)
);

CREATE TABLE IF NOT EXISTS expnew_policies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  policy_code VARCHAR(50) NOT NULL,
  policy_name VARCHAR(191) NOT NULL,
  applies_to VARCHAR(80) DEFAULT 'expense',
  action_on_violation VARCHAR(30) DEFAULT 'warning',
  is_active TINYINT(1) DEFAULT 1,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_policies_code_business (business_id, policy_code)
);

CREATE TABLE IF NOT EXISTS expnew_policy_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  policy_id BIGINT UNSIGNED NOT NULL,
  rule_key VARCHAR(80) NOT NULL,
  operator VARCHAR(20) DEFAULT '<=',
  rule_value VARCHAR(191) NULL,
  amount_limit DECIMAL(22,4) NULL,
  severity VARCHAR(30) DEFAULT 'warning',
  message TEXT NULL,
  is_active TINYINT(1) DEFAULT 1,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_policy_rules_policy (policy_id)
);

CREATE TABLE IF NOT EXISTS expnew_tax_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  tax_code VARCHAR(50) NOT NULL,
  tax_name VARCHAR(191) NOT NULL,
  tax_type VARCHAR(50) DEFAULT 'vat',
  calculation_type VARCHAR(30) DEFAULT 'exclusive',
  is_active TINYINT(1) DEFAULT 1,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_tax_codes_code_business (business_id, tax_code)
);

CREATE TABLE IF NOT EXISTS expnew_tax_rates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tax_code_id BIGINT UNSIGNED NOT NULL,
  rate DECIMAL(10,4) DEFAULT 0,
  effective_from DATE NULL,
  effective_to DATE NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_tax_rates_code (tax_code_id)
);

CREATE TABLE IF NOT EXISTS expnew_recurring_expenses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  recurring_code VARCHAR(50) NOT NULL,
  title VARCHAR(191) NOT NULL,
  frequency VARCHAR(30) DEFAULT 'monthly',
  start_date DATE NULL,
  end_date DATE NULL,
  next_run_date DATE NULL,
  max_occurrences INT UNSIGNED NULL,
  occurrence_count INT UNSIGNED DEFAULT 0,
  amount DECIMAL(22,4) DEFAULT 0,
  auto_submit TINYINT(1) DEFAULT 0,
  auto_approve TINYINT(1) DEFAULT 0,
  auto_pay TINYINT(1) DEFAULT 0,
  status VARCHAR(30) DEFAULT 'active',
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_recurring_code_business (business_id, recurring_code)
);

CREATE TABLE IF NOT EXISTS expnew_recurring_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recurring_expense_id BIGINT UNSIGNED NOT NULL,
  run_date DATE NOT NULL,
  generated_expense_id BIGINT UNSIGNED NULL,
  status VARCHAR(30) DEFAULT 'generated',
  message TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_recurring_runs_recurring (recurring_expense_id)
);

CREATE TABLE IF NOT EXISTS expnew_duplicate_checks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  expense_id BIGINT UNSIGNED NULL,
  matched_expense_id BIGINT UNSIGNED NULL,
  check_type VARCHAR(80) NOT NULL,
  score DECIMAL(8,4) DEFAULT 0,
  status VARCHAR(30) DEFAULT 'open',
  details JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_duplicate_checks_expense (expense_id)
);

CREATE TABLE IF NOT EXISTS expnew_project_cost_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id BIGINT UNSIGNED NOT NULL,
  snapshot_date DATE NOT NULL,
  expense_amount DECIMAL(22,4) DEFAULT 0,
  committed_amount DECIMAL(22,4) DEFAULT 0,
  budget_amount DECIMAL(22,4) DEFAULT 0,
  variance_amount DECIMAL(22,4) DEFAULT 0,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_project_cost_snapshots_project (project_id)
);

CREATE TABLE IF NOT EXISTS expnew_vendor_metrics (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payee_id BIGINT UNSIGNED NOT NULL,
  period_start DATE NULL,
  period_end DATE NULL,
  total_expenses DECIMAL(22,4) DEFAULT 0,
  total_paid DECIMAL(22,4) DEFAULT 0,
  outstanding DECIMAL(22,4) DEFAULT 0,
  expense_count INT UNSIGNED DEFAULT 0,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_vendor_metrics_payee (payee_id)
);


-- ============================================================================
-- BEGIN MASTER_SQL_EXPENSES_NEW_UP_TO_006.sql
-- ============================================================================
-- EXPNEW_006 Executive Analytics and Expense Intelligence SQL
-- Tenant database only. All tables use expnew_ prefix.

CREATE TABLE IF NOT EXISTS expnew_expense_kpi_snapshots (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 location_id BIGINT UNSIGNED NULL,
 snapshot_date DATE NOT NULL,
 kpi_code VARCHAR(100) NOT NULL,
 kpi_value DECIMAL(22,4) NOT NULL DEFAULT 0,
 meta JSON NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX expnew_kpi_biz_date (business_id, snapshot_date),
 UNIQUE KEY expnew_kpi_unique (business_id, location_id, snapshot_date, kpi_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_expense_analytics_snapshots (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 location_id BIGINT UNSIGNED NULL,
 dimension_type VARCHAR(80) NOT NULL,
 dimension_id BIGINT UNSIGNED NULL,
 period_type VARCHAR(30) NOT NULL DEFAULT 'monthly',
 period_start DATE NOT NULL,
 period_end DATE NOT NULL,
 total_expenses DECIMAL(22,4) NOT NULL DEFAULT 0,
 total_paid DECIMAL(22,4) NOT NULL DEFAULT 0,
 total_pending DECIMAL(22,4) NOT NULL DEFAULT 0,
 record_count INT NOT NULL DEFAULT 0,
 meta JSON NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX expnew_analytics_period (business_id, dimension_type, period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_expense_intelligence_alerts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 location_id BIGINT UNSIGNED NULL,
 expense_id BIGINT UNSIGNED NULL,
 alert_type VARCHAR(80) NOT NULL,
 severity VARCHAR(30) NOT NULL DEFAULT 'info',
 title VARCHAR(191) NOT NULL,
 message TEXT NULL,
 score DECIMAL(10,4) NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'open',
 detected_at DATETIME NULL,
 resolved_at DATETIME NULL,
 resolved_by BIGINT UNSIGNED NULL,
 meta JSON NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX expnew_alert_status (business_id, alert_type, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_expense_report_definitions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 report_code VARCHAR(100) NOT NULL UNIQUE,
 report_group VARCHAR(80) NOT NULL,
 report_title VARCHAR(191) NOT NULL,
 permission_name VARCHAR(191) NULL,
 columns_json JSON NULL,
 filters_json JSON NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_expense_report_runs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 report_code VARCHAR(100) NOT NULL,
 filters_json JSON NULL,
 export_type VARCHAR(30) NULL,
 run_by BIGINT UNSIGNED NULL,
 started_at DATETIME NULL,
 completed_at DATETIME NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'pending',
 file_path VARCHAR(500) NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX expnew_report_runs_biz (business_id, report_code, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_expense_dashboard_widgets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 widget_code VARCHAR(100) NOT NULL,
 widget_title VARCHAR(191) NOT NULL,
 widget_type VARCHAR(50) NOT NULL DEFAULT 'card',
 sort_order INT NOT NULL DEFAULT 0,
 settings_json JSON NULL,
 is_visible TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX expnew_widgets_user (business_id, user_id, is_visible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_expense_chart_definitions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 chart_code VARCHAR(100) NOT NULL UNIQUE,
 chart_title VARCHAR(191) NOT NULL,
 chart_type VARCHAR(50) NOT NULL,
 data_source VARCHAR(191) NULL,
 config_json JSON NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_expense_drilldown_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 source_type VARCHAR(80) NOT NULL,
 source_code VARCHAR(100) NULL,
 filters_json JSON NULL,
 created_at TIMESTAMP NULL,
 INDEX expnew_drill_logs_user (business_id, user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO expnew_expense_report_definitions (report_code, report_group, report_title, permission_name, is_active, created_at, updated_at)
SELECT 'expense_register','operational','Expense Register','expenses_new.reports.view',1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_expense_report_definitions WHERE report_code='expense_register');
INSERT INTO expnew_expense_report_definitions (report_code, report_group, report_title, permission_name, is_active, created_at, updated_at)
SELECT 'budget_vs_actual','management','Budget vs Actual','expenses_new.reports.view',1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_expense_report_definitions WHERE report_code='budget_vs_actual');
INSERT INTO expnew_expense_report_definitions (report_code, report_group, report_title, permission_name, is_active, created_at, updated_at)
SELECT 'executive_kpi','executive','Executive KPI Dashboard','expenses_new.executive_dashboard.view',1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_expense_report_definitions WHERE report_code='executive_kpi');


-- ============================================================================
-- BEGIN 00_EXPNEW_007_Master_SQL.sql
-- ============================================================================
-- EXPNEW_007 Tenant Create Tables
CREATE TABLE IF NOT EXISTS expnew_integration_sources (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  source_module VARCHAR(100) NOT NULL,
  display_name VARCHAR(191) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  settings_json LONGTEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_source_unique (business_id, source_module)
);

CREATE TABLE IF NOT EXISTS expnew_integration_postings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  business_location_id BIGINT UNSIGNED NULL,
  expense_id BIGINT UNSIGNED NULL,
  source_module VARCHAR(100) NOT NULL,
  source_reference VARCHAR(191) NULL,
  posting_type VARCHAR(50) NOT NULL DEFAULT 'expense',
  amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'LKR',
  payload_json LONGTEXT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'received',
  processed_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_postings_business_idx (business_id, business_location_id),
  INDEX expnew_postings_source_idx (source_module, source_reference),
  INDEX expnew_postings_status_idx (status)
);

CREATE TABLE IF NOT EXISTS expnew_integration_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  posting_id BIGINT UNSIGNED NULL,
  log_level VARCHAR(50) NOT NULL DEFAULT 'info',
  message TEXT NULL,
  context_json LONGTEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS expnew_notification_templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  event_key VARCHAR(120) NOT NULL,
  subject VARCHAR(191) NULL,
  body LONGTEXT NULL,
  channels_json LONGTEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_notification_template_unique (business_id, event_key)
);

CREATE TABLE IF NOT EXISTS expnew_notification_queue (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_key VARCHAR(120) NOT NULL,
  channels_json LONGTEXT NULL,
  payload_json LONGTEXT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  sent_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_notification_status_idx (status, event_key)
);

CREATE TABLE IF NOT EXISTS expnew_api_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  name VARCHAR(191) NOT NULL,
  token_hash VARCHAR(191) NOT NULL,
  abilities_json LONGTEXT NULL,
  last_used_at TIMESTAMP NULL,
  expires_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS expnew_webhook_endpoints (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  name VARCHAR(191) NOT NULL,
  endpoint_url VARCHAR(500) NOT NULL,
  events_json LONGTEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS expnew_webhook_deliveries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  endpoint_id BIGINT UNSIGNED NOT NULL,
  event_key VARCHAR(120) NOT NULL,
  payload_json LONGTEXT NULL,
  response_code INT NULL,
  response_body LONGTEXT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  delivered_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_webhook_delivery_idx (endpoint_id, status)
);


-- EXPNEW_007 Idempotent Default Data
INSERT INTO expnew_integration_sources (business_id, source_module, display_name, is_active, created_at, updated_at)
SELECT NULL, 'HRManager', 'HR Manager', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_integration_sources WHERE business_id IS NULL AND source_module='HRManager');
INSERT INTO expnew_integration_sources (business_id, source_module, display_name, is_active, created_at, updated_at)
SELECT NULL, 'POS', 'Point of Sale', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_integration_sources WHERE business_id IS NULL AND source_module='POS');
INSERT INTO expnew_notification_templates (business_id, event_key, subject, body, channels_json, is_active, created_at, updated_at)
SELECT NULL, 'expense.submitted', 'Expense Submitted', 'An expense has been submitted for approval.', '["system","email"]', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_notification_templates WHERE business_id IS NULL AND event_key='expense.submitted');
INSERT INTO expnew_notification_templates (business_id, event_key, subject, body, channels_json, is_active, created_at, updated_at)
SELECT NULL, 'expense.approved', 'Expense Approved', 'An expense has been approved.', '["system","email"]', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_notification_templates WHERE business_id IS NULL AND event_key='expense.approved');
INSERT INTO expnew_notification_templates (business_id, event_key, subject, body, channels_json, is_active, created_at, updated_at)
SELECT NULL, 'expense.paid', 'Expense Paid', 'An expense payment has been completed.', '["system","sms","email"]', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_notification_templates WHERE business_id IS NULL AND event_key='expense.paid');


-- ============================================================================
-- BEGIN 00_EXPNEW_008_Master_SQL.sql
-- ============================================================================
-- EXPNEW_008 Tenant Create Tables
-- Run on each tenant database only.

CREATE TABLE IF NOT EXISTS `expnew_cost_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_cost_transactions_business_idx` (`business_id`),
  KEY `expnew_cost_transactions_location_idx` (`business_location_id`),
  KEY `expnew_cost_transactions_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_cost_pools` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_cost_pools_business_idx` (`business_id`),
  KEY `expnew_cost_pools_location_idx` (`business_location_id`),
  KEY `expnew_cost_pools_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_cost_drivers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_cost_drivers_business_idx` (`business_id`),
  KEY `expnew_cost_drivers_location_idx` (`business_location_id`),
  KEY `expnew_cost_drivers_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_cost_allocation_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_cost_allocation_rules_business_idx` (`business_id`),
  KEY `expnew_cost_allocation_rules_location_idx` (`business_location_id`),
  KEY `expnew_cost_allocation_rules_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_cost_allocation_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_cost_allocation_runs_business_idx` (`business_id`),
  KEY `expnew_cost_allocation_runs_location_idx` (`business_location_id`),
  KEY `expnew_cost_allocation_runs_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_cost_allocation_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_cost_allocation_lines_business_idx` (`business_id`),
  KEY `expnew_cost_allocation_lines_location_idx` (`business_location_id`),
  KEY `expnew_cost_allocation_lines_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_shared_expenses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_shared_expenses_business_idx` (`business_id`),
  KEY `expnew_shared_expenses_location_idx` (`business_location_id`),
  KEY `expnew_shared_expenses_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_overhead_recovery_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_overhead_recovery_rules_business_idx` (`business_id`),
  KEY `expnew_overhead_recovery_rules_location_idx` (`business_location_id`),
  KEY `expnew_overhead_recovery_rules_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_activitys` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_activitys_business_idx` (`business_id`),
  KEY `expnew_activitys_location_idx` (`business_location_id`),
  KEY `expnew_activitys_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_activity_costs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_activity_costs_business_idx` (`business_id`),
  KEY `expnew_activity_costs_location_idx` (`business_location_id`),
  KEY `expnew_activity_costs_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_profitability_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_profitability_snapshots_business_idx` (`business_id`),
  KEY `expnew_profitability_snapshots_location_idx` (`business_location_id`),
  KEY `expnew_profitability_snapshots_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_kpi_metrics` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_kpi_metrics_business_idx` (`business_id`),
  KEY `expnew_kpi_metrics_location_idx` (`business_location_id`),
  KEY `expnew_kpi_metrics_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_kpi_values` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_kpi_values_business_idx` (`business_id`),
  KEY `expnew_kpi_values_location_idx` (`business_location_id`),
  KEY `expnew_kpi_values_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- EXPNEW_008 Default Data - idempotent style
INSERT INTO `expnew_kpi_metrics` (`business_id`,`name`,`code`,`status`,`created_at`,`updated_at`)
SELECT NULL,'Expense Ratio','EXPENSE_RATIO','active',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `expnew_kpi_metrics` WHERE `code`='EXPENSE_RATIO' AND `business_id` IS NULL);

INSERT INTO `expnew_kpi_metrics` (`business_id`,`name`,`code`,`status`,`created_at`,`updated_at`)
SELECT NULL,'Cost Per Employee','COST_PER_EMPLOYEE','active',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `expnew_kpi_metrics` WHERE `code`='COST_PER_EMPLOYEE' AND `business_id` IS NULL);


-- ============================================================================
-- BEGIN 00_EXPNEW_009_Master_SQL.sql
-- ============================================================================
-- EXPNEW_009 Tenant Create Tables - Enterprise Command Center
CREATE TABLE IF NOT EXISTS expnew_command_widgets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  widget_key VARCHAR(100) NOT NULL,
  widget_title VARCHAR(191) NOT NULL,
  widget_type VARCHAR(50) NOT NULL DEFAULT 'kpi',
  settings JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_cmd_widget_unique (business_id, widget_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_command_preferences (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  business_id BIGINT UNSIGNED NULL,
  workspace_key VARCHAR(100) NOT NULL,
  preferences JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_cmd_pref_unique (user_id, business_id, workspace_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_operation_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NULL,
  business_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NULL,
  source_type VARCHAR(100) NULL,
  source_id BIGINT UNSIGNED NULL,
  event_type VARCHAR(100) NOT NULL,
  event_title VARCHAR(191) NOT NULL,
  event_message TEXT NULL,
  event_time DATETIME NOT NULL,
  severity VARCHAR(30) NOT NULL DEFAULT 'info',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_ops_event_time (event_time),
  INDEX expnew_ops_business (business_id, location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_approval_queues (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NULL,
  expense_id BIGINT UNSIGNED NULL,
  expense_no VARCHAR(100) NULL,
  amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  current_level INT NOT NULL DEFAULT 1,
  assigned_to BIGINT UNSIGNED NULL,
  assigned_role VARCHAR(100) NULL,
  priority INT NOT NULL DEFAULT 0,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  last_action VARCHAR(50) NULL,
  last_action_by BIGINT UNSIGNED NULL,
  last_action_at DATETIME NULL,
  last_comments TEXT NULL,
  due_at DATETIME NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_appr_status (status, priority),
  INDEX expnew_appr_business (business_id, location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_command_alerts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NULL,
  alert_type VARCHAR(100) NOT NULL,
  alert_title VARCHAR(191) NOT NULL,
  alert_message TEXT NULL,
  source_type VARCHAR(100) NULL,
  source_id BIGINT UNSIGNED NULL,
  severity VARCHAR(30) NOT NULL DEFAULT 'warning',
  is_resolved TINYINT(1) NOT NULL DEFAULT 0,
  resolved_by BIGINT UNSIGNED NULL,
  resolved_at DATETIME NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_alert_active (alert_type, is_resolved),
  INDEX expnew_alert_business (business_id, location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_saved_filters (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  filter_name VARCHAR(191) NOT NULL,
  filter_context VARCHAR(100) NOT NULL,
  filter_payload JSON NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- EXPNEW_009 Default widgets - safe re-run
INSERT INTO expnew_command_widgets (business_id, widget_key, widget_title, widget_type, sort_order, is_active, created_at, updated_at)
SELECT NULL, 'today_expenses', 'Today Expenses', 'kpi', 10, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_command_widgets WHERE business_id IS NULL AND widget_key='today_expenses');
INSERT INTO expnew_command_widgets (business_id, widget_key, widget_title, widget_type, sort_order, is_active, created_at, updated_at)
SELECT NULL, 'pending_approvals', 'Pending Approvals', 'kpi', 20, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_command_widgets WHERE business_id IS NULL AND widget_key='pending_approvals');
INSERT INTO expnew_command_widgets (business_id, widget_key, widget_title, widget_type, sort_order, is_active, created_at, updated_at)
SELECT NULL, 'pending_payments', 'Pending Payments', 'kpi', 30, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_command_widgets WHERE business_id IS NULL AND widget_key='pending_payments');
INSERT INTO expnew_command_widgets (business_id, widget_key, widget_title, widget_type, sort_order, is_active, created_at, updated_at)
SELECT NULL, 'budget_alerts', 'Budget Alerts', 'alert', 40, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_command_widgets WHERE business_id IS NULL AND widget_key='budget_alerts');


-- ============================================================================
-- BEGIN 00_EXPNEW_010_Master_SQL.sql
-- ============================================================================
CREATE TABLE IF NOT EXISTS `expnew_budgets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` VARCHAR(191) NULL,
  `location_id` VARCHAR(191) NULL,
  `budget_type` VARCHAR(191) NULL,
  `budget_name` VARCHAR(191) NULL,
  `period_start` VARCHAR(191) NULL,
  `period_end` VARCHAR(191) NULL,
  `original_amount` VARCHAR(191) NULL,
  `revised_amount` VARCHAR(191) NULL,
  `approved_amount` VARCHAR(191) NULL,
  `actual_amount` VARCHAR(191) NULL,
  `committed_amount` VARCHAR(191) NULL,
  `reserved_amount` VARCHAR(191) NULL,
  `remaining_amount` VARCHAR(191) NULL,
  `forecast_amount` VARCHAR(191) NULL,
  `status` VARCHAR(191) NULL,
  `created_by` VARCHAR(191) NULL,
  `updated_by` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_budget_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id` VARCHAR(191) NULL,
  `department_id` VARCHAR(191) NULL,
  `cost_center_id` VARCHAR(191) NULL,
  `project_id` VARCHAR(191) NULL,
  `category_id` VARCHAR(191) NULL,
  `line_name` VARCHAR(191) NULL,
  `original_amount` VARCHAR(191) NULL,
  `revised_amount` VARCHAR(191) NULL,
  `actual_amount` VARCHAR(191) NULL,
  `variance_amount` VARCHAR(191) NULL,
  `utilization_percent` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_budget_revisions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id` VARCHAR(191) NULL,
  `revision_no` VARCHAR(191) NULL,
  `old_amount` VARCHAR(191) NULL,
  `new_amount` VARCHAR(191) NULL,
  `reason` VARCHAR(191) NULL,
  `status` VARCHAR(191) NULL,
  `requested_by` VARCHAR(191) NULL,
  `approved_by` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_budget_approvals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id` VARCHAR(191) NULL,
  `approval_level` VARCHAR(191) NULL,
  `approver_id` VARCHAR(191) NULL,
  `status` VARCHAR(191) NULL,
  `comments` VARCHAR(191) NULL,
  `approved_at` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_budget_forecasts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id` VARCHAR(191) NULL,
  `forecast_month` VARCHAR(191) NULL,
  `forecast_amount` VARCHAR(191) NULL,
  `basis` VARCHAR(191) NULL,
  `confidence_score` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_budget_variances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id` VARCHAR(191) NULL,
  `budget_line_id` VARCHAR(191) NULL,
  `variance_type` VARCHAR(191) NULL,
  `variance_amount` VARCHAR(191) NULL,
  `variance_percent` VARCHAR(191) NULL,
  `reason` VARCHAR(191) NULL,
  `action_required` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_financial_signals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` VARCHAR(191) NULL,
  `location_id` VARCHAR(191) NULL,
  `signal_type` VARCHAR(191) NULL,
  `severity` VARCHAR(191) NULL,
  `title` VARCHAR(191) NULL,
  `message` VARCHAR(191) NULL,
  `reference_type` VARCHAR(191) NULL,
  `reference_id` VARCHAR(191) NULL,
  `status` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_kpi_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` VARCHAR(191) NULL,
  `location_id` VARCHAR(191) NULL,
  `snapshot_date` VARCHAR(191) NULL,
  `kpi_code` VARCHAR(191) NULL,
  `kpi_value` VARCHAR(191) NULL,
  `comparison_value` VARCHAR(191) NULL,
  `trend_direction` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_closing_periods` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` VARCHAR(191) NULL,
  `location_id` VARCHAR(191) NULL,
  `period_month` VARCHAR(191) NULL,
  `period_year` VARCHAR(191) NULL,
  `status` VARCHAR(191) NULL,
  `closed_by` VARCHAR(191) NULL,
  `closed_at` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_audit_exceptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` VARCHAR(191) NULL,
  `location_id` VARCHAR(191) NULL,
  `exception_type` VARCHAR(191) NULL,
  `severity` VARCHAR(191) NULL,
  `reference_type` VARCHAR(191) NULL,
  `reference_id` VARCHAR(191) NULL,
  `description` VARCHAR(191) NULL,
  `status` VARCHAR(191) NULL,
  `resolved_by` VARCHAR(191) NULL,
  `resolved_at` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO expnew_financial_signals (business_id, location_id, signal_type, severity, title, message, status, created_at, updated_at)
SELECT 0, 0, 'system_ready', 'info', 'Financial Intelligence Ready', 'EXPNEW_010 financial intelligence parcel installed.', 'open', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_financial_signals WHERE signal_type='system_ready' AND title='Financial Intelligence Ready');

-- Omitted non-idempotent legacy index statement: CREATE INDEX idx_expnew_budgets_business ON `expnew_budgets` (`business_id`);
-- Omitted non-idempotent legacy index statement: CREATE INDEX idx_expnew_financial_signals_business ON `expnew_financial_signals` (`business_id`);
-- Omitted non-idempotent legacy index statement: CREATE INDEX idx_expnew_kpi_snapshots_business ON `expnew_kpi_snapshots` (`business_id`);
-- Omitted non-idempotent legacy index statement: CREATE INDEX idx_expnew_closing_periods_business ON `expnew_closing_periods` (`business_id`);
-- Omitted non-idempotent legacy index statement: CREATE INDEX idx_expnew_audit_exceptions_business ON `expnew_audit_exceptions` (`business_id`);


-- ============================================================================
-- BEGIN 00_EXPNEW_011_Master_SQL.sql
-- ============================================================================
-- EXPNEW_011 Tenant Create Tables
CREATE TABLE IF NOT EXISTS expnew_report_definitions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 code VARCHAR(191) NOT NULL,
 title VARCHAR(191) NOT NULL,
 category VARCHAR(100) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 UNIQUE KEY expnew_report_definitions_code_unique (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_report_runs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 report_code VARCHAR(191) NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 filters JSON NULL,
 status VARCHAR(50) NOT NULL DEFAULT 'completed',
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 INDEX expnew_report_runs_business_report_idx (business_id, report_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_dashboard_widgets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 widget_code VARCHAR(191) NOT NULL,
 title VARCHAR(191) NOT NULL,
 settings JSON NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 INDEX expnew_dashboard_widgets_business_idx (business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- EXPNEW_011 Default Reports (idempotent)
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'expense_register','Expense Register','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='expense_register');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'expense_summary','Expense Summary','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='expense_summary');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'expense_by_category','Expense By Category','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='expense_by_category');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'expense_by_payee','Expense By Payee','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='expense_by_payee');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'expense_by_department','Expense By Department','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='expense_by_department');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'expense_by_business','Expense By Business','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='expense_by_business');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'expense_by_location','Expense By Location','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='expense_by_location');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'expense_by_cost_centre','Expense By Cost Centre','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='expense_by_cost_centre');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'expense_by_project','Expense By Project','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='expense_by_project');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'payment_register','Payment Register','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='payment_register');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'outstanding_payments','Outstanding Payments','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='outstanding_payments');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'budget_vs_actual','Budget Vs Actual','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='budget_vs_actual');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'budget_variance','Budget Variance','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='budget_variance');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'budget_utilization','Budget Utilization','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='budget_utilization');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'employee_claims','Employee Claims','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='employee_claims');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'petty_cash','Petty Cash','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='petty_cash');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'cash_advances','Cash Advances','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='cash_advances');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'tax_summary','Tax Summary','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='tax_summary');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'approval_history','Approval History','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='approval_history');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'policy_violations','Policy Violations','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='policy_violations');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'duplicate_detection','Duplicate Detection','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='duplicate_detection');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'audit_register','Audit Register','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='audit_register');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'ceo_dashboard','Ceo Dashboard','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='ceo_dashboard');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'cfo_dashboard','Cfo Dashboard','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='cfo_dashboard');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'board_summary','Board Summary','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='board_summary');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'kpi_dashboard','Kpi Dashboard','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='kpi_dashboard');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'trend_analysis','Trend Analysis','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='trend_analysis');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'profitability_analysis','Profitability Analysis','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='profitability_analysis');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'monthly_closing','Monthly Closing','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='monthly_closing');
INSERT INTO expnew_report_definitions (code,title,category,is_active,created_at,updated_at) SELECT 'year_end_summary','Year End Summary','enterprise',1,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM expnew_report_definitions WHERE code='year_end_summary');

-- EXPNEW_011 Views and Indexes
CREATE OR REPLACE VIEW expnew_v_report_catalog AS SELECT code,title,category,is_active FROM expnew_report_definitions;
