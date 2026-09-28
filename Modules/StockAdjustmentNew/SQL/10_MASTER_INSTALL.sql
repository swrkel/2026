-- Stock Adjustment New - MASTER TENANT INSTALL
-- Includes all idempotent module SQL through S592 (2 Aug 2026).
-- Safe to run repeatedly on each tenant database.


-- =============================================================
-- BEGIN 01_Create_Tables.sql
-- =============================================================
-- Stock Adjustment New - idempotent tenant table creation
CREATE TABLE IF NOT EXISTS `san_stock_adjustment_reasons` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(50) NULL,
  `effect` VARCHAR(20) NOT NULL DEFAULT 'both',
  `requires_approval` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `san_reason_business_idx` (`business_id`),
  KEY `san_reason_code_idx` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `san_stock_adjustments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `adjustment_no` VARCHAR(191) NOT NULL,
  `adjustment_date` DATE NOT NULL,
  `adjustment_type` VARCHAR(50) NOT NULL DEFAULT 'quantity',
  `stock_adjustment_type` VARCHAR(20) NOT NULL DEFAULT 'increase',
  `reason_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `total_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `total_cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `approval_remarks` TEXT NULL,
  `posted_by` BIGINT UNSIGNED NULL,
  `posted_at` TIMESTAMP NULL,
  `host_transaction_id` BIGINT UNSIGNED NULL,
  `posting_summary` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `san_adj_business_no_unique` (`business_id`,`adjustment_no`),
  KEY `san_adj_scope_idx` (`business_id`,`location_id`,`store_id`),
  KEY `san_adj_status_idx` (`status`),
  KEY `san_adj_date_idx` (`adjustment_date`),
  KEY `san_adj_stock_type_idx` (`stock_adjustment_type`),
  KEY `san_adj_host_transaction_idx` (`host_transaction_id`),
  KEY `san_adj_business_status_date_idx` (`business_id`,`status`,`adjustment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `san_stock_adjustment_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `adjustment_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `variation_id` BIGINT UNSIGNED NULL,
  `product_name` VARCHAR(191) NULL,
  `sku` VARCHAR(191) NULL,
  `batch_no` VARCHAR(191) NULL,
  `expiry_date` DATE NULL,
  `system_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `counted_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `adjustment_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `stock_adjustment_type` VARCHAR(20) NOT NULL DEFAULT 'increase',
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `line_notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `san_line_adjustment_idx` (`adjustment_id`),
  KEY `san_line_product_idx` (`product_id`,`variation_id`),
  KEY `san_line_batch_idx` (`batch_no`),
  KEY `san_line_stock_type_idx` (`stock_adjustment_type`),
  KEY `san_line_product_batch_expiry_idx` (`product_id`,`batch_no`,`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `san_stock_adjustment_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `adjustment_id` BIGINT UNSIGNED NOT NULL,
  `adjustment_line_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `variation_id` BIGINT UNSIGNED NULL,
  `batch_no` VARCHAR(191) NULL,
  `movement_type` VARCHAR(30) NOT NULL,
  `qty_change` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `cost_change` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `movement_date` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `san_mov_scope_idx` (`business_id`,`location_id`,`store_id`),
  KEY `san_mov_product_idx` (`product_id`,`variation_id`),
  KEY `san_mov_adjustment_idx` (`adjustment_id`),
  KEY `san_mov_scope_date_idx` (`business_id`,`location_id`,`store_id`,`movement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `san_stock_adjustment_audits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `adjustment_id` BIGINT UNSIGNED NOT NULL,
  `event` VARCHAR(80) NOT NULL,
  `payload` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `san_audit_business_idx` (`business_id`),
  KEY `san_audit_adjustment_idx` (`adjustment_id`),
  KEY `san_audit_event_idx` (`event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `san_stock_adjustment_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `number_prefix` VARCHAR(30) NOT NULL DEFAULT 'SAN-',
  `number_padding` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `default_adjustment_type` VARCHAR(30) NOT NULL DEFAULT 'quantity',
  `default_page_size` SMALLINT UNSIGNED NOT NULL DEFAULT 25,
  `quantity_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 4,
  `amount_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 4,
  `require_reason` TINYINT(1) NOT NULL DEFAULT 0,
  `require_location` TINYINT(1) NOT NULL DEFAULT 1,
  `require_store` TINYINT(1) NOT NULL DEFAULT 0,
  `require_approval` TINYINT(1) NOT NULL DEFAULT 1,
  `auto_submit` TINYINT(1) NOT NULL DEFAULT 0,
  `auto_post_after_approval` TINYINT(1) NOT NULL DEFAULT 0,
  `require_batch_when_available` TINYINT(1) NOT NULL DEFAULT 1,
  `hide_zero_stock_products` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_negative_stock` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_zero_unit_cost` TINYINT(1) NOT NULL DEFAULT 1,
  `allow_backdated_adjustments` TINYINT(1) NOT NULL DEFAULT 1,
  `max_backdate_days` INT UNSIGNED NULL,
  `allow_future_dated_adjustments` TINYINT(1) NOT NULL DEFAULT 1,
  `batch_selection_method` VARCHAR(20) NOT NULL DEFAULT 'fefo',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `san_settings_business_unique` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `san_stock_adjustment_account_mappings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `effective_from` TIMESTAMP NULL,
  `adjustment_type` VARCHAR(30) NOT NULL DEFAULT 'quantity',
  `category_id` BIGINT UNSIGNED NULL,
  `sub_category_id` BIGINT UNSIGNED NULL,
  `account_to_link_id` BIGINT UNSIGNED NULL,
  `increase_account_id` BIGINT UNSIGNED NULL,
  `decrease_account_id` BIGINT UNSIGNED NULL,
  `stock_account_group_id` BIGINT UNSIGNED NULL,
  `stock_account_id` BIGINT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `san_mapping_business_idx` (`business_id`),
  KEY `san_mapping_effective_idx` (`effective_from`),
  KEY `san_mapping_type_idx` (`adjustment_type`),
  KEY `san_mapping_category_idx` (`category_id`),
  KEY `san_mapping_sub_category_idx` (`sub_category_id`),
  KEY `san_mapping_account_link_idx` (`account_to_link_id`),
  KEY `san_mapping_increase_account_idx` (`increase_account_id`),
  KEY `san_mapping_decrease_account_idx` (`decrease_account_id`),
  KEY `san_mapping_stock_group_idx` (`stock_account_group_id`),
  KEY `san_mapping_stock_account_idx` (`stock_account_id`),
  KEY `san_mapping_active_idx` (`is_active`),
  KEY `san_mapping_lookup_idx` (`business_id`,`adjustment_type`,`category_id`,`sub_category_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- =============================================================
-- END 01_Create_Tables.sql
-- =============================================================

-- =============================================================
-- BEGIN 02_Alter_Tables.sql
-- =============================================================
-- Stock Adjustment New - safe incremental columns for partially installed tenant databases
ALTER TABLE `san_stock_adjustment_reasons`
  ADD COLUMN IF NOT EXISTS `business_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `name` VARCHAR(191) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `code` VARCHAR(50) NULL,
  ADD COLUMN IF NOT EXISTS `effect` VARCHAR(20) NOT NULL DEFAULT 'both',
  ADD COLUMN IF NOT EXISTS `requires_approval` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL;

ALTER TABLE `san_stock_adjustments`
  ADD COLUMN IF NOT EXISTS `business_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `location_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `store_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `adjustment_no` VARCHAR(191) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `adjustment_date` DATE NULL,
  ADD COLUMN IF NOT EXISTS `adjustment_type` VARCHAR(50) NOT NULL DEFAULT 'quantity',
  ADD COLUMN IF NOT EXISTS `stock_adjustment_type` VARCHAR(20) NOT NULL DEFAULT 'increase',
  ADD COLUMN IF NOT EXISTS `reason_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  ADD COLUMN IF NOT EXISTS `total_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  ADD COLUMN IF NOT EXISTS `total_cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  ADD COLUMN IF NOT EXISTS `notes` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `approved_by` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `approved_at` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `approval_remarks` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `posted_by` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `posted_at` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `host_transaction_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `posting_summary` JSON NULL,
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL;

ALTER TABLE `san_stock_adjustment_lines`
  ADD COLUMN IF NOT EXISTS `adjustment_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `product_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `variation_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `product_name` VARCHAR(191) NULL,
  ADD COLUMN IF NOT EXISTS `sku` VARCHAR(191) NULL,
  ADD COLUMN IF NOT EXISTS `batch_no` VARCHAR(191) NULL,
  ADD COLUMN IF NOT EXISTS `expiry_date` DATE NULL,
  ADD COLUMN IF NOT EXISTS `system_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  ADD COLUMN IF NOT EXISTS `counted_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  ADD COLUMN IF NOT EXISTS `adjustment_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  ADD COLUMN IF NOT EXISTS `stock_adjustment_type` VARCHAR(20) NOT NULL DEFAULT 'increase',
  ADD COLUMN IF NOT EXISTS `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  ADD COLUMN IF NOT EXISTS `cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  ADD COLUMN IF NOT EXISTS `line_notes` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL;

ALTER TABLE `san_stock_adjustment_movements`
  ADD COLUMN IF NOT EXISTS `business_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `location_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `store_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `adjustment_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `adjustment_line_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `product_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `variation_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `batch_no` VARCHAR(191) NULL,
  ADD COLUMN IF NOT EXISTS `movement_type` VARCHAR(30) NOT NULL DEFAULT 'increase',
  ADD COLUMN IF NOT EXISTS `qty_change` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  ADD COLUMN IF NOT EXISTS `cost_change` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  ADD COLUMN IF NOT EXISTS `movement_date` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL;

ALTER TABLE `san_stock_adjustment_audits`
  ADD COLUMN IF NOT EXISTS `business_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `adjustment_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `event` VARCHAR(80) NOT NULL DEFAULT 'unknown',
  ADD COLUMN IF NOT EXISTS `payload` JSON NULL,
  ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL;

ALTER TABLE `san_stock_adjustment_settings`
  ADD COLUMN IF NOT EXISTS `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `number_prefix` VARCHAR(30) NOT NULL DEFAULT 'SAN-',
  ADD COLUMN IF NOT EXISTS `number_padding` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  ADD COLUMN IF NOT EXISTS `default_adjustment_type` VARCHAR(30) NOT NULL DEFAULT 'quantity',
  ADD COLUMN IF NOT EXISTS `default_page_size` SMALLINT UNSIGNED NOT NULL DEFAULT 25,
  ADD COLUMN IF NOT EXISTS `quantity_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 4,
  ADD COLUMN IF NOT EXISTS `amount_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 4,
  ADD COLUMN IF NOT EXISTS `require_reason` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `require_location` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `require_store` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `require_approval` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `auto_submit` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `auto_post_after_approval` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `require_batch_when_available` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `hide_zero_stock_products` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `allow_negative_stock` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `allow_zero_unit_cost` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `allow_backdated_adjustments` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `max_backdate_days` INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `allow_future_dated_adjustments` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `batch_selection_method` VARCHAR(20) NOT NULL DEFAULT 'fefo',
  ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `updated_by` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL;

ALTER TABLE `san_stock_adjustment_account_mappings`
  ADD COLUMN IF NOT EXISTS `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `effective_from` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `adjustment_type` VARCHAR(30) NOT NULL DEFAULT 'quantity',
  ADD COLUMN IF NOT EXISTS `category_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `sub_category_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `account_to_link_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `increase_account_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `decrease_account_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `stock_account_group_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `stock_account_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `notes` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `updated_by` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL;

-- =============================================================
-- END 02_Alter_Tables.sql
-- =============================================================

-- =============================================================
-- BEGIN 03_Create_Views.sql
-- =============================================================
CREATE OR REPLACE VIEW `san_v_stock_adjustment_summary` AS
SELECT a.business_id, a.location_id, a.store_id, a.status, DATE(a.adjustment_date) AS adjustment_date,
       COUNT(*) AS adjustment_count, SUM(a.total_qty) AS total_qty, SUM(a.total_cost_amount) AS total_cost_amount
FROM san_stock_adjustments a
GROUP BY a.business_id, a.location_id, a.store_id, a.status, DATE(a.adjustment_date);
-- =============================================================
-- END 03_Create_Views.sql
-- =============================================================

-- =============================================================
-- BEGIN 04_Create_Procedures.sql
-- =============================================================
-- SAN_001: No stored procedures are required.
-- Business logic is kept in standalone Laravel services for maintainability.
-- =============================================================
-- END 04_Create_Procedures.sql
-- =============================================================

-- =============================================================
-- BEGIN 05_Insert_Master_Data.sql
-- =============================================================
INSERT INTO `san_stock_adjustment_reasons`
(`business_id`,`name`,`code`,`effect`,`requires_approval`,`is_active`,`created_at`,`updated_at`)
SELECT NULL,'Physical stock count difference','COUNT_DIFF','both',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `san_stock_adjustment_reasons` WHERE `business_id` IS NULL AND `code`='COUNT_DIFF');

INSERT INTO `san_stock_adjustment_reasons`
(`business_id`,`name`,`code`,`effect`,`requires_approval`,`is_active`,`created_at`,`updated_at`)
SELECT NULL,'Damaged stock write-off','DAMAGE','decrease',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `san_stock_adjustment_reasons` WHERE `business_id` IS NULL AND `code`='DAMAGE');

INSERT INTO `san_stock_adjustment_reasons`
(`business_id`,`name`,`code`,`effect`,`requires_approval`,`is_active`,`created_at`,`updated_at`)
SELECT NULL,'Expired stock write-off','EXPIRY','decrease',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `san_stock_adjustment_reasons` WHERE `business_id` IS NULL AND `code`='EXPIRY');

INSERT INTO `san_stock_adjustment_reasons`
(`business_id`,`name`,`code`,`effect`,`requires_approval`,`is_active`,`created_at`,`updated_at`)
SELECT NULL,'System correction','SYSTEM_CORRECTION','both',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `san_stock_adjustment_reasons` WHERE `business_id` IS NULL AND `code`='SYSTEM_CORRECTION');

INSERT INTO `san_stock_adjustment_reasons`
(`business_id`,`name`,`code`,`effect`,`requires_approval`,`is_active`,`created_at`,`updated_at`)
SELECT NULL,'Opening balance correction','OPENING_CORRECTION','both',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `san_stock_adjustment_reasons` WHERE `business_id` IS NULL AND `code`='OPENING_CORRECTION');
-- =============================================================
-- END 05_Insert_Master_Data.sql
-- =============================================================

-- =============================================================
-- BEGIN 06_Insert_Permissions.sql
-- =============================================================
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.view','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.create','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.create' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.edit','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.edit' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.submit','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.submit' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.approve','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.approve' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.reject','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.reject' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.post','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.post' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.reports','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.reports' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.settings','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.settings' AND `guard_name`='web');
-- =============================================================
-- END 06_Insert_Permissions.sql
-- =============================================================

-- =============================================================
-- BEGIN 07_Insert_Menu.sql
-- =============================================================
-- Add this module to your existing sidebar/menu registry if your system uses DB-driven menus.
-- Safe placeholder because different ERP installations keep sidebars in JSON, PHP config, or DB tables.
-- Module: Stock Adjustment - New
-- Dashboard route: /stock-adjustment-new/dashboard
-- Settings route:  /stock-adjustment-new/settings
-- Permission:      stock_adjustment_new.settings
-- =============================================================
-- END 07_Insert_Menu.sql
-- =============================================================

-- =============================================================
-- BEGIN 08_Create_Indexes.sql
-- =============================================================
-- Indexes are created by 01_Create_Tables.sql for fresh installations.
-- This file safely adds them to older/partial installations.
SET @db := DATABASE();

SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db AND table_name='san_stock_adjustments' AND index_name='san_adj_business_status_date_idx'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustments` ADD INDEX `san_adj_business_status_date_idx` (`business_id`,`status`,`adjustment_date`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db AND table_name='san_stock_adjustment_lines' AND index_name='san_line_product_batch_expiry_idx'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustment_lines` ADD INDEX `san_line_product_batch_expiry_idx` (`product_id`,`batch_no`,`expiry_date`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db AND table_name='san_stock_adjustment_movements' AND index_name='san_mov_scope_date_idx'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustment_movements` ADD INDEX `san_mov_scope_date_idx` (`business_id`,`location_id`,`store_id`,`movement_date`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db AND table_name='san_stock_adjustment_settings' AND index_name='san_settings_business_unique'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustment_settings` ADD UNIQUE INDEX `san_settings_business_unique` (`business_id`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db AND table_name='san_stock_adjustment_account_mappings' AND index_name='san_mapping_lookup_idx'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustment_account_mappings` ADD INDEX `san_mapping_lookup_idx` (`business_id`,`adjustment_type`,`category_id`,`sub_category_id`,`is_active`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db AND table_name='san_stock_adjustment_account_mappings' AND index_name='san_mapping_increase_account_idx')
  OR NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='san_stock_adjustment_account_mappings' AND column_name='increase_account_id'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustment_account_mappings` ADD INDEX `san_mapping_increase_account_idx` (`increase_account_id`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db AND table_name='san_stock_adjustment_account_mappings' AND index_name='san_mapping_decrease_account_idx')
  OR NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='san_stock_adjustment_account_mappings' AND column_name='decrease_account_id'),
  'SELECT 1',
  'ALTER TABLE `san_stock_adjustment_account_mappings` ADD INDEX `san_mapping_decrease_account_idx` (`decrease_account_id`)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
-- =============================================================
-- END 08_Create_Indexes.sql
-- =============================================================

-- =============================================================
-- BEGIN 09_Update_Data.sql
-- =============================================================
-- SAN_001: No data update required.
-- =============================================================
-- END 09_Update_Data.sql
-- =============================================================

-- =============================================================
-- BEGIN 11_Settings_Upgrade.sql
-- =============================================================
-- S 577 - Stock Adjustment New Settings upgrade
-- Run on every tenant database. The script is idempotent and does not hardcode a database name.

CREATE TABLE IF NOT EXISTS `san_stock_adjustment_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `number_prefix` VARCHAR(30) NOT NULL DEFAULT 'SAN-',
  `number_padding` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `default_adjustment_type` VARCHAR(30) NOT NULL DEFAULT 'quantity',
  `default_page_size` SMALLINT UNSIGNED NOT NULL DEFAULT 25,
  `quantity_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 4,
  `amount_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 4,
  `require_reason` TINYINT(1) NOT NULL DEFAULT 0,
  `require_location` TINYINT(1) NOT NULL DEFAULT 1,
  `require_store` TINYINT(1) NOT NULL DEFAULT 0,
  `require_approval` TINYINT(1) NOT NULL DEFAULT 1,
  `auto_submit` TINYINT(1) NOT NULL DEFAULT 0,
  `auto_post_after_approval` TINYINT(1) NOT NULL DEFAULT 0,
  `require_batch_when_available` TINYINT(1) NOT NULL DEFAULT 1,
  `hide_zero_stock_products` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_negative_stock` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_zero_unit_cost` TINYINT(1) NOT NULL DEFAULT 1,
  `allow_backdated_adjustments` TINYINT(1) NOT NULL DEFAULT 1,
  `max_backdate_days` INT UNSIGNED NULL,
  `allow_future_dated_adjustments` TINYINT(1) NOT NULL DEFAULT 1,
  `batch_selection_method` VARCHAR(20) NOT NULL DEFAULT 'fefo',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `san_settings_business_unique` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `san_stock_adjustment_account_mappings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `effective_from` TIMESTAMP NULL,
  `adjustment_type` VARCHAR(30) NOT NULL DEFAULT 'quantity',
  `category_id` BIGINT UNSIGNED NULL,
  `sub_category_id` BIGINT UNSIGNED NULL,
  `account_to_link_id` BIGINT UNSIGNED NULL,
  `increase_account_id` BIGINT UNSIGNED NULL,
  `decrease_account_id` BIGINT UNSIGNED NULL,
  `stock_account_group_id` BIGINT UNSIGNED NULL,
  `stock_account_id` BIGINT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `san_mapping_business_idx` (`business_id`),
  KEY `san_mapping_effective_idx` (`effective_from`),
  KEY `san_mapping_type_idx` (`adjustment_type`),
  KEY `san_mapping_category_idx` (`category_id`),
  KEY `san_mapping_sub_category_idx` (`sub_category_id`),
  KEY `san_mapping_account_link_idx` (`account_to_link_id`),
  KEY `san_mapping_increase_account_idx` (`increase_account_id`),
  KEY `san_mapping_decrease_account_idx` (`decrease_account_id`),
  KEY `san_mapping_stock_group_idx` (`stock_account_group_id`),
  KEY `san_mapping_stock_account_idx` (`stock_account_id`),
  KEY `san_mapping_active_idx` (`is_active`),
  KEY `san_mapping_lookup_idx` (`business_id`,`adjustment_type`,`category_id`,`sub_category_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.settings','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (
    SELECT 1 FROM `permissions`
    WHERE `name`='stock_adjustment_new.settings' AND `guard_name`='web'
  );
-- =============================================================
-- END 11_Settings_Upgrade.sql
-- =============================================================

-- =============================================================
-- BEGIN 12_S592_Posting_And_Reference_Fix.sql
-- =============================================================
-- S592 - Stock Adjustment New posting, per-product Increase/Decrease and reference fix
-- Run on every tenant database. Safe to run repeatedly.
SET @san_db := DATABASE();

-- Preserve the original document Type (Quantity / Value / Damage / Expiry).
-- Direction is stored separately on the document and on every product line.
SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustments' AND column_name='stock_adjustment_type'),
  'ALTER TABLE `san_stock_adjustments` ADD COLUMN `stock_adjustment_type` VARCHAR(20) NOT NULL DEFAULT ''increase''',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines' AND column_name='stock_adjustment_type'),
  'ALTER TABLE `san_stock_adjustment_lines` ADD COLUMN `stock_adjustment_type` VARCHAR(20) NOT NULL DEFAULT ''increase''',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustments' AND column_name='host_transaction_id'),
  'ALTER TABLE `san_stock_adjustments` ADD COLUMN `host_transaction_id` BIGINT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustments' AND column_name='posting_summary'),
  'ALTER TABLE `san_stock_adjustments` ADD COLUMN `posting_summary` JSON NULL',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@san_db AND table_name='san_stock_adjustments' AND index_name='san_adj_stock_type_idx'),
  'ALTER TABLE `san_stock_adjustments` ADD INDEX `san_adj_stock_type_idx` (`stock_adjustment_type`)',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines')
  AND NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines' AND index_name='san_line_stock_type_idx'),
  'ALTER TABLE `san_stock_adjustment_lines` ADD INDEX `san_line_stock_type_idx` (`stock_adjustment_type`)',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@san_db AND table_name='san_stock_adjustments' AND index_name='san_adj_host_transaction_idx'),
  'ALTER TABLE `san_stock_adjustments` ADD INDEX `san_adj_host_transaction_idx` (`host_transaction_id`)',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

-- Existing line directions are derived from the already-calculated quantity difference.
SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines' AND column_name='stock_adjustment_type')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines' AND column_name='adjustment_qty'),
  'UPDATE `san_stock_adjustment_lines` SET `stock_adjustment_type`=CASE WHEN COALESCE(`adjustment_qty`,0)<0 THEN ''decrease'' ELSE ''increase'' END WHERE `stock_adjustment_type` IS NULL OR `stock_adjustment_type` NOT IN (''increase'',''decrease'') OR (`stock_adjustment_type`=''increase'' AND COALESCE(`adjustment_qty`,0)<0) OR (`stock_adjustment_type`=''decrease'' AND COALESCE(`adjustment_qty`,0)>0)',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

-- Header direction becomes Mixed when the same document contains both directions.
SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustments')
  AND EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustments' AND column_name='stock_adjustment_type')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustment_lines' AND column_name='adjustment_qty'),
  'UPDATE `san_stock_adjustments` a LEFT JOIN (SELECT `adjustment_id`, CASE WHEN MAX(CASE WHEN `adjustment_qty`>0 THEN 1 ELSE 0 END)=1 AND MAX(CASE WHEN `adjustment_qty`<0 THEN 1 ELSE 0 END)=1 THEN ''mixed'' WHEN MAX(CASE WHEN `adjustment_qty`<0 THEN 1 ELSE 0 END)=1 THEN ''decrease'' ELSE ''increase'' END AS `derived_direction` FROM `san_stock_adjustment_lines` GROUP BY `adjustment_id`) d ON d.`adjustment_id`=a.`id` SET a.`stock_adjustment_type`=COALESCE(d.`derived_direction`,CASE WHEN COALESCE(a.`total_qty`,0)<0 THEN ''decrease'' ELSE ''increase'' END) WHERE NOT (a.`stock_adjustment_type` <=> COALESCE(d.`derived_direction`,CASE WHEN COALESCE(a.`total_qty`,0)<0 THEN ''decrease'' ELSE ''increase'' END))',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

-- Keep the original document Type contract and repair only invalid settings.
SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustments' AND column_name='adjustment_type'),
  'ALTER TABLE `san_stock_adjustments` MODIFY COLUMN `adjustment_type` VARCHAR(50) NOT NULL DEFAULT ''quantity''',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustment_settings' AND column_name='default_adjustment_type'),
  'ALTER TABLE `san_stock_adjustment_settings` MODIFY COLUMN `default_adjustment_type` VARCHAR(30) NOT NULL DEFAULT ''quantity''',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustment_settings' AND column_name='default_adjustment_type'),
  'UPDATE `san_stock_adjustment_settings` SET `default_adjustment_type`=''quantity'' WHERE `default_adjustment_type` IS NULL OR `default_adjustment_type` NOT IN (''quantity'',''value'',''damage'',''expiry'')',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;

-- Posting must always be scoped to a real business location.
SET @san_sql := IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@san_db AND table_name='san_stock_adjustment_settings' AND column_name='require_location'),
  'UPDATE `san_stock_adjustment_settings` SET `require_location`=1 WHERE `require_location`<>1 OR `require_location` IS NULL',
  'SELECT 1'
);
PREPARE san_stmt FROM @san_sql; EXECUTE san_stmt; DEALLOCATE PREPARE san_stmt;
-- =============================================================
-- END 12_S592_Posting_And_Reference_Fix.sql
-- =============================================================
