-- CUS_032 Distribution Dealer GPS Delivery Tracking + Live Delivery Status
-- Safe optional tables. Existing Distribution/Petro/ERP delivery tables are not modified.

CREATE TABLE IF NOT EXISTS `customer_portal_delivery_tracking` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `contact_id` INT UNSIGNED NOT NULL,
  `delivery_id` BIGINT UNSIGNED NULL,
  `delivery_no` VARCHAR(191) NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `event_datetime` DATETIME NULL,
  `remarks` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cus32_delivery_tracking_business_contact` (`business_id`, `contact_id`),
  KEY `cus32_delivery_tracking_delivery` (`delivery_id`),
  KEY `cus32_delivery_tracking_delivery_no` (`delivery_no`),
  KEY `cus32_delivery_tracking_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_portal_vehicle_locations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `contact_id` INT UNSIGNED NULL,
  `delivery_id` BIGINT UNSIGNED NULL,
  `delivery_no` VARCHAR(191) NULL,
  `vehicle_no` VARCHAR(191) NULL,
  `driver_name` VARCHAR(191) NULL,
  `latitude` DECIMAL(11,8) NULL,
  `longitude` DECIMAL(11,8) NULL,
  `status` VARCHAR(50) NULL,
  `remarks` TEXT NULL,
  `last_updated_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cus32_vehicle_locations_business_contact` (`business_id`, `contact_id`),
  KEY `cus32_vehicle_locations_delivery` (`delivery_id`),
  KEY `cus32_vehicle_locations_vehicle` (`vehicle_no`),
  KEY `cus32_vehicle_locations_last_updated` (`last_updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
