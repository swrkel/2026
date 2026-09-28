-- HOTELMGT_028_SQL.sql
-- Hotel Management Parcel 028: Valet Parking
-- Run on each tenant database that uses the Hotel Management module.

CREATE TABLE IF NOT EXISTS `hm_valet_parking_zones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `zone_code` VARCHAR(50) NOT NULL,
  `zone_name` VARCHAR(120) NOT NULL,
  `capacity` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_valet_zones_business_code_unique` (`business_id`, `zone_code`),
  KEY `hm_valet_zones_business_id_index` (`business_id`),
  KEY `hm_valet_zones_location_id_index` (`business_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_valet_parking_tickets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `ticket_no` VARCHAR(80) NOT NULL,
  `ticket_date` DATE NULL,
  `zone_id` BIGINT UNSIGNED NULL,
  `room_no` VARCHAR(30) NULL,
  `guest_name` VARCHAR(150) NULL,
  `mobile` VARCHAR(50) NULL,
  `vehicle_no` VARCHAR(80) NOT NULL,
  `vehicle_type` VARCHAR(80) NULL,
  `vehicle_colour` VARCHAR(80) NULL,
  `key_tag_no` VARCHAR(80) NULL,
  `parked_slot` VARCHAR(80) NULL,
  `check_in_time` VARCHAR(30) NULL,
  `expected_out_time` VARCHAR(30) NULL,
  `retrieved_time` VARCHAR(30) NULL,
  `driver_name` VARCHAR(150) NULL,
  `rate` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `net_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'parked',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_valet_tickets_business_no_unique` (`business_id`, `ticket_no`),
  KEY `hm_valet_tickets_business_id_index` (`business_id`),
  KEY `hm_valet_tickets_location_id_index` (`business_location_id`),
  KEY `hm_valet_tickets_ticket_date_index` (`ticket_date`),
  KEY `hm_valet_tickets_zone_id_index` (`zone_id`),
  KEY `hm_valet_tickets_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_valet_parking_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `ticket_id` BIGINT UNSIGNED NOT NULL,
  `payment_date` DATE NULL,
  `payment_method` VARCHAR(40) NOT NULL,
  `paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `payment_reference` VARCHAR(150) NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_valet_payments_business_id_index` (`business_id`),
  KEY `hm_valet_payments_location_id_index` (`business_location_id`),
  KEY `hm_valet_payments_ticket_id_index` (`ticket_id`),
  KEY `hm_valet_payments_payment_date_index` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
