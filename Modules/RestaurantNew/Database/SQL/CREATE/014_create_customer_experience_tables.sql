CREATE TABLE IF NOT EXISTS `restaurant_new_qr_menus` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(191) NOT NULL,
  `public_token` VARCHAR(80) NOT NULL,
  `allow_self_order` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `settings` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rn_qr_public_token_unique` (`public_token`),
  KEY `rn_qr_business_location_index` (`business_id`, `location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_customer_order_links` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `restaurant_order_id` BIGINT UNSIGNED NOT NULL,
  `purpose` VARCHAR(40) NOT NULL DEFAULT 'status',
  `public_token` VARCHAR(100) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `expires_at` TIMESTAMP NULL,
  `last_accessed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rn_customer_link_token_unique` (`public_token`),
  KEY `rn_customer_link_order_index` (`business_id`, `restaurant_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_digital_receipts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `restaurant_order_id` BIGINT UNSIGNED NOT NULL,
  `restaurant_bill_id` BIGINT UNSIGNED NULL,
  `receipt_token` VARCHAR(100) NOT NULL,
  `sent_to` VARCHAR(191) NULL,
  `delivery_channel` VARCHAR(40) NULL,
  `delivery_status` VARCHAR(40) NOT NULL DEFAULT 'pending',
  `sent_at` TIMESTAMP NULL,
  `payload` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rn_receipt_token_unique` (`receipt_token`),
  KEY `rn_receipt_order_index` (`business_id`, `restaurant_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_customer_feedback` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `restaurant_order_id` BIGINT UNSIGNED NULL,
  `customer_id` BIGINT UNSIGNED NULL,
  `customer_name` VARCHAR(191) NULL,
  `customer_mobile` VARCHAR(50) NULL,
  `rating_food` TINYINT UNSIGNED NULL,
  `rating_service` TINYINT UNSIGNED NULL,
  `rating_overall` TINYINT UNSIGNED NULL,
  `comments` TEXT NULL,
  `metadata` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_feedback_business_location_index` (`business_id`, `location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
