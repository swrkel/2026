
-- DISNEW 006 - Collections, Settlements, Portals and Operational Reports
-- Run this in every tenant database. No database name is hard-coded.

CREATE TABLE IF NOT EXISTS `disnew_collections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `sales_rep_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `sales_order_id` bigint unsigned DEFAULT NULL,
  `sales_invoice_id` bigint unsigned DEFAULT NULL,
  `collection_no` varchar(50) NOT NULL,
  `collection_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'cash',
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `note` text DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_collections_no_unique` (`business_id`,`collection_no`),
  KEY `disnew_collections_business_location_idx` (`business_id`,`business_location_id`),
  KEY `disnew_collections_sales_rep_idx` (`sales_rep_id`),
  KEY `disnew_collections_invoice_idx` (`sales_invoice_id`),
  KEY `disnew_collections_customer_idx` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_settlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `sales_rep_id` bigint unsigned DEFAULT NULL,
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `settlement_no` varchar(50) NOT NULL,
  `settlement_date` date NOT NULL,
  `opening_stock_value` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `loaded_value` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `sold_value` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `returned_value` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `collection_total` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `shortage_value` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `excess_value` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `finalized_at` timestamp NULL DEFAULT NULL,
  `finalized_by` bigint unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_settlements_no_unique` (`business_id`,`settlement_no`),
  KEY `disnew_settlements_business_location_idx` (`business_id`,`business_location_id`),
  KEY `disnew_settlements_sales_rep_vehicle_idx` (`sales_rep_id`,`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_settlement_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `settlement_id` bigint unsigned NOT NULL,
  `line_type` varchar(40) NOT NULL,
  `reference_type` varchar(80) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `qty` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_settlement_lines_settlement_idx` (`settlement_id`),
  KEY `disnew_settlement_lines_ref_idx` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_order_status_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `sales_order_id` bigint unsigned NOT NULL,
  `from_status` varchar(40) DEFAULT NULL,
  `to_status` varchar(40) NOT NULL,
  `changed_by_type` varchar(40) DEFAULT NULL,
  `changed_by` bigint unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_order_status_logs_order_idx` (`sales_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_portal_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `portal_type` varchar(40) NOT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `reference_type` varchar(80) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_portal_audit_logs_business_idx` (`business_id`),
  KEY `disnew_portal_audit_logs_ref_idx` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
