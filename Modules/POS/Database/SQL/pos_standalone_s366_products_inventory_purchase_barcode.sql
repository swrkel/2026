-- POS Standalone S366 - Products, Inventory Purchase Stock, Setup and Barcode Labels
-- Global SQL: run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_purchase_headers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(100) DEFAULT NULL,
  `supplier_name` varchar(191) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'posted',
  `total_amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `note` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_purchase_headers_reference_no_index` (`reference_no`),
  KEY `pos_purchase_headers_purchase_date_index` (`purchase_date`),
  KEY `pos_purchase_headers_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_purchase_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `line_total` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_purchase_lines_purchase_id_index` (`purchase_id`),
  KEY `pos_purchase_lines_product_id_index` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pos_stock_movements`
  ADD INDEX `pos_stock_movements_type_reference_index` (`movement_type`, `reference_id`);
