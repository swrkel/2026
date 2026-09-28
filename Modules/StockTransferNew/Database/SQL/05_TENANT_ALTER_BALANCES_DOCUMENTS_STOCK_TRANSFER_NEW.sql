-- StockTransferNew STN_003 tenant database SQL
-- Run in each tenant database after STN_001 and STN_002 SQL.

CREATE TABLE IF NOT EXISTS `stnew_stock_balances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `business_location_id` bigint unsigned NULL,
  `store_id` bigint unsigned NULL,
  `product_id` bigint unsigned NOT NULL,
  `variation_id` bigint unsigned NULL,
  `qty_on_hand` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `qty_in_transit` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `last_unit_cost` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `last_movement_at` timestamp NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stnew_balances_unique` (`business_id`,`business_location_id`,`store_id`,`product_id`,`variation_id`),
  KEY `stnew_balances_business_idx` (`business_id`),
  KEY `stnew_balances_location_idx` (`business_location_id`),
  KEY `stnew_balances_store_idx` (`store_id`),
  KEY `stnew_balances_product_idx` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `stnew_stock_transfers` ADD COLUMN IF NOT EXISTS `dispatch_note_no` varchar(191) NULL AFTER `transfer_no`;
ALTER TABLE `stnew_stock_transfers` ADD COLUMN IF NOT EXISTS `receive_note_no` varchar(191) NULL AFTER `dispatch_note_no`;
ALTER TABLE `stnew_stock_transfers` ADD COLUMN IF NOT EXISTS `vehicle_no` varchar(191) NULL AFTER `remarks`;
ALTER TABLE `stnew_stock_transfers` ADD COLUMN IF NOT EXISTS `driver_name` varchar(191) NULL AFTER `vehicle_no`;
ALTER TABLE `stnew_stock_transfers` ADD COLUMN IF NOT EXISTS `driver_mobile` varchar(191) NULL AFTER `driver_name`;
ALTER TABLE `stnew_stock_transfer_lines` ADD COLUMN IF NOT EXISTS `batch_no` varchar(191) NULL AFTER `variation_id`;
ALTER TABLE `stnew_stock_transfer_lines` ADD COLUMN IF NOT EXISTS `expiry_date` date NULL AFTER `batch_no`;
ALTER TABLE `stnew_stock_transfer_lines` ADD COLUMN IF NOT EXISTS `short_qty` decimal(22,4) NOT NULL DEFAULT 0.0000 AFTER `qty_received`;
ALTER TABLE `stnew_stock_transfer_lines` ADD COLUMN IF NOT EXISTS `excess_qty` decimal(22,4) NOT NULL DEFAULT 0.0000 AFTER `short_qty`;
