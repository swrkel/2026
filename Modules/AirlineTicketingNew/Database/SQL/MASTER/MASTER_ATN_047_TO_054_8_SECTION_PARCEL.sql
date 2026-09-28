-- ATN-047 TO ATN-054 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_visa_status_history` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`business_location_id` BIGINT UNSIGNED NULL,
`store_id` BIGINT UNSIGNED NULL,
`visa_application_id` BIGINT UNSIGNED NOT NULL,
`from_status` VARCHAR(40) NULL,
`to_status` VARCHAR(40) NOT NULL,
`note` TEXT NULL,
`changed_by` BIGINT UNSIGNED NULL,
`changed_at` DATETIME NOT NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_visa_status_history_idx` (`business_id`,`visa_application_id`,`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_hotel_room_types` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`hotel_id` BIGINT UNSIGNED NOT NULL,
`room_code` VARCHAR(40) NOT NULL,
`name` VARCHAR(150) NOT NULL,
`max_guests` INT UNSIGNED NOT NULL DEFAULT 1,
`base_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
`base_sale` DECIMAL(22,4) NOT NULL DEFAULT 0,
`currency_code` VARCHAR(3) NOT NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_hotel_room_type_uq` (`business_id`,`hotel_id`,`room_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_hotel_availability` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`hotel_id` BIGINT UNSIGNED NOT NULL,
`room_type_id` BIGINT UNSIGNED NOT NULL,
`availability_date` DATE NOT NULL,
`available_rooms` INT NOT NULL DEFAULT 0,
`cost_rate` DECIMAL(22,4) NOT NULL DEFAULT 0,
`sale_rate` DECIMAL(22,4) NOT NULL DEFAULT 0,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_hotel_availability_uq` (`business_id`,`hotel_id`,`room_type_id`,`availability_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_insurance_providers` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`provider_code` VARCHAR(40) NOT NULL,
`name` VARCHAR(180) NOT NULL,
`phone` VARCHAR(40) NULL,
`email` VARCHAR(190) NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_insurance_provider_uq` (`business_id`,`provider_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_travel_insurance_policies` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`business_location_id` BIGINT UNSIGNED NULL,
`store_id` BIGINT UNSIGNED NULL,
`policy_no` VARCHAR(40) NOT NULL,
`provider_id` BIGINT UNSIGNED NOT NULL,
`passenger_id` BIGINT UNSIGNED NOT NULL,
`start_date` DATE NOT NULL,
`end_date` DATE NOT NULL,
`currency_code` VARCHAR(3) NOT NULL,
`premium_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`coverage_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`status` VARCHAR(30) NOT NULL DEFAULT 'issued',
`remarks` TEXT NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_insurance_policy_uq` (`business_id`,`policy_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_transfer_routes` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`business_location_id` BIGINT UNSIGNED NULL,
`store_id` BIGINT UNSIGNED NULL,
`route_code` VARCHAR(40) NOT NULL,
`name` VARCHAR(180) NOT NULL,
`pickup_location` VARCHAR(190) NOT NULL,
`drop_location` VARCHAR(190) NOT NULL,
`distance_km` DECIMAL(12,3) NOT NULL DEFAULT 0,
`currency_code` VARCHAR(3) NOT NULL,
`base_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
`base_sale` DECIMAL(22,4) NOT NULL DEFAULT 0,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_transfer_route_uq` (`business_id`,`route_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_tour_departures` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`tour_package_id` BIGINT UNSIGNED NOT NULL,
`departure_code` VARCHAR(40) NOT NULL,
`departure_date` DATE NOT NULL,
`return_date` DATE NOT NULL,
`capacity` INT UNSIGNED NOT NULL,
`booked_count` INT UNSIGNED NOT NULL DEFAULT 0,
`currency_code` VARCHAR(3) NOT NULL,
`cost_per_person` DECIMAL(22,4) NOT NULL DEFAULT 0,
`sale_per_person` DECIMAL(22,4) NOT NULL DEFAULT 0,
`is_open` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_tour_departure_uq` (`business_id`,`departure_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_ancillary_services` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`business_location_id` BIGINT UNSIGNED NULL,
`store_id` BIGINT UNSIGNED NULL,
`service_code` VARCHAR(40) NOT NULL,
`name` VARCHAR(180) NOT NULL,
`service_type` VARCHAR(80) NOT NULL,
`currency_code` VARCHAR(3) NOT NULL,
`cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`sale_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_ancillary_service_uq` (`business_id`,`service_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_ancillary_bookings` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`business_location_id` BIGINT UNSIGNED NULL,
`store_id` BIGINT UNSIGNED NULL,
`booking_no` VARCHAR(40) NOT NULL,
`ancillary_service_id` BIGINT UNSIGNED NOT NULL,
`passenger_id` BIGINT UNSIGNED NULL,
`reservation_id` BIGINT UNSIGNED NULL,
`service_date` DATE NULL,
`quantity` DECIMAL(12,3) NOT NULL DEFAULT 1,
`unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
`total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`status` VARCHAR(30) NOT NULL DEFAULT 'confirmed',
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_ancillary_booking_uq` (`business_id`,`booking_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_supplier_service_agreements` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`business_location_id` BIGINT UNSIGNED NULL,
`store_id` BIGINT UNSIGNED NULL,
`supplier_id` BIGINT UNSIGNED NOT NULL,
`service_type` VARCHAR(80) NOT NULL,
`effective_from` DATE NOT NULL,
`effective_to` DATE NULL,
`commission_rate` DECIMAL(12,4) NOT NULL DEFAULT 0,
`priority` INT NOT NULL DEFAULT 100,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`terms` TEXT NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_supplier_service_agreement_idx` (`business_id`,`service_type`,`is_active`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_service_bundles` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`business_location_id` BIGINT UNSIGNED NULL,
`store_id` BIGINT UNSIGNED NULL,
`bundle_code` VARCHAR(40) NOT NULL,
`name` VARCHAR(180) NOT NULL,
`currency_code` VARCHAR(3) NOT NULL,
`bundle_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`description` TEXT NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_service_bundle_uq` (`business_id`,`bundle_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_service_bundle_items` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`service_bundle_id` BIGINT UNSIGNED NOT NULL,
`item_type` VARCHAR(80) NOT NULL,
`item_id` BIGINT UNSIGNED NOT NULL,
`quantity` DECIMAL(12,3) NOT NULL DEFAULT 1,
`unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_service_bundle_item_idx` (`business_id`,`service_bundle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.visa_applications.status' AS name
UNION ALL SELECT 'airline_ticketing_new.hotel_availability.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.insurance.view' AS name
UNION ALL SELECT 'airline_ticketing_new.insurance.issue' AS name
UNION ALL SELECT 'airline_ticketing_new.transfer_routes.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.tour_departures.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.ancillary.view' AS name
UNION ALL SELECT 'airline_ticketing_new.ancillary.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.supplier_services.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.bundles.view' AS name
UNION ALL SELECT 'airline_ticketing_new.bundles.manage' AS name) p
WHERE NOT EXISTS (
    SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);
