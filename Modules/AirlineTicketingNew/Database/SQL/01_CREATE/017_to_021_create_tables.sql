-- ATN-017 TO ATN-021 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_corporate_agreements` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`agreement_no` VARCHAR(40) NOT NULL,`corporate_customer_id` BIGINT UNSIGNED NOT NULL,`effective_from` DATE NOT NULL,`effective_to` DATE NULL,
`credit_limit` DECIMAL(22,4) NOT NULL DEFAULT 0,`credit_days` INT UNSIGNED NOT NULL DEFAULT 0,`currency_code` VARCHAR(3) NOT NULL,
`discount_type` VARCHAR(20) NULL,`discount_value` DECIMAL(22,4) NOT NULL DEFAULT 0,`travel_policy_json` JSON NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'draft',`remarks` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_corporate_agreement_no_uq` (`business_id`,`agreement_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_corporate_ledgers` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`corporate_customer_id` BIGINT UNSIGNED NOT NULL,`transaction_date` DATE NOT NULL,`reference_type` VARCHAR(80) NULL,`reference_id` BIGINT UNSIGNED NULL,
`reference_no` VARCHAR(80) NULL,`description` TEXT NULL,`debit` DECIMAL(22,4) NOT NULL DEFAULT 0,`credit` DECIMAL(22,4) NOT NULL DEFAULT 0,
`balance` DECIMAL(22,4) NOT NULL DEFAULT 0,`currency_code` VARCHAR(3) NOT NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,KEY `atn_corporate_ledger_idx` (`business_id`,`corporate_customer_id`,`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_visa_applications` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`application_no` VARCHAR(40) NOT NULL,`passenger_id` BIGINT UNSIGNED NOT NULL,`country_code` VARCHAR(2) NOT NULL,`visa_type` VARCHAR(80) NOT NULL,
`embassy_name` VARCHAR(150) NULL,`appointment_at` DATETIME NULL,`submission_date` DATE NULL,`expected_completion_date` DATE NULL,
`visa_fee` DECIMAL(22,4) NOT NULL DEFAULT 0,`service_fee` DECIMAL(22,4) NOT NULL DEFAULT 0,`currency_code` VARCHAR(3) NOT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'draft',`remarks` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_visa_application_no_uq` (`business_id`,`application_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_visa_checklist_items` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`visa_application_id` BIGINT UNSIGNED NOT NULL,`item_name` VARCHAR(190) NOT NULL,`is_required` TINYINT(1) NOT NULL DEFAULT 1,
`is_received` TINYINT(1) NOT NULL DEFAULT 0,`received_at` DATETIME NULL,`notes` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,
`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,KEY `atn_visa_checklist_idx` (`business_id`,`visa_application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_tour_packages` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`package_code` VARCHAR(40) NOT NULL,`name` VARCHAR(180) NOT NULL,`package_type` VARCHAR(30) NOT NULL,`destination_country_code` VARCHAR(2) NULL,
`destination_city` VARCHAR(100) NOT NULL,`duration_days` INT UNSIGNED NOT NULL,`duration_nights` INT UNSIGNED NOT NULL,
`currency_code` VARCHAR(3) NOT NULL,`cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`sale_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`max_passengers` INT UNSIGNED NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'draft',`description` TEXT NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_tour_package_code_uq` (`business_id`,`package_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_tour_bookings` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`booking_no` VARCHAR(40) NOT NULL,`tour_package_id` BIGINT UNSIGNED NOT NULL,`departure_date` DATE NOT NULL,`return_date` DATE NOT NULL,
`passenger_count` INT UNSIGNED NOT NULL,`currency_code` VARCHAR(3) NOT NULL,`gross_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`net_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`due_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`status` VARCHAR(30) NOT NULL DEFAULT 'confirmed',`remarks` TEXT NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_tour_booking_no_uq` (`business_id`,`booking_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_hotels` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`hotel_code` VARCHAR(40) NOT NULL,`name` VARCHAR(180) NOT NULL,`country_code` VARCHAR(2) NOT NULL,`city` VARCHAR(100) NOT NULL,
`address` TEXT NULL,`phone` VARCHAR(40) NULL,`email` VARCHAR(150) NULL,`star_rating` TINYINT UNSIGNED NULL,`supplier_id` BIGINT UNSIGNED NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_hotel_code_uq` (`business_id`,`hotel_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_hotel_reservations` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`reservation_no` VARCHAR(40) NOT NULL,`hotel_id` BIGINT UNSIGNED NOT NULL,`passenger_id` BIGINT UNSIGNED NULL,`check_in_date` DATE NOT NULL,
`check_out_date` DATE NOT NULL,`room_type` VARCHAR(80) NOT NULL,`room_count` INT UNSIGNED NOT NULL DEFAULT 1,`guest_count` INT UNSIGNED NOT NULL DEFAULT 1,
`meal_plan` VARCHAR(50) NULL,`currency_code` VARCHAR(3) NOT NULL,`cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`sale_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`due_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`status` VARCHAR(30) NOT NULL DEFAULT 'confirmed',
`voucher_no` VARCHAR(40) NULL,`remarks` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_hotel_reservation_no_uq` (`business_id`,`reservation_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_transport_vehicles` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`vehicle_no` VARCHAR(40) NOT NULL,`vehicle_type` VARCHAR(80) NOT NULL,`make` VARCHAR(80) NULL,`model` VARCHAR(80) NULL,
`seat_capacity` INT UNSIGNED NOT NULL,`supplier_id` BIGINT UNSIGNED NULL,`driver_name` VARCHAR(150) NULL,`driver_phone` VARCHAR(40) NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_transport_vehicle_no_uq` (`business_id`,`vehicle_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_transfer_bookings` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`booking_no` VARCHAR(40) NOT NULL,`passenger_id` BIGINT UNSIGNED NULL,`vehicle_id` BIGINT UNSIGNED NULL,`transfer_type` VARCHAR(30) NOT NULL,
`pickup_location` VARCHAR(190) NOT NULL,`drop_location` VARCHAR(190) NOT NULL,`pickup_at` DATETIME NOT NULL,`passenger_count` INT UNSIGNED NOT NULL DEFAULT 1,
`currency_code` VARCHAR(3) NOT NULL,`cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`sale_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`due_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`status` VARCHAR(30) NOT NULL DEFAULT 'confirmed',
`driver_notes` TEXT NULL,`remarks` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_transfer_booking_no_uq` (`business_id`,`booking_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
