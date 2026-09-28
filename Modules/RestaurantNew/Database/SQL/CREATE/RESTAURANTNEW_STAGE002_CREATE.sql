CREATE TABLE IF NOT EXISTS `rn_kitchen_sections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `print_kot` TINYINT(1) NOT NULL DEFAULT 1,
  `show_on_kds` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_kitchen_sections_business_id_index` (`business_id`),
  KEY `rn_kitchen_sections_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_order_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `requires_table` TINYINT(1) NOT NULL DEFAULT 0,
  `requires_customer` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_delivery` TINYINT(1) NOT NULL DEFAULT 0,
  `default_service_charge_percent` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `default_delivery_charge` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_order_types_business_id_index` (`business_id`),
  KEY `rn_order_types_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_numbering_sequences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `document_type` VARCHAR(191) NOT NULL,
  `prefix` VARCHAR(191) NULL,
  `next_number` INT UNSIGNED NOT NULL DEFAULT 1,
  `padding` INT UNSIGNED NOT NULL DEFAULT 5,
  `suffix` VARCHAR(191) NULL,
  `reset_yearly` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_numbering_sequences_business_id_index` (`business_id`),
  KEY `rn_numbering_sequences_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
