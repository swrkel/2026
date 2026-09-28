-- Petro Direct-New tenant installer
-- Run in each tenant database. Safe to rerun.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `pdirectnew_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` longtext NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdn_settings_scope_key_uq` (`business_id`,`location_id`,`setting_key`),
  KEY `pdn_settings_business_idx` (`business_id`),
  KEY `pdn_settings_location_idx` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_number_sequences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL DEFAULT 0,
  `sequence_type` varchar(80) NOT NULL,
  `prefix` varchar(40) DEFAULT NULL,
  `next_number` bigint unsigned NOT NULL DEFAULT 1,
  `padding` tinyint unsigned NOT NULL DEFAULT 5,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdn_sequence_scope_uq` (`business_id`,`location_id`,`sequence_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_operators` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `source_operator_id` bigint unsigned DEFAULT NULL,
  `operator_no` varchar(80) NOT NULL,
  `name` varchar(190) NOT NULL,
  `address` text NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `landline` varchar(50) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `nic` varchar(80) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `passcode_hash` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(22,4) NOT NULL DEFAULT 0,
  `commission_type` varchar(30) NOT NULL DEFAULT 'none',
  `commission_value` decimal(22,4) NOT NULL DEFAULT 0,
  `short_amount` decimal(22,4) NOT NULL DEFAULT 0,
  `excess_amount` decimal(22,4) NOT NULL DEFAULT 0,
  `transaction_date` date DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `can_fullscreen` tinyint(1) NOT NULL DEFAULT 0,
  `hide_in_direct_settlement_if_pending_shifts` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `can_login` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `source_updated_at` datetime DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdn_operator_no_uq` (`business_id`,`operator_no`),
  UNIQUE KEY `pdn_operator_source_uq` (`business_id`,`source_operator_id`),
  KEY `pdn_operator_location_idx` (`business_id`,`location_id`),
  KEY `pdn_operator_active_idx` (`business_id`,`is_active`),
  KEY `pdn_operator_list_idx` (`business_id`,`location_id`,`is_active`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_tanks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `tank_no` varchar(80) NOT NULL,
  `name` varchar(190) NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `capacity` decimal(22,3) NOT NULL DEFAULT 0,
  `current_stock` decimal(22,3) NOT NULL DEFAULT 0,
  `reorder_level` decimal(22,3) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdn_tank_no_uq` (`business_id`,`location_id`,`tank_no`),
  KEY `pdn_tank_product_idx` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_pumps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `pump_no` varchar(80) NOT NULL,
  `name` varchar(190) NOT NULL,
  `tank_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `meter_type` varchar(30) NOT NULL DEFAULT 'digital',
  `opening_meter` decimal(22,3) NOT NULL DEFAULT 0,
  `current_meter` decimal(22,3) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdn_pump_no_uq` (`business_id`,`location_id`,`pump_no`),
  KEY `pdn_pump_tank_idx` (`tank_id`),
  KEY `pdn_pump_product_idx` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_shifts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `shift_no` varchar(80) NOT NULL,
  `operator_id` bigint unsigned NOT NULL,
  `opened_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'open',
  `note` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `closed_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdn_shift_no_uq` (`business_id`,`shift_no`),
  KEY `pdn_shift_scope_idx` (`business_id`,`location_id`,`status`),
  KEY `pdn_shift_operator_idx` (`operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned NOT NULL,
  `operator_id` bigint unsigned NOT NULL,
  `pump_id` bigint unsigned NOT NULL,
  `opening_meter` decimal(22,3) NOT NULL DEFAULT 0,
  `closing_meter` decimal(22,3) DEFAULT NULL,
  `testing_qty` decimal(22,3) NOT NULL DEFAULT 0,
  `sold_qty` decimal(22,3) NOT NULL DEFAULT 0,
  `unit_price` decimal(22,4) NOT NULL DEFAULT 0,
  `sales_amount` decimal(22,4) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'assigned',
  `received_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdn_assignment_shift_pump_uq` (`business_id`,`shift_id`,`pump_id`),
  KEY `pdn_assignment_scope_idx` (`business_id`,`location_id`,`status`),
  KEY `pdn_assignment_operator_idx` (`operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_meter_readings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `assignment_id` bigint unsigned DEFAULT NULL,
  `pump_id` bigint unsigned NOT NULL,
  `operator_id` bigint unsigned DEFAULT NULL,
  `reading_type` varchar(30) NOT NULL DEFAULT 'current',
  `reading` decimal(22,3) NOT NULL,
  `testing_qty` decimal(22,3) NOT NULL DEFAULT 0,
  `recorded_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_meter_scope_idx` (`business_id`,`location_id`,`recorded_at`),
  KEY `pdn_meter_pump_idx` (`pump_id`),
  KEY `pdn_meter_assignment_idx` (`assignment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_meter_resets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `pump_id` bigint unsigned NOT NULL,
  `old_meter` decimal(22,3) NOT NULL,
  `new_meter` decimal(22,3) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `reset_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_meter_reset_scope_idx` (`business_id`,`location_id`,`pump_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_settlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `settlement_no` varchar(80) NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `operator_id` bigint unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `work_shift` varchar(80) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `note` text NULL,
  `expected_total` decimal(22,4) NOT NULL DEFAULT 0,
  `received_total` decimal(22,4) NOT NULL DEFAULT 0,
  `variance` decimal(22,4) NOT NULL DEFAULT 0,
  `finalized_at` datetime DEFAULT NULL,
  `finalized_by` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdn_settlement_no_uq` (`business_id`,`settlement_no`),
  KEY `pdn_settlement_scope_idx` (`business_id`,`location_id`,`transaction_date`,`status`),
  KEY `pdn_settlement_operator_idx` (`operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_settlement_meter_sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `settlement_id` bigint unsigned NOT NULL,
  `assignment_id` bigint unsigned DEFAULT NULL,
  `pump_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `opening_meter` decimal(22,3) NOT NULL DEFAULT 0,
  `closing_meter` decimal(22,3) NOT NULL DEFAULT 0,
  `testing_qty` decimal(22,3) NOT NULL DEFAULT 0,
  `sold_qty` decimal(22,3) NOT NULL DEFAULT 0,
  `unit_price` decimal(22,4) NOT NULL DEFAULT 0,
  `discount_type` varchar(30) NOT NULL DEFAULT 'fixed',
  `discount_value` decimal(22,4) NOT NULL DEFAULT 0,
  `amount` decimal(22,4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_meter_sale_settlement_idx` (`settlement_id`),
  KEY `pdn_meter_sale_pump_idx` (`pump_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_settlement_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `settlement_id` bigint unsigned NOT NULL,
  `payment_type` varchar(50) NOT NULL,
  `reference_no` varchar(190) DEFAULT NULL,
  `contact_id` bigint unsigned DEFAULT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(22,4) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `details` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_payment_settlement_idx` (`settlement_id`,`payment_type`,`status`),
  KEY `pdn_payment_reference_idx` (`reference_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_settlement_other_sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `settlement_id` bigint unsigned NOT NULL,
  `reference_no` varchar(190) DEFAULT NULL,
  `contact_id` bigint unsigned DEFAULT NULL,
  `total` decimal(22,4) NOT NULL DEFAULT 0,
  `notes` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_other_sale_settlement_idx` (`settlement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_settlement_other_sale_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `other_sale_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `variation_id` bigint unsigned DEFAULT NULL,
  `qty` decimal(22,4) NOT NULL DEFAULT 0,
  `unit_price` decimal(22,4) NOT NULL DEFAULT 0,
  `tax_amount` decimal(22,4) NOT NULL DEFAULT 0,
  `line_total` decimal(22,4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_other_sale_line_parent_idx` (`other_sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_settlement_other_income` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `settlement_id` bigint unsigned NOT NULL,
  `description` varchar(500) NOT NULL,
  `amount` decimal(22,4) NOT NULL DEFAULT 0,
  `account_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_other_income_settlement_idx` (`settlement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_settlement_customer_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `settlement_id` bigint unsigned NOT NULL,
  `contact_id` bigint unsigned NOT NULL,
  `reference_no` varchar(190) DEFAULT NULL,
  `amount` decimal(22,4) NOT NULL DEFAULT 0,
  `payment_method` varchar(50) NOT NULL DEFAULT 'cash',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_customer_payment_settlement_idx` (`settlement_id`),
  KEY `pdn_customer_payment_contact_idx` (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_daily_collections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `collection_no` varchar(80) NOT NULL,
  `collection_date` date NOT NULL,
  `operator_id` bigint unsigned DEFAULT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `cash_total` decimal(22,4) NOT NULL DEFAULT 0,
  `card_total` decimal(22,4) NOT NULL DEFAULT 0,
  `cheque_total` decimal(22,4) NOT NULL DEFAULT 0,
  `credit_total` decimal(22,4) NOT NULL DEFAULT 0,
  `other_total` decimal(22,4) NOT NULL DEFAULT 0,
  `shortage` decimal(22,4) NOT NULL DEFAULT 0,
  `excess` decimal(22,4) NOT NULL DEFAULT 0,
  `grand_total` decimal(22,4) NOT NULL DEFAULT 0,
  `note` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `finalized_by` bigint unsigned DEFAULT NULL,
  `finalized_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdn_collection_no_uq` (`business_id`,`collection_no`),
  KEY `pdn_collection_scope_idx` (`business_id`,`location_id`,`collection_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_collection_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `daily_collection_id` bigint unsigned NOT NULL,
  `line_type` varchar(50) NOT NULL,
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(22,4) NOT NULL DEFAULT 0,
  `details` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_collection_line_parent_idx` (`daily_collection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `pdirectnew_tank_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `transfer_no` varchar(80) NOT NULL,
  `from_tank_id` bigint unsigned NOT NULL,
  `to_tank_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(22,3) NOT NULL,
  `transfer_date` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'completed',
  `note` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdn_transfer_no_uq` (`business_id`,`transfer_no`),
  KEY `pdn_transfer_scope_idx` (`business_id`,`location_id`,`transfer_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_dip_charts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `tank_id` bigint unsigned NOT NULL,
  `name` varchar(190) NOT NULL,
  `unit` varchar(30) NOT NULL DEFAULT 'litre',
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_dip_chart_scope_idx` (`business_id`,`location_id`,`tank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_dip_chart_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dip_chart_id` bigint unsigned NOT NULL,
  `dip_value` decimal(22,3) NOT NULL,
  `volume` decimal(22,3) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdn_dip_chart_line_uq` (`dip_chart_id`,`dip_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_dip_readings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `tank_id` bigint unsigned NOT NULL,
  `dip_chart_id` bigint unsigned DEFAULT NULL,
  `reading_date` date NOT NULL,
  `dip_value` decimal(22,3) NOT NULL,
  `calculated_stock` decimal(22,3) NOT NULL DEFAULT 0,
  `actual_stock` decimal(22,3) NOT NULL DEFAULT 0,
  `variance` decimal(22,3) NOT NULL DEFAULT 0,
  `note` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_dip_reading_scope_idx` (`business_id`,`location_id`,`reading_date`),
  KEY `pdn_dip_reading_tank_idx` (`tank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `settlement_id` bigint unsigned DEFAULT NULL,
  `operator_id` bigint unsigned DEFAULT NULL,
  `adjustment_type` varchar(50) NOT NULL,
  `amount` decimal(22,4) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `requested_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_adjustment_scope_idx` (`business_id`,`location_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_print_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `document_type` varchar(100) NOT NULL,
  `document_id` bigint unsigned DEFAULT NULL,
  `printed_by` bigint unsigned DEFAULT NULL,
  `printed_at` datetime DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_print_scope_idx` (`business_id`,`document_type`,`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(120) NOT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `before_data` longtext NULL,
  `after_data` longtext NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_audit_scope_idx` (`business_id`,`entity_type`,`entity_id`),
  KEY `pdn_audit_user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdirectnew_saved_report_filters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `report_type` varchar(80) NOT NULL,
  `name` varchar(190) NOT NULL,
  `filters` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdn_report_filter_scope_idx` (`business_id`,`user_id`,`report_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;

-- Petro Direct-New permissions. Safe to rerun.
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.access','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.access' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.dashboard.view','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.dashboard.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.settlements.view','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.settlements.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.settlements.create','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.settlements.create' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.settlements.edit','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.settlements.edit' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.settlements.delete','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.settlements.delete' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.settlements.finalize','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.settlements.finalize' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.settlements.print','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.settlements.print' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.pumpers.view','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.pumpers.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.operators.manage','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.operators.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.pumps.manage','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.pumps.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.tanks.manage','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.tanks.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.assignments.manage','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.assignments.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.meters.manage','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.meters.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.dips.manage','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.dips.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.transfers.manage','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.transfers.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.collections.manage','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.collections.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.payments.manage','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.payments.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.day_entries.manage','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.day_entries.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.shifts.close','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.shifts.close' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.unload_stock.manage','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.unload_stock.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.reports.view','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.reports.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.reports.export','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.reports.export' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT 'petro_direct_new.settings.manage','web',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_direct_new.settings.manage' AND `guard_name`='web');


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
