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
