-- StockTransferNew tenant database CREATE TABLE SQL
CREATE TABLE IF NOT EXISTS `stnew_stores` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`code` VARCHAR(191) NULL,`name` VARCHAR(191) NOT NULL,`is_active` TINYINT(1) NOT NULL DEFAULT 1,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,`deleted_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `stnew_stores_business_id_index` (`business_id`),KEY `stnew_stores_location_index` (`business_location_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `stnew_stock_transfers` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`transfer_no` VARCHAR(191) NOT NULL,`transfer_date` DATE NOT NULL,`from_location_id` BIGINT UNSIGNED NULL,`to_location_id` BIGINT UNSIGNED NULL,`from_store_id` BIGINT UNSIGNED NULL,`to_store_id` BIGINT UNSIGNED NULL,`status` VARCHAR(191) NOT NULL DEFAULT 'draft',`reason` TEXT NULL,`remarks` TEXT NULL,`approval_note` TEXT NULL,`rejection_note` TEXT NULL,`cancel_reason` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`submitted_by` BIGINT UNSIGNED NULL,`approved_by` BIGINT UNSIGNED NULL,`rejected_by` BIGINT UNSIGNED NULL,`dispatched_by` BIGINT UNSIGNED NULL,`received_by` BIGINT UNSIGNED NULL,`cancelled_by` BIGINT UNSIGNED NULL,`submitted_at` TIMESTAMP NULL,`approved_at` TIMESTAMP NULL,`rejected_at` TIMESTAMP NULL,`dispatched_at` TIMESTAMP NULL,`received_at` TIMESTAMP NULL,`cancelled_at` TIMESTAMP NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,`deleted_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `stnew_stock_transfers_business_transfer_unique` (`business_id`,`transfer_no`),KEY `stnew_stock_transfers_business_id_index` (`business_id`),KEY `stnew_stock_transfers_status_index` (`status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `stnew_stock_transfer_lines` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`transfer_id` BIGINT UNSIGNED NOT NULL,`product_id` BIGINT UNSIGNED NOT NULL,`variation_id` BIGINT UNSIGNED NULL,`qty_requested` DECIMAL(22,4) NOT NULL DEFAULT 0,`qty_dispatched` DECIMAL(22,4) NOT NULL DEFAULT 0,`qty_received` DECIMAL(22,4) NOT NULL DEFAULT 0,`unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,`line_total` DECIMAL(22,4) NOT NULL DEFAULT 0,`remarks` TEXT NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `stnew_stock_transfer_lines_transfer_id_index` (`transfer_id`),KEY `stnew_stock_transfer_lines_product_id_index` (`product_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `stnew_stock_transfer_audits` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`transfer_id` BIGINT UNSIGNED NOT NULL,`business_id` BIGINT UNSIGNED NOT NULL,`action` VARCHAR(191) NOT NULL,`note` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `stnew_stock_transfer_audits_transfer_id_index` (`transfer_id`),KEY `stnew_stock_transfer_audits_business_id_index` (`business_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `stnew_stock_transfer_settings` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`approval_required` TINYINT(1) NOT NULL DEFAULT 1,`allow_partial_receive` TINYINT(1) NOT NULL DEFAULT 1,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `stnew_stock_transfer_settings_business_unique` (`business_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Defaults: see 02_TENANT_INSERT_DEFAULTS_STOCK_TRANSFER_NEW.sql
ALTER TABLE `stnew_stock_transfer_lines`
  ADD COLUMN IF NOT EXISTS `short_qty` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `qty_received`,
  ADD COLUMN IF NOT EXISTS `excess_qty` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `short_qty`;

CREATE TABLE IF NOT EXISTS `stnew_stock_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `stock_transfer_id` BIGINT UNSIGNED NOT NULL,
  `stock_transfer_line_id` BIGINT UNSIGNED NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `variation_id` BIGINT UNSIGNED NULL,
  `from_location_id` BIGINT UNSIGNED NULL,
  `to_location_id` BIGINT UNSIGNED NULL,
  `from_store_id` BIGINT UNSIGNED NULL,
  `to_store_id` BIGINT UNSIGNED NULL,
  `movement_type` VARCHAR(30) NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `total_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reference_no` VARCHAR(191) NULL,
  `movement_date` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `stnew_mov_business_idx` (`business_id`),
  KEY `stnew_mov_transfer_idx` (`stock_transfer_id`),
  KEY `stnew_mov_line_idx` (`stock_transfer_line_id`),
  KEY `stnew_mov_product_idx` (`product_id`),
  KEY `stnew_mov_variation_idx` (`variation_id`),
  KEY `stnew_mov_from_location_idx` (`from_location_id`),
  KEY `stnew_mov_to_location_idx` (`to_location_id`),
  KEY `stnew_mov_from_store_idx` (`from_store_id`),
  KEY `stnew_mov_to_store_idx` (`to_store_id`),
  KEY `stnew_mov_type_idx` (`movement_type`),
  KEY `stnew_mov_ref_idx` (`reference_no`),
  KEY `stnew_mov_date_idx` (`movement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- STN_003 additions are in 05_TENANT_ALTER_BALANCES_DOCUMENTS_STOCK_TRANSFER_NEW.sql


-- STN_004 Product bridge: no duplicate product tables. See 06_TENANT_PRODUCT_BRIDGE_STOCK_TRANSFER_NEW.sql.



-- StockTransferNew STN_005 tenant SQL
ALTER TABLE stnew_stock_transfer_settings
  ADD COLUMN IF NOT EXISTS require_stock_before_dispatch TINYINT(1) NOT NULL DEFAULT 0 AFTER allow_partial_receive,
  ADD COLUMN IF NOT EXISTS auto_generate_document_numbers TINYINT(1) NOT NULL DEFAULT 1 AFTER require_stock_before_dispatch;

CREATE TABLE IF NOT EXISTS stnew_stock_transfer_alerts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  transfer_id BIGINT UNSIGNED NOT NULL,
  event VARCHAR(191) NOT NULL,
  message TEXT NULL,
  target_user_id BIGINT UNSIGNED NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX stnew_alert_business_idx (business_id),
  INDEX stnew_alert_transfer_idx (transfer_id),
  INDEX stnew_alert_event_idx (event),
  INDEX stnew_alert_user_idx (target_user_id),
  INDEX stnew_alert_read_idx (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permission keys: stocktransfernew.view, stocktransfernew.create, stocktransfernew.edit,
-- stocktransfernew.submit, stocktransfernew.approve, stocktransfernew.dispatch,
-- stocktransfernew.receive, stocktransfernew.reports, stocktransfernew.settings, stocktransfernew.export

-- ================= STN_006 =================
SOURCE Modules/StockTransferNew/Database/SQL/08_TENANT_RECONCILIATION_RETURNS_TEMPLATES_STOCK_TRANSFER_NEW.sql;
