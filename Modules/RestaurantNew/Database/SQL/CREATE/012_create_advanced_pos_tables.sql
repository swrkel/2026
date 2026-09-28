CREATE TABLE IF NOT EXISTS `rn_table_operations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `order_id` BIGINT UNSIGNED NULL,
  `operation_type` VARCHAR(40) NOT NULL,
  `from_table_id` BIGINT UNSIGNED NULL,
  `to_table_id` BIGINT UNSIGNED NULL,
  `from_waiter_id` BIGINT UNSIGNED NULL,
  `to_waiter_id` BIGINT UNSIGNED NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_table_ops_business_location_idx` (`business_id`, `location_id`),
  KEY `rn_table_ops_order_type_idx` (`order_id`, `operation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_held_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `hold_no` VARCHAR(191) NOT NULL,
  `reason` TEXT NULL,
  `snapshot` JSON NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'held',
  `held_by` BIGINT UNSIGNED NULL,
  `held_at` TIMESTAMP NULL,
  `resumed_by` BIGINT UNSIGNED NULL,
  `resumed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rn_held_orders_hold_no_unique` (`hold_no`),
  KEY `rn_held_orders_status_idx` (`business_id`, `location_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_split_bills` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `split_no` VARCHAR(191) NOT NULL,
  `guest_name` VARCHAR(191) NULL,
  `seat_no` VARCHAR(191) NULL,
  `sub_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `service_charge_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `grand_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `payment_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_split_bills_business_location_idx` (`business_id`, `location_id`),
  KEY `rn_split_bills_order_payment_idx` (`order_id`, `payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_split_bill_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `split_bill_id` BIGINT UNSIGNED NOT NULL,
  `order_line_id` BIGINT UNSIGNED NULL,
  `menu_item_name` VARCHAR(191) NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_split_bill_lines_split_idx` (`split_bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_multi_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `split_bill_id` BIGINT UNSIGNED NULL,
  `payment_method` VARCHAR(30) NOT NULL,
  `account_id` BIGINT UNSIGNED NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reference_no` VARCHAR(191) NULL,
  `card_type` VARCHAR(191) NULL,
  `paid_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_multi_payments_business_location_idx` (`business_id`, `location_id`),
  KEY `rn_multi_payments_order_split_idx` (`order_id`, `split_bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
