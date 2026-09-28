-- ATN-004 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_quotations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `quotation_no` VARCHAR(40) NOT NULL,
  `quotation_date` DATE NOT NULL,
  `valid_until` DATE NULL,
  `customer_type` VARCHAR(30) NOT NULL,
  `corporate_customer_id` BIGINT UNSIGNED NULL,
  `passenger_id` BIGINT UNSIGNED NULL,
  `agent_id` BIGINT UNSIGNED NULL,
  `currency_code` VARCHAR(3) NOT NULL,
  `exchange_rate` DECIMAL(20,8) NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `service_fee_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `grand_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_quotation_no_unique` (`business_id`,`quotation_no`),
  KEY `atn_quotation_date_idx` (`business_id`,`quotation_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_quotation_segments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `quotation_id` BIGINT UNSIGNED NOT NULL,
  `segment_no` INT UNSIGNED NOT NULL,
  `trip_type` VARCHAR(30) NULL,
  `airline_id` BIGINT UNSIGNED NOT NULL,
  `flight_number` VARCHAR(20) NULL,
  `origin_airport_id` BIGINT UNSIGNED NOT NULL,
  `destination_airport_id` BIGINT UNSIGNED NOT NULL,
  `departure_at` DATETIME NOT NULL,
  `arrival_at` DATETIME NOT NULL,
  `travel_class_id` BIGINT UNSIGNED NULL,
  `booking_class` VARCHAR(10) NULL,
  `fare_basis` VARCHAR(30) NULL,
  `baggage_allowance` VARCHAR(50) NULL,
  `base_fare` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `service_fee` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `segment_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_quote_segment_unique` (`quotation_id`,`segment_no`),
  KEY `atn_quote_segment_route_idx` (`business_id`,`origin_airport_id`,`destination_airport_id`,`departure_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_reservations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `reservation_no` VARCHAR(40) NOT NULL,
  `pnr_code` VARCHAR(20) NULL,
  `reservation_date` DATE NOT NULL,
  `ticketing_deadline` DATETIME NULL,
  `quotation_id` BIGINT UNSIGNED NULL,
  `customer_type` VARCHAR(30) NOT NULL,
  `corporate_customer_id` BIGINT UNSIGNED NULL,
  `passenger_id` BIGINT UNSIGNED NULL,
  `agent_id` BIGINT UNSIGNED NULL,
  `supplier_id` BIGINT UNSIGNED NULL,
  `currency_code` VARCHAR(3) NOT NULL,
  `exchange_rate` DECIMAL(20,8) NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `service_fee_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `grand_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `paid_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `due_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'reserved',
  `source` VARCHAR(30) NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_reservation_no_unique` (`business_id`,`reservation_no`),
  KEY `atn_reservation_pnr_idx` (`business_id`,`pnr_code`),
  KEY `atn_reservation_deadline_idx` (`business_id`,`ticketing_deadline`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_reservation_segments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `reservation_id` BIGINT UNSIGNED NOT NULL,
  `segment_no` INT UNSIGNED NOT NULL,
  `airline_id` BIGINT UNSIGNED NOT NULL,
  `flight_number` VARCHAR(20) NULL,
  `origin_airport_id` BIGINT UNSIGNED NOT NULL,
  `destination_airport_id` BIGINT UNSIGNED NOT NULL,
  `departure_at` DATETIME NOT NULL,
  `arrival_at` DATETIME NOT NULL,
  `travel_class_id` BIGINT UNSIGNED NULL,
  `booking_class` VARCHAR(10) NULL,
  `fare_basis` VARCHAR(30) NULL,
  `baggage_allowance` VARCHAR(50) NULL,
  `supplier_id` BIGINT UNSIGNED NULL,
  `base_fare` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `service_fee` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `segment_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `segment_status` VARCHAR(30) NOT NULL DEFAULT 'reserved',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_reservation_segment_unique` (`reservation_id`,`segment_no`),
  KEY `atn_reservation_segment_route_idx` (`business_id`,`origin_airport_id`,`destination_airport_id`,`departure_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_reservation_passengers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `reservation_id` BIGINT UNSIGNED NOT NULL,
  `passenger_id` BIGINT UNSIGNED NOT NULL,
  `passenger_type` VARCHAR(20) NOT NULL DEFAULT 'adult',
  `ticket_name` VARCHAR(250) NULL,
  `document_id` BIGINT UNSIGNED NULL,
  `seat_preference` VARCHAR(40) NULL,
  `meal_preference` VARCHAR(80) NULL,
  `special_service_request` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_reservation_passenger_unique` (`reservation_id`,`passenger_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_reservation_status_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `reservation_id` BIGINT UNSIGNED NOT NULL,
  `from_status` VARCHAR(30) NULL,
  `to_status` VARCHAR(30) NOT NULL,
  `reason` TEXT NULL,
  `changed_by` BIGINT UNSIGNED NULL,
  `changed_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `atn_reservation_status_history_idx` (`business_id`,`reservation_id`,`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ATN-004 permissions
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name,'web',NOW(),NOW()
FROM (
    SELECT 'airline_ticketing_new.quotations.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.quotations.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.quotations.edit' AS name
    UNION ALL SELECT 'airline_ticketing_new.quotations.cancel' AS name
    UNION ALL SELECT 'airline_ticketing_new.reservations.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.reservations.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.reservations.edit' AS name
    UNION ALL SELECT 'airline_ticketing_new.reservations.status' AS name
    UNION ALL SELECT 'airline_ticketing_new.reservations.cancel' AS name
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` existing
    WHERE existing.name=p.name AND existing.guard_name='web'
);
