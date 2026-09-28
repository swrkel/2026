-- ATN-005 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_tickets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `ticket_no` VARCHAR(30) NOT NULL,
  `reservation_id` BIGINT UNSIGNED NOT NULL,
  `reservation_passenger_id` BIGINT UNSIGNED NULL,
  `passenger_id` BIGINT UNSIGNED NULL,
  `airline_id` BIGINT UNSIGNED NULL,
  `supplier_id` BIGINT UNSIGNED NULL,
  `issue_date` DATE NOT NULL,
  `ticketing_agent_id` BIGINT UNSIGNED NULL,
  `currency_code` VARCHAR(3) NOT NULL,
  `exchange_rate` DECIMAL(20,8) NOT NULL DEFAULT 1,
  `base_fare` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `service_fee_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `grand_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'issued',
  `ticket_type` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `original_ticket_id` BIGINT UNSIGNED NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_ticket_no_unique` (`business_id`,`ticket_no`),
  KEY `atn_ticket_reservation_idx` (`business_id`,`reservation_id`),
  KEY `atn_ticket_issue_date_idx` (`business_id`,`issue_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_ticket_segments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `ticket_id` BIGINT UNSIGNED NOT NULL,
  `reservation_segment_id` BIGINT UNSIGNED NULL,
  `segment_no` INT UNSIGNED NOT NULL,
  `airline_id` BIGINT UNSIGNED NULL,
  `flight_number` VARCHAR(20) NULL,
  `origin_airport_id` BIGINT UNSIGNED NOT NULL,
  `destination_airport_id` BIGINT UNSIGNED NOT NULL,
  `departure_at` DATETIME NOT NULL,
  `arrival_at` DATETIME NOT NULL,
  `travel_class_id` BIGINT UNSIGNED NULL,
  `booking_class` VARCHAR(10) NULL,
  `fare_basis` VARCHAR(30) NULL,
  `baggage_allowance` VARCHAR(50) NULL,
  `coupon_status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_ticket_segment_unique` (`ticket_id`,`segment_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `invoice_no` VARCHAR(40) NOT NULL,
  `invoice_date` DATE NOT NULL,
  `reservation_id` BIGINT UNSIGNED NULL,
  `ticket_id` BIGINT UNSIGNED NULL,
  `customer_type` VARCHAR(30) NOT NULL,
  `corporate_customer_id` BIGINT UNSIGNED NULL,
  `passenger_id` BIGINT UNSIGNED NULL,
  `currency_code` VARCHAR(3) NOT NULL,
  `exchange_rate` DECIMAL(20,8) NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `service_fee_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `grand_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `paid_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `due_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'unpaid',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_invoice_no_unique` (`business_id`,`invoice_no`),
  KEY `atn_invoice_status_idx` (`business_id`,`invoice_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `payment_no` VARCHAR(40) NOT NULL,
  `payment_date` DATE NOT NULL,
  `reservation_id` BIGINT UNSIGNED NULL,
  `invoice_id` BIGINT UNSIGNED NULL,
  `customer_type` VARCHAR(30) NOT NULL,
  `corporate_customer_id` BIGINT UNSIGNED NULL,
  `passenger_id` BIGINT UNSIGNED NULL,
  `payment_method` VARCHAR(30) NOT NULL,
  `payment_account` VARCHAR(150) NULL,
  `reference_no` VARCHAR(100) NULL,
  `currency_code` VARCHAR(3) NOT NULL,
  `exchange_rate` DECIMAL(20,8) NOT NULL DEFAULT 1,
  `amount` DECIMAL(22,4) NOT NULL,
  `base_amount` DECIMAL(22,4) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'posted',
  `remarks` TEXT NULL,
  `received_by` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_payment_no_unique` (`business_id`,`payment_no`),
  KEY `atn_payment_date_idx` (`business_id`,`payment_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_payment_allocations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `payment_id` BIGINT UNSIGNED NOT NULL,
  `invoice_id` BIGINT UNSIGNED NULL,
  `reservation_id` BIGINT UNSIGNED NULL,
  `allocated_amount` DECIMAL(22,4) NOT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `atn_payment_allocation_idx` (`business_id`,`payment_id`,`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_receipts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `receipt_no` VARCHAR(40) NOT NULL,
  `receipt_date` DATE NOT NULL,
  `payment_id` BIGINT UNSIGNED NOT NULL,
  `invoice_id` BIGINT UNSIGNED NULL,
  `reservation_id` BIGINT UNSIGNED NULL,
  `currency_code` VARCHAR(3) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'issued',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_receipt_no_unique` (`business_id`,`receipt_no`),
  KEY `atn_receipt_payment_idx` (`business_id`,`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ATN-005 permissions
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name,'web',NOW(),NOW()
FROM (
    SELECT 'airline_ticketing_new.tickets.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.tickets.issue' AS name
    UNION ALL SELECT 'airline_ticketing_new.invoices.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.invoices.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.payments.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.payments.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.receipts.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.receipts.print' AS name
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` existing
    WHERE existing.name=p.name AND existing.guard_name='web'
);
