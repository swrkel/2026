-- Rice Mill Module Master SQL
-- Safe for a fresh tenant database. All module tables use rcm_ prefix.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `rcm_settings` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`settings` JSON NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `rcm_settings_business_unique` (`business_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_number_series` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`series_type` VARCHAR(60) NOT NULL,`prefix` VARCHAR(20) NOT NULL,`next_number` BIGINT UNSIGNED NOT NULL DEFAULT 1,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `rcm_number_series_unique` (`business_id`,`series_type`),KEY `rcm_number_series_business_idx` (`business_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_paddy_varieties` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`paddy_product_id` BIGINT UNSIGNED NULL,`code` VARCHAR(30) NULL,`name` VARCHAR(120) NOT NULL,`default_moisture_percent` DECIMAL(8,3) NULL,`foreign_matter_limit_percent` DECIMAL(8,3) NULL,`expected_rice_yield_percent` DECIMAL(8,3) NULL,`expected_broken_rice_percent` DECIMAL(8,3) NULL,`expected_bran_percent` DECIMAL(8,3) NULL,`expected_husk_percent` DECIMAL(8,3) NULL,`expected_process_loss_percent` DECIMAL(8,3) NULL,`quality_grade` VARCHAR(50) NULL,`lot_opening_number` BIGINT UNSIGNED NOT NULL DEFAULT 1,`active` TINYINT(1) NOT NULL DEFAULT 1,`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `rcm_paddy_variety_unique` (`business_id`,`name`),UNIQUE KEY `rcm_paddy_variety_business_product_unique` (`business_id`,`paddy_product_id`),KEY `rcm_paddy_variety_business_idx` (`business_id`),KEY `rcm_paddy_variety_product_idx` (`paddy_product_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_mills` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`location_id` BIGINT UNSIGNED NULL,`code` VARCHAR(30) NULL,`name` VARCHAR(120) NOT NULL,`capacity_per_hour` DECIMAL(20,3) NULL,`active` TINYINT(1) NOT NULL DEFAULT 1,`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `rcm_mills_business_idx` (`business_id`),KEY `rcm_mills_location_idx` (`location_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_products` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`products_new_product_id` BIGINT UNSIGNED NULL,`code` VARCHAR(40) NULL,`name` VARCHAR(150) NOT NULL,`rice_type` VARCHAR(100) NULL,`paddy_variety_id` BIGINT UNSIGNED NULL,`current_qty` DECIMAL(20,3) NOT NULL DEFAULT 0,`active` TINYINT(1) NOT NULL DEFAULT 1,`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `rcm_product_unique` (`business_id`,`name`),UNIQUE KEY `rcm_products_business_products_new_unique` (`business_id`,`products_new_product_id`),KEY `rcm_products_business_idx` (`business_id`),KEY `rcm_products_products_new_product_idx` (`products_new_product_id`),KEY `rcm_products_business_paddy_variety_idx` (`business_id`,`paddy_variety_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_paddy_purchases` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,`purchase_no` VARCHAR(40) NOT NULL,`purchase_date` DATE NOT NULL,`supplier_id` BIGINT UNSIGNED NOT NULL,`subtotal` DECIMAL(20,4) NOT NULL DEFAULT 0,`other_charges` DECIMAL(20,4) NOT NULL DEFAULT 0,`net_total` DECIMAL(20,4) NOT NULL DEFAULT 0,`status` VARCHAR(30) NOT NULL DEFAULT 'draft',`note` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`approved_by` BIGINT UNSIGNED NULL,`approved_at` TIMESTAMP NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `rcm_purchase_unique` (`business_id`,`purchase_no`),KEY `rcm_purchase_business_idx` (`business_id`),KEY `rcm_purchase_supplier_idx` (`supplier_id`),KEY `rcm_purchase_status_idx` (`status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_paddy_purchase_lines` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`purchase_id` BIGINT UNSIGNED NOT NULL,`paddy_variety_id` BIGINT UNSIGNED NOT NULL,`net_weight` DECIMAL(20,3) NOT NULL,`unit_rate` DECIMAL(20,4) NOT NULL,`deduction_amount` DECIMAL(20,4) NOT NULL DEFAULT 0,`line_total` DECIMAL(20,4) NOT NULL DEFAULT 0,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `rcm_ppl_business_idx` (`business_id`),KEY `rcm_ppl_purchase_idx` (`purchase_id`),KEY `rcm_ppl_variety_idx` (`paddy_variety_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_weighbridge_entries` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`location_id` BIGINT UNSIGNED NULL,`entry_no` VARCHAR(40) NOT NULL,`vehicle_no` VARCHAR(50) NULL,`supplier_id` BIGINT UNSIGNED NULL,`gross_weight` DECIMAL(20,3) NOT NULL,`tare_weight` DECIMAL(20,3) NOT NULL,`net_weight` DECIMAL(20,3) NOT NULL,`receipt_id` BIGINT UNSIGNED NULL,`weighed_at` TIMESTAMP NULL,`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `rcm_we_unique` (`business_id`,`entry_no`),KEY `rcm_we_business_idx` (`business_id`),KEY `rcm_we_supplier_idx` (`supplier_id`),KEY `rcm_we_receipt_idx` (`receipt_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_paddy_receipts` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,`receipt_no` VARCHAR(40) NOT NULL,`weighbridge_entry_id` BIGINT UNSIGNED NULL,`purchase_id` BIGINT UNSIGNED NULL,`supplier_id` BIGINT UNSIGNED NOT NULL,`paddy_variety_id` BIGINT UNSIGNED NOT NULL,`paddy_lot_id` BIGINT UNSIGNED NULL,`vehicle_no` VARCHAR(50) NULL,`gross_weight` DECIMAL(20,3) NOT NULL,`tare_weight` DECIMAL(20,3) NOT NULL,`net_weight` DECIMAL(20,3) NOT NULL,`moisture_percent` DECIMAL(8,3) NULL,`foreign_matter_percent` DECIMAL(8,3) NULL,`foreign_matter_limit_percent` DECIMAL(8,3) NULL,`quality_grade` VARCHAR(50) NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'received',`received_at` TIMESTAMP NOT NULL,`note` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `rcm_receipt_unique` (`business_id`,`receipt_no`),KEY `rcm_receipt_business_idx` (`business_id`),KEY `rcm_receipt_purchase_idx` (`purchase_id`),KEY `rcm_receipt_supplier_idx` (`supplier_id`),KEY `rcm_receipt_variety_idx` (`paddy_variety_id`),KEY `rcm_receipt_lot_idx` (`paddy_lot_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_paddy_lots` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,`lot_no` VARCHAR(40) NOT NULL,`receipt_id` BIGINT UNSIGNED NULL,`paddy_variety_id` BIGINT UNSIGNED NOT NULL,`supplier_id` BIGINT UNSIGNED NULL,`original_qty` DECIMAL(20,3) NOT NULL,`balance_qty` DECIMAL(20,3) NOT NULL DEFAULT 0,`moisture_percent` DECIMAL(8,3) NULL,`quality_grade` VARCHAR(50) NULL,`received_date` DATE NOT NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'available',`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `rcm_lot_unique` (`business_id`,`lot_no`),KEY `rcm_lot_business_idx` (`business_id`),KEY `rcm_lot_balance_idx` (`balance_qty`),KEY `rcm_lot_receipt_idx` (`receipt_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_paddy_stock_movements` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,`paddy_lot_id` BIGINT UNSIGNED NOT NULL,`movement_date` DATE NOT NULL,`movement_type` VARCHAR(40) NOT NULL,`quantity` DECIMAL(20,3) NOT NULL,`signed_quantity` DECIMAL(20,3) NOT NULL,`reference_type` VARCHAR(60) NULL,`reference_id` BIGINT UNSIGNED NULL,`note` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `rcm_psm_business_idx` (`business_id`),KEY `rcm_psm_lot_idx` (`paddy_lot_id`),KEY `rcm_psm_date_idx` (`movement_date`),KEY `rcm_psm_ref_idx` (`reference_type`,`reference_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_production_batches` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,`mill_id` BIGINT UNSIGNED NULL,`batch_no` VARCHAR(40) NOT NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'draft',`started_at` TIMESTAMP NULL,`completed_at` TIMESTAMP NULL,`input_qty` DECIMAL(20,3) NOT NULL DEFAULT 0,`rice_output_qty` DECIMAL(20,3) NOT NULL DEFAULT 0,`total_output_qty` DECIMAL(20,3) NOT NULL DEFAULT 0,`process_loss_qty` DECIMAL(20,3) NOT NULL DEFAULT 0,`rice_yield_percent` DECIMAL(10,4) NOT NULL DEFAULT 0,`production_cost` DECIMAL(20,4) NOT NULL DEFAULT 0,`cost_per_kg` DECIMAL(20,4) NOT NULL DEFAULT 0,`note` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`completed_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `rcm_batch_unique` (`business_id`,`batch_no`),KEY `rcm_batch_business_idx` (`business_id`),KEY `rcm_batch_status_idx` (`status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_production_inputs` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`production_batch_id` BIGINT UNSIGNED NOT NULL,`paddy_lot_id` BIGINT UNSIGNED NOT NULL,`quantity` DECIMAL(20,3) NOT NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `rcm_pi_business_idx` (`business_id`),KEY `rcm_pi_batch_idx` (`production_batch_id`),KEY `rcm_pi_lot_idx` (`paddy_lot_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_production_outputs` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`production_batch_id` BIGINT UNSIGNED NOT NULL,`output_type` VARCHAR(40) NOT NULL,`product_id` BIGINT UNSIGNED NULL,`quantity` DECIMAL(20,3) NOT NULL,`unit_cost` DECIMAL(20,4) NOT NULL DEFAULT 0,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `rcm_po_business_idx` (`business_id`),KEY `rcm_po_batch_idx` (`production_batch_id`),KEY `rcm_po_type_idx` (`output_type`),KEY `rcm_po_product_idx` (`product_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_byproduct_movements` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,`byproduct_type` VARCHAR(60) NOT NULL,`movement_date` DATE NOT NULL,`movement_type` VARCHAR(40) NOT NULL,`quantity` DECIMAL(20,3) NOT NULL,`signed_quantity` DECIMAL(20,3) NOT NULL,`reference_type` VARCHAR(60) NULL,`reference_id` BIGINT UNSIGNED NULL,`note` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `rcm_bpm_business_idx` (`business_id`),KEY `rcm_bpm_type_idx` (`byproduct_type`),KEY `rcm_bpm_date_idx` (`movement_date`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_packing_batches` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,`packing_no` VARCHAR(40) NOT NULL,`packed_at` TIMESTAMP NULL,`total_packed_qty` DECIMAL(20,3) NOT NULL DEFAULT 0,`status` VARCHAR(30) NOT NULL DEFAULT 'completed',`note` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `rcm_packing_unique` (`business_id`,`packing_no`),KEY `rcm_packing_business_idx` (`business_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_packing_lines` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`packing_batch_id` BIGINT UNSIGNED NOT NULL,`product_id` BIGINT UNSIGNED NOT NULL,`bag_size_kg` DECIMAL(12,3) NOT NULL,`bag_count` INT UNSIGNED NOT NULL,`total_qty` DECIMAL(20,3) NOT NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `rcm_pl_business_idx` (`business_id`),KEY `rcm_pl_batch_idx` (`packing_batch_id`),KEY `rcm_pl_product_idx` (`product_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_packaging_materials` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`code` VARCHAR(40) NULL,`name` VARCHAR(150) NOT NULL,`unit` VARCHAR(30) NOT NULL DEFAULT 'pcs',`current_qty` DECIMAL(20,4) NOT NULL DEFAULT 0,`active` TINYINT(1) NOT NULL DEFAULT 1,`note` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `rcm_pack_material_business_name_unique` (`business_id`,`name`),KEY `rcm_pack_material_business_idx` (`business_id`),KEY `rcm_pack_material_business_code_idx` (`business_id`,`code`),KEY `rcm_pack_material_active_idx` (`active`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_packaging_material_mappings` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`product_id` BIGINT UNSIGNED NOT NULL,`bag_size_kg` DECIMAL(12,3) NOT NULL,`material_id` BIGINT UNSIGNED NOT NULL,`usage_per_bag` DECIMAL(20,4) NOT NULL,`active` TINYINT(1) NOT NULL DEFAULT 1,`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `rcm_pack_material_mapping_unique` (`business_id`,`product_id`,`bag_size_kg`,`material_id`),KEY `rcm_pack_material_mapping_business_idx` (`business_id`),KEY `rcm_pack_material_mapping_product_idx` (`product_id`),KEY `rcm_pack_material_mapping_material_idx` (`material_id`),KEY `rcm_pack_material_mapping_lookup_idx` (`business_id`,`product_id`,`bag_size_kg`,`active`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_packaging_material_movements` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`material_id` BIGINT UNSIGNED NOT NULL,`location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,`movement_date` DATE NOT NULL,`movement_type` VARCHAR(40) NOT NULL,`quantity` DECIMAL(20,4) NOT NULL,`signed_quantity` DECIMAL(20,4) NOT NULL,`packing_batch_id` BIGINT UNSIGNED NULL,`reference_type` VARCHAR(60) NULL,`reference_id` BIGINT UNSIGNED NULL,`note` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `rcm_pack_material_move_business_idx` (`business_id`),KEY `rcm_pack_material_move_material_idx` (`material_id`),KEY `rcm_pack_material_move_date_idx` (`movement_date`),KEY `rcm_pack_material_move_type_idx` (`movement_type`),KEY `rcm_pack_material_move_packing_idx` (`packing_batch_id`),KEY `rcm_pack_material_move_ledger_idx` (`business_id`,`material_id`,`movement_date`),KEY `rcm_pack_material_move_ref_idx` (`reference_type`,`reference_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_packing_sources` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`packing_line_id` BIGINT UNSIGNED NOT NULL,`production_batch_id` BIGINT UNSIGNED NULL,`quantity` DECIMAL(20,3) NOT NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `rcm_packing_source_business_idx` (`business_id`),KEY `rcm_packing_source_line_idx` (`packing_line_id`),KEY `rcm_packing_source_production_idx` (`production_batch_id`),KEY `rcm_packing_source_batch_idx` (`business_id`,`production_batch_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_finished_stock_movements` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,`product_id` BIGINT UNSIGNED NOT NULL,`movement_date` DATE NOT NULL,`movement_type` VARCHAR(40) NOT NULL,`quantity` DECIMAL(20,3) NOT NULL,`signed_quantity` DECIMAL(20,3) NOT NULL,`reference_type` VARCHAR(60) NULL,`reference_id` BIGINT UNSIGNED NULL,`note` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `rcm_fsm_business_idx` (`business_id`),KEY `rcm_fsm_product_idx` (`product_id`),KEY `rcm_fsm_date_idx` (`movement_date`),KEY `rcm_fsm_ref_idx` (`reference_type`,`reference_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_dispatches` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,`dispatch_no` VARCHAR(40) NOT NULL,`dispatch_date` DATE NOT NULL,`customer_id` BIGINT UNSIGNED NOT NULL,`vehicle_no` VARCHAR(60) NULL,`driver_name` VARCHAR(120) NULL,`subtotal` DECIMAL(20,4) NOT NULL DEFAULT 0,`unit_discount_amount` DECIMAL(20,4) NOT NULL DEFAULT 0,`discount_type` VARCHAR(20) NOT NULL DEFAULT 'fixed',`discount_value` DECIMAL(20,4) NOT NULL DEFAULT 0,`discount_amount` DECIMAL(20,4) NOT NULL DEFAULT 0,`tax_percent` DECIMAL(10,4) NOT NULL DEFAULT 0,`tax_amount` DECIMAL(20,4) NOT NULL DEFAULT 0,`net_total` DECIMAL(20,4) NOT NULL DEFAULT 0,`status` VARCHAR(30) NOT NULL DEFAULT 'draft',`note` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`approved_by` BIGINT UNSIGNED NULL,`approved_at` TIMESTAMP NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `rcm_dispatch_unique` (`business_id`,`dispatch_no`),KEY `rcm_dispatch_business_idx` (`business_id`),KEY `rcm_dispatch_customer_idx` (`customer_id`),KEY `rcm_dispatch_date_idx` (`dispatch_date`),KEY `rcm_dispatch_status_idx` (`status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_dispatch_lines` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`dispatch_id` BIGINT UNSIGNED NOT NULL,`product_id` BIGINT UNSIGNED NOT NULL,`quantity` DECIMAL(20,3) NOT NULL,`unit_price` DECIMAL(20,4) NOT NULL,`unit_discount_type` VARCHAR(20) NOT NULL DEFAULT 'fixed',`unit_discount_value` DECIMAL(20,4) NOT NULL DEFAULT 0,`unit_discount_amount` DECIMAL(20,4) NOT NULL DEFAULT 0,`net_unit_price` DECIMAL(20,4) NOT NULL DEFAULT 0,`line_total` DECIMAL(20,4) NOT NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `rcm_dl_business_idx` (`business_id`),KEY `rcm_dl_dispatch_idx` (`dispatch_id`),KEY `rcm_dl_product_idx` (`product_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_cost_entries` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`production_batch_id` BIGINT UNSIGNED NULL,`cost_date` DATE NOT NULL,`cost_type` VARCHAR(80) NOT NULL,`amount` DECIMAL(20,4) NOT NULL,`note` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `rcm_cost_business_idx` (`business_id`),KEY `rcm_cost_batch_idx` (`production_batch_id`),KEY `rcm_cost_date_idx` (`cost_date`),KEY `rcm_cost_type_idx` (`cost_type`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_finance_outbox` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`event_type` VARCHAR(80) NOT NULL,`source_type` VARCHAR(80) NOT NULL,`source_id` BIGINT UNSIGNED NOT NULL,`payload` JSON NOT NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'pending',`attempts` INT UNSIGNED NOT NULL DEFAULT 0,`last_error` TEXT NULL,`processed_at` TIMESTAMP NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `rcm_fo_business_idx` (`business_id`),KEY `rcm_fo_event_idx` (`event_type`),KEY `rcm_fo_status_idx` (`status`),KEY `rcm_fo_source_idx` (`source_type`,`source_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rcm_integration_logs` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` BIGINT UNSIGNED NOT NULL,`integration` VARCHAR(80) NOT NULL,`direction` VARCHAR(20) NOT NULL,`status` VARCHAR(30) NOT NULL,`payload` JSON NULL,`message` TEXT NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `rcm_il_business_idx` (`business_id`),KEY `rcm_il_integration_idx` (`integration`),KEY `rcm_il_status_idx` (`status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;

-- IS2289-2 / 17 Sep 2026 - Purchase Order payment posting audit/link table
CREATE TABLE IF NOT EXISTS `rcm_purchase_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `purchase_id` BIGINT UNSIGNED NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_method` VARCHAR(60) NOT NULL,
  `payment_method_label` VARCHAR(120) NULL,
  `payable_account_id` BIGINT UNSIGNED NOT NULL,
  `payment_account_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL,
  `cheque_number` VARCHAR(120) NULL,
  `note` TEXT NULL,
  `debit_account_transaction_id` BIGINT UNSIGNED NULL,
  `credit_account_transaction_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rcm_purchase_payment_unique` (`business_id`,`purchase_id`),
  KEY `rcm_purchase_payment_business_idx` (`business_id`),
  KEY `rcm_purchase_payment_date_idx` (`payment_date`),
  KEY `rcm_purchase_payment_payable_idx` (`payable_account_id`),
  KEY `rcm_purchase_payment_account_idx` (`payment_account_id`),
  KEY `rcm_purchase_payment_debit_tx_idx` (`debit_account_transaction_id`),
  KEY `rcm_purchase_payment_credit_tx_idx` (`credit_account_transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Rice Mill - IS2289-2 follow-up - 17 Sep 2026
-- Standard Purchase payment flow + deferred Purchase Tax at Purchase Order stage.
-- Tenant DB incremental SQL. Safe to run after the previous IS2289-2 SQL.
-- It is also safe when rcm_purchase_payments does not yet exist.

CREATE TABLE IF NOT EXISTS `rcm_purchase_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `purchase_id` BIGINT UNSIGNED NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_method` VARCHAR(60) NOT NULL,
  `payment_method_label` VARCHAR(120) NULL,
  `payable_account_id` BIGINT UNSIGNED NOT NULL,
  `payment_account_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `cheque_number` VARCHAR(120) NULL,
  `note` TEXT NULL,
  `debit_account_transaction_id` BIGINT UNSIGNED NULL,
  `credit_account_transaction_id` BIGINT UNSIGNED NULL,
  `transaction_id` BIGINT UNSIGNED NULL,
  `transaction_payment_id` BIGINT UNSIGNED NULL,
  `payment_status` VARCHAR(30) NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rcm_purchase_payment_unique` (`business_id`,`purchase_id`),
  KEY `rcm_purchase_payment_business_idx` (`business_id`),
  KEY `rcm_purchase_payment_date_idx` (`payment_date`),
  KEY `rcm_purchase_payment_payable_idx` (`payable_account_id`),
  KEY `rcm_purchase_payment_account_idx` (`payment_account_id`),
  KEY `rcm_purchase_payment_transaction_idx` (`transaction_id`),
  KEY `rcm_purchase_payment_transaction_payment_idx` (`transaction_payment_id`),
  KEY `rcm_purchase_payment_status_idx` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add missing columns without relying on ALTER ... IF NOT EXISTS syntax.
SET @db := DATABASE();

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_paddy_purchases' AND COLUMN_NAME='purchase_tax_id')=0,
 'ALTER TABLE `rcm_paddy_purchases` ADD COLUMN `purchase_tax_id` BIGINT UNSIGNED NULL, ADD INDEX `rcm_paddy_purchase_tax_idx` (`purchase_tax_id`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_paddy_purchases' AND COLUMN_NAME='purchase_tax_percent')=0,
 'ALTER TABLE `rcm_paddy_purchases` ADD COLUMN `purchase_tax_percent` DECIMAL(10,4) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_paddy_purchases' AND COLUMN_NAME='purchase_tax_amount')=0,
 'ALTER TABLE `rcm_paddy_purchases` ADD COLUMN `purchase_tax_amount` DECIMAL(20,4) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_paddy_purchases' AND COLUMN_NAME='core_transaction_id')=0,
 'ALTER TABLE `rcm_paddy_purchases` ADD COLUMN `core_transaction_id` BIGINT UNSIGNED NULL, ADD INDEX `rcm_paddy_purchase_core_tx_idx` (`core_transaction_id`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_paddy_purchases' AND COLUMN_NAME='payment_status')=0,
 'ALTER TABLE `rcm_paddy_purchases` ADD COLUMN `payment_status` VARCHAR(30) NOT NULL DEFAULT ''due'', ADD INDEX `rcm_paddy_purchase_payment_status_idx` (`payment_status`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_purchase_payments' AND COLUMN_NAME='transaction_id')=0,
 'ALTER TABLE `rcm_purchase_payments` ADD COLUMN `transaction_id` BIGINT UNSIGNED NULL, ADD INDEX `rcm_purchase_payment_transaction_idx` (`transaction_id`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_purchase_payments' AND COLUMN_NAME='transaction_payment_id')=0,
 'ALTER TABLE `rcm_purchase_payments` ADD COLUMN `transaction_payment_id` BIGINT UNSIGNED NULL, ADD INDEX `rcm_purchase_payment_transaction_payment_idx` (`transaction_payment_id`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_purchase_payments' AND COLUMN_NAME='payment_status')=0,
 'ALTER TABLE `rcm_purchase_payments` ADD COLUMN `payment_status` VARCHAR(30) NULL, ADD INDEX `rcm_purchase_payment_status_idx` (`payment_status`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- No tax ledger rows are created by this SQL. Purchase Tax is only retained
-- on rcm_paddy_purchases at Purchase Order stage; actual tax posting is deferred
-- until the future actual-purchase/receipt flow.

-- ============================================================================
-- 18 Sep 2026 - Products New Master Link (upgrade-safe)
-- ============================================================================
-- Rice Mill - Products New LIVE Master Correction
-- 18 Sep 2026
-- Run in EACH tenant database that uses Rice Mill.
-- Safe to re-run.
--
-- IMPORTANT - verified against the Products New module code:
--   Products New categories are stored in `categories`.
--   Products New products are stored in `products`.
--   rcm_paddy_varieties.paddy_product_id = products.id
--   rcm_products.products_new_product_id = products.id
--
-- This patch also repairs IDs written by the previous interim Rice Mill build
-- that incorrectly treated products_new_products as the live Products New table.

SET @rcm_db := DATABASE();

-- Ensure Paddy Variety link column exists.
SELECT COUNT(*) INTO @rcm_has_paddy_col
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=@rcm_db AND TABLE_NAME='rcm_paddy_varieties' AND COLUMN_NAME='paddy_product_id';
SET @rcm_sql := IF(@rcm_has_paddy_col=0,
 'ALTER TABLE `rcm_paddy_varieties` ADD COLUMN `paddy_product_id` BIGINT UNSIGNED NULL AFTER `business_id`',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

SELECT COUNT(*) INTO @rcm_has_paddy_idx
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@rcm_db AND TABLE_NAME='rcm_paddy_varieties' AND INDEX_NAME='rcm_paddy_variety_product_idx';
SET @rcm_sql := IF(@rcm_has_paddy_idx=0,
 'ALTER TABLE `rcm_paddy_varieties` ADD INDEX `rcm_paddy_variety_product_idx` (`paddy_product_id`)',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

SELECT COUNT(*) INTO @rcm_has_paddy_uq
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@rcm_db AND TABLE_NAME='rcm_paddy_varieties' AND INDEX_NAME='rcm_paddy_variety_business_product_unique';
SET @rcm_sql := IF(@rcm_has_paddy_uq=0,
 'ALTER TABLE `rcm_paddy_varieties` ADD UNIQUE INDEX `rcm_paddy_variety_business_product_unique` (`business_id`,`paddy_product_id`)',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

-- Ensure Rice Product live Products New link column exists.
SELECT COUNT(*) INTO @rcm_has_rice_col
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=@rcm_db AND TABLE_NAME='rcm_products' AND COLUMN_NAME='products_new_product_id';
SET @rcm_sql := IF(@rcm_has_rice_col=0,
 'ALTER TABLE `rcm_products` ADD COLUMN `products_new_product_id` BIGINT UNSIGNED NULL AFTER `business_id`',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

SELECT COUNT(*) INTO @rcm_has_rice_idx
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@rcm_db AND TABLE_NAME='rcm_products' AND INDEX_NAME='rcm_products_products_new_product_idx';
SET @rcm_sql := IF(@rcm_has_rice_idx=0,
 'ALTER TABLE `rcm_products` ADD INDEX `rcm_products_products_new_product_idx` (`products_new_product_id`)',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

SELECT COUNT(*) INTO @rcm_has_rice_uq
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=@rcm_db AND TABLE_NAME='rcm_products' AND INDEX_NAME='rcm_products_business_products_new_unique';
SET @rcm_sql := IF(@rcm_has_rice_uq=0,
 'ALTER TABLE `rcm_products` ADD UNIQUE INDEX `rcm_products_business_products_new_unique` (`business_id`,`products_new_product_id`)',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

SELECT COUNT(*) INTO @rcm_has_products
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=@rcm_db AND TABLE_NAME='products';

-- Clear any Paddy link that is not the same Products New live product by
-- ID + Business + Name + SKU, then safely backfill from products.id.
SET @rcm_sql := IF(@rcm_has_products>0,
 'UPDATE `rcm_paddy_varieties` pv
  LEFT JOIN `products` p
    ON p.`id`=pv.`paddy_product_id`
   AND p.`business_id`=pv.`business_id`
   AND TRIM(p.`name`)=TRIM(pv.`name`)
   AND TRIM(COALESCE(p.`sku`,CHAR(0)))=TRIM(COALESCE(pv.`code`,CHAR(0)))
  SET pv.`paddy_product_id`=NULL
  WHERE pv.`paddy_product_id` IS NOT NULL AND p.`id` IS NULL',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

SET @rcm_sql := IF(@rcm_has_products>0,
 'UPDATE `rcm_paddy_varieties` pv
  INNER JOIN `products` p
    ON p.`business_id`=pv.`business_id`
   AND TRIM(p.`name`)=TRIM(pv.`name`)
   AND TRIM(COALESCE(p.`sku`,CHAR(0)))=TRIM(COALESCE(pv.`code`,CHAR(0)))
  LEFT JOIN `rcm_paddy_varieties` duplicate_link
    ON duplicate_link.`business_id`=pv.`business_id`
   AND duplicate_link.`paddy_product_id`=p.`id`
   AND duplicate_link.`id`<>pv.`id`
  SET pv.`paddy_product_id`=p.`id`
  WHERE pv.`paddy_product_id` IS NULL AND duplicate_link.`id` IS NULL',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

-- Repair/backfill Rice Product live Products New IDs the same way.
SET @rcm_sql := IF(@rcm_has_products>0,
 'UPDATE `rcm_products` rp
  LEFT JOIN `products` p
    ON p.`id`=rp.`products_new_product_id`
   AND p.`business_id`=rp.`business_id`
   AND TRIM(p.`name`)=TRIM(rp.`name`)
   AND TRIM(COALESCE(p.`sku`,CHAR(0)))=TRIM(COALESCE(rp.`code`,CHAR(0)))
  SET rp.`products_new_product_id`=NULL
  WHERE rp.`products_new_product_id` IS NOT NULL AND p.`id` IS NULL',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

SET @rcm_sql := IF(@rcm_has_products>0,
 'UPDATE `rcm_products` rp
  INNER JOIN `products` p
    ON p.`business_id`=rp.`business_id`
   AND TRIM(p.`name`)=TRIM(rp.`name`)
   AND TRIM(COALESCE(p.`sku`,CHAR(0)))=TRIM(COALESCE(rp.`code`,CHAR(0)))
  LEFT JOIN `rcm_products` duplicate_link
    ON duplicate_link.`business_id`=rp.`business_id`
   AND duplicate_link.`products_new_product_id`=p.`id`
   AND duplicate_link.`id`<>rp.`id`
  SET rp.`products_new_product_id`=p.`id`
  WHERE rp.`products_new_product_id` IS NULL AND duplicate_link.`id` IS NULL',
 'SELECT 1');
PREPARE rcm_stmt FROM @rcm_sql; EXECUTE rcm_stmt; DEALLOCATE PREPARE rcm_stmt;

-- Verification
SELECT
 (SELECT COUNT(*) FROM `categories`) AS products_new_live_categories,
 (SELECT COUNT(*) FROM `products`) AS products_new_live_products,
 (SELECT COUNT(*) FROM `rcm_paddy_varieties` WHERE `paddy_product_id` IS NOT NULL) AS linked_paddy_varieties,
 (SELECT COUNT(*) FROM `rcm_products` WHERE `products_new_product_id` IS NOT NULL) AS linked_rice_products;


-- IS22305 (22 Sep 2026): optional operational payments for Receive Paddy,
-- Milling / Production and Sales / Dispatch. This CREATE is repeat-safe and
-- does not require INFORMATION_SCHEMA access.
CREATE TABLE IF NOT EXISTS `rcm_operational_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `source_type` VARCHAR(60) NOT NULL,
  `source_id` BIGINT UNSIGNED NOT NULL,
  `payment_context` VARCHAR(30) NOT NULL,
  `payment_method` VARCHAR(60) NOT NULL,
  `payment_method_label` VARCHAR(120) NULL,
  `payment_account_id` BIGINT UNSIGNED NOT NULL,
  `payment_account_name` VARCHAR(191) NULL,
  `amount` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `event_type` VARCHAR(80) NOT NULL,
  `meta` JSON NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `finance_outbox_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `posted_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rcm_operational_payment_source_unique` (`business_id`,`source_type`,`source_id`),
  KEY `rcm_operational_payment_business_idx` (`business_id`),
  KEY `rcm_operational_payment_status_idx` (`status`),
  KEY `rcm_operational_payment_outbox_idx` (`finance_outbox_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
