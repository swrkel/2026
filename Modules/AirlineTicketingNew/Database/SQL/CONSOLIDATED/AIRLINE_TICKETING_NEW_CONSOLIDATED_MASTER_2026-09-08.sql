-- Airline Ticketing New - Consolidated Master SQL
-- Built from all supplied ATN parcels in numeric order.
-- Fresh-install script for the database containing the module and permissions tables.
-- Supplied archive does not contain ATN-037 through ATN-041 parcels; no files were invented for that gap.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- ============================================================
-- ATN_001_AIRLINE_TICKETING_NEW_FOUNDATION :: 01_CREATE/001_create_atn_foundation_tables.sql
-- ============================================================
-- ATN-001 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `key` VARCHAR(100) NOT NULL,
  `value_text` TEXT NULL,
  `value_json` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_settings_scope_key_unique` (`business_id`,`business_location_id`,`store_id`,`key`),
  KEY `atn_settings_business_active_idx` (`business_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_sequences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `document_type` VARCHAR(50) NOT NULL,
  `prefix` VARCHAR(20) NULL,
  `next_number` BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `padding` INT UNSIGNED NOT NULL DEFAULT 6,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_sequences_scope_type_unique` (`business_id`,`business_location_id`,`store_id`,`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `event` VARCHAR(80) NOT NULL,
  `auditable_type` VARCHAR(190) NULL,
  `auditable_id` BIGINT UNSIGNED NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `atn_audit_lookup_idx` (`business_id`,`auditable_type`,`auditable_id`),
  KEY `atn_audit_business_date_idx` (`business_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_001_AIRLINE_TICKETING_NEW_FOUNDATION :: 03_INSERT/001_insert_atn_foundation_permissions.sql
-- ============================================================
-- ATN-001 PERMISSIONS
-- Uses INSERT ... SELECT to remain safe when permissions already exist.
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT p.name, 'web', NOW(), NOW()
FROM (
    SELECT 'airline_ticketing_new.access' AS name
    UNION ALL SELECT 'airline_ticketing_new.dashboard.view'
    UNION ALL SELECT 'airline_ticketing_new.settings.manage'
    UNION ALL SELECT 'airline_ticketing_new.audit.view'
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` existing
    WHERE existing.name = p.name AND existing.guard_name = 'web'
);


-- ============================================================
-- ATN_002_AIRLINE_TICKETING_NEW_CORE_MASTERS :: 01_CREATE/002_create_atn_core_master_tables.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS `atn_airlines` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `business_id` BIGINT UNSIGNED NOT NULL, `business_location_id` BIGINT UNSIGNED NULL, `store_id` BIGINT UNSIGNED NULL,
`name` VARCHAR(150) NOT NULL, `iata_code` VARCHAR(2) NULL, `icao_code` VARCHAR(3) NULL, `ticketing_code` VARCHAR(3) NULL, `country_code` VARCHAR(2) NULL,
`phone` VARCHAR(40) NULL, `email` VARCHAR(150) NULL, `website` VARCHAR(190) NULL, `logo_path` VARCHAR(255) NULL, `notes` TEXT NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_by` BIGINT UNSIGNED NULL, `updated_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
KEY `atn_airlines_business_idx` (`business_id`,`is_active`), UNIQUE KEY `atn_airlines_iata_unique` (`business_id`,`iata_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_airports` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `business_id` BIGINT UNSIGNED NOT NULL, `business_location_id` BIGINT UNSIGNED NULL, `store_id` BIGINT UNSIGNED NULL,
`name` VARCHAR(150) NOT NULL, `iata_code` VARCHAR(3) NOT NULL, `icao_code` VARCHAR(4) NULL, `country_code` VARCHAR(2) NOT NULL, `city` VARCHAR(100) NOT NULL, `timezone` VARCHAR(80) NULL,
`latitude` DECIMAL(10,7) NULL, `longitude` DECIMAL(10,7) NULL, `terminal_notes` TEXT NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_by` BIGINT UNSIGNED NULL, `updated_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_airports_iata_unique` (`business_id`,`iata_code`), KEY `atn_airports_city_idx` (`business_id`,`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_aircraft_types` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `business_id` BIGINT UNSIGNED NOT NULL, `business_location_id` BIGINT UNSIGNED NULL, `store_id` BIGINT UNSIGNED NULL,
`name` VARCHAR(150) NOT NULL, `iata_code` VARCHAR(10) NULL, `icao_code` VARCHAR(10) NULL, `manufacturer` VARCHAR(100) NULL, `model` VARCHAR(100) NULL, `seat_capacity` INT UNSIGNED NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1, `created_by` BIGINT UNSIGNED NULL, `updated_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
KEY `atn_aircraft_business_idx` (`business_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_travel_classes` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `business_id` BIGINT UNSIGNED NOT NULL, `business_location_id` BIGINT UNSIGNED NULL, `store_id` BIGINT UNSIGNED NULL,
`name` VARCHAR(100) NOT NULL, `code` VARCHAR(20) NOT NULL, `cabin_code` VARCHAR(5) NULL, `display_order` INT UNSIGNED NOT NULL DEFAULT 0, `is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_by` BIGINT UNSIGNED NULL, `updated_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_travel_classes_code_unique` (`business_id`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_routes` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `business_id` BIGINT UNSIGNED NOT NULL, `business_location_id` BIGINT UNSIGNED NULL, `store_id` BIGINT UNSIGNED NULL,
`code` VARCHAR(40) NOT NULL, `origin_airport_id` BIGINT UNSIGNED NOT NULL, `destination_airport_id` BIGINT UNSIGNED NOT NULL, `default_airline_id` BIGINT UNSIGNED NULL,
`distance_km` DECIMAL(12,2) NULL, `duration_minutes` INT UNSIGNED NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_by` BIGINT UNSIGNED NULL, `updated_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_routes_code_unique` (`business_id`,`code`), KEY `atn_routes_sector_idx` (`business_id`,`origin_airport_id`,`destination_airport_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_suppliers` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `business_id` BIGINT UNSIGNED NOT NULL, `business_location_id` BIGINT UNSIGNED NULL, `store_id` BIGINT UNSIGNED NULL,
`code` VARCHAR(40) NOT NULL, `name` VARCHAR(150) NOT NULL, `supplier_type` VARCHAR(40) NOT NULL, `contact_person` VARCHAR(150) NULL, `phone` VARCHAR(40) NULL, `email` VARCHAR(150) NULL,
`address` TEXT NULL, `currency_code` VARCHAR(3) NULL, `credit_limit` DECIMAL(22,4) NOT NULL DEFAULT 0, `payment_terms_days` INT UNSIGNED NOT NULL DEFAULT 0,
`is_active` TINYINT(1) NOT NULL DEFAULT 1, `created_by` BIGINT UNSIGNED NULL, `updated_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_suppliers_code_unique` (`business_id`,`code`), KEY `atn_suppliers_name_idx` (`business_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_agents` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `business_id` BIGINT UNSIGNED NOT NULL, `business_location_id` BIGINT UNSIGNED NULL, `store_id` BIGINT UNSIGNED NULL,
`code` VARCHAR(40) NOT NULL, `name` VARCHAR(150) NOT NULL, `agent_type` VARCHAR(40) NOT NULL, `contact_person` VARCHAR(150) NULL, `phone` VARCHAR(40) NULL, `email` VARCHAR(150) NULL,
`address` TEXT NULL, `commission_type` VARCHAR(20) NULL, `commission_value` DECIMAL(22,4) NOT NULL DEFAULT 0, `credit_limit` DECIMAL(22,4) NOT NULL DEFAULT 0,
`is_active` TINYINT(1) NOT NULL DEFAULT 1, `created_by` BIGINT UNSIGNED NULL, `updated_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_agents_code_unique` (`business_id`,`code`), KEY `atn_agents_name_idx` (`business_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_currencies` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `business_id` BIGINT UNSIGNED NOT NULL, `business_location_id` BIGINT UNSIGNED NULL, `store_id` BIGINT UNSIGNED NULL,
`code` VARCHAR(3) NOT NULL, `name` VARCHAR(100) NOT NULL, `symbol` VARCHAR(10) NULL, `decimal_places` TINYINT UNSIGNED NOT NULL DEFAULT 2,
`exchange_rate` DECIMAL(20,8) NOT NULL DEFAULT 1, `is_base` TINYINT(1) NOT NULL DEFAULT 0, `is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_by` BIGINT UNSIGNED NULL, `updated_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_currencies_code_unique` (`business_id`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_tax_rules` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `business_id` BIGINT UNSIGNED NOT NULL, `business_location_id` BIGINT UNSIGNED NULL, `store_id` BIGINT UNSIGNED NULL,
`name` VARCHAR(150) NOT NULL, `code` VARCHAR(40) NOT NULL, `calculation_type` VARCHAR(20) NOT NULL, `rate` DECIMAL(12,4) NULL, `fixed_amount` DECIMAL(22,4) NULL,
`applies_to` VARCHAR(40) NOT NULL, `country_code` VARCHAR(2) NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_by` BIGINT UNSIGNED NULL, `updated_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_tax_rules_code_unique` (`business_id`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_commission_rules` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `business_id` BIGINT UNSIGNED NOT NULL, `business_location_id` BIGINT UNSIGNED NULL, `store_id` BIGINT UNSIGNED NULL,
`name` VARCHAR(150) NOT NULL, `code` VARCHAR(40) NOT NULL, `party_type` VARCHAR(30) NOT NULL, `calculation_type` VARCHAR(20) NOT NULL, `value` DECIMAL(22,4) NOT NULL,
`airline_id` BIGINT UNSIGNED NULL, `route_id` BIGINT UNSIGNED NULL, `travel_class_id` BIGINT UNSIGNED NULL, `effective_from` DATE NULL, `effective_to` DATE NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1, `created_by` BIGINT UNSIGNED NULL, `updated_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_commission_rules_code_unique` (`business_id`,`code`), KEY `atn_commission_effective_idx` (`business_id`,`effective_from`,`effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_002_AIRLINE_TICKETING_NEW_CORE_MASTERS :: 03_INSERT/002_insert_atn_core_master_permissions.sql
-- ============================================================
-- ATN-002 permissions
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name,'web',NOW(),NOW()
FROM (
    SELECT 'airline_ticketing_new.airlines.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.airports.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.aircraft-types.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.travel-classes.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.routes.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.suppliers.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.agents.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.currencies.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.tax-rules.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.commission-rules.manage' AS name
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` x WHERE x.name=p.name AND x.guard_name='web'
);


-- ============================================================
-- ATN_003_AIRLINE_TICKETING_NEW_PASSENGERS_CUSTOMERS :: 01_CREATE/003_create_atn_profile_tables.sql
-- ============================================================
-- ATN-003 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_corporate_customers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `customer_no` VARCHAR(40) NOT NULL,
  `company_name` VARCHAR(180) NOT NULL,
  `registration_no` VARCHAR(80) NULL,
  `tax_no` VARCHAR(80) NULL,
  `contact_person` VARCHAR(150) NULL,
  `email` VARCHAR(150) NULL,
  `phone` VARCHAR(40) NULL,
  `alternate_phone` VARCHAR(40) NULL,
  `billing_address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `country_code` VARCHAR(2) NULL,
  `credit_limit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `payment_terms_days` INT UNSIGNED NOT NULL DEFAULT 0,
  `currency_code` VARCHAR(3) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_corp_customer_no_unique` (`business_id`,`customer_no`),
  KEY `atn_corp_customer_name_idx` (`business_id`,`company_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_passengers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `passenger_no` VARCHAR(40) NOT NULL,
  `title` VARCHAR(20) NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `gender` VARCHAR(20) NULL,
  `date_of_birth` DATE NULL,
  `nationality_code` VARCHAR(2) NULL,
  `email` VARCHAR(150) NULL,
  `phone` VARCHAR(40) NULL,
  `alternate_phone` VARCHAR(40) NULL,
  `address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `country_code` VARCHAR(2) NULL,
  `corporate_customer_id` BIGINT UNSIGNED NULL,
  `is_vip` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_passenger_no_unique` (`business_id`,`passenger_no`),
  KEY `atn_passenger_name_idx` (`business_id`,`last_name`,`first_name`),
  KEY `atn_passenger_contact_idx` (`business_id`,`phone`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_passenger_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `passenger_id` BIGINT UNSIGNED NOT NULL,
  `document_type` VARCHAR(40) NOT NULL,
  `document_number` VARCHAR(100) NOT NULL,
  `issuing_country_code` VARCHAR(2) NULL,
  `issued_date` DATE NULL,
  `expiry_date` DATE NULL,
  `place_of_issue` VARCHAR(150) NULL,
  `file_path` VARCHAR(255) NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `verified_by` BIGINT UNSIGNED NULL,
  `verified_at` TIMESTAMP NULL,
  `notes` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_passenger_doc_unique` (`business_id`,`document_type`,`document_number`),
  KEY `atn_passenger_doc_expiry_idx` (`business_id`,`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_passenger_visas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `passenger_id` BIGINT UNSIGNED NOT NULL,
  `country_code` VARCHAR(2) NOT NULL,
  `visa_type` VARCHAR(80) NOT NULL,
  `visa_number` VARCHAR(100) NULL,
  `issued_date` DATE NULL,
  `expiry_date` DATE NULL,
  `entries_allowed` VARCHAR(40) NULL,
  `status` VARCHAR(40) NULL,
  `file_path` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `atn_passenger_visa_expiry_idx` (`business_id`,`expiry_date`),
  KEY `atn_passenger_visa_country_idx` (`business_id`,`country_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_passenger_loyalty_accounts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `passenger_id` BIGINT UNSIGNED NOT NULL,
  `airline_id` BIGINT UNSIGNED NULL,
  `program_name` VARCHAR(120) NOT NULL,
  `membership_number` VARCHAR(100) NOT NULL,
  `tier_name` VARCHAR(80) NULL,
  `points_balance` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `expiry_date` DATE NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_loyalty_member_unique` (`business_id`,`program_name`,`membership_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_passenger_emergency_contacts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `passenger_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `relationship` VARCHAR(80) NULL,
  `phone` VARCHAR(40) NOT NULL,
  `alternate_phone` VARCHAR(40) NULL,
  `email` VARCHAR(150) NULL,
  `country_code` VARCHAR(2) NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `atn_emergency_passenger_idx` (`business_id`,`passenger_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_corporate_contacts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `corporate_customer_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `designation` VARCHAR(100) NULL,
  `department` VARCHAR(100) NULL,
  `email` VARCHAR(150) NULL,
  `phone` VARCHAR(40) NULL,
  `alternate_phone` VARCHAR(40) NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `atn_corporate_contact_idx` (`business_id`,`corporate_customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_003_AIRLINE_TICKETING_NEW_PASSENGERS_CUSTOMERS :: 03_INSERT/003_insert_atn_profile_permissions.sql
-- ============================================================
-- ATN-003 permission SQL
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name, 'web', NOW(), NOW()
FROM (
    SELECT 'airline_ticketing_new.passengers.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.passenger_documents.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.passenger_visas.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.passenger_loyalty.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.passenger_emergency_contacts.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.corporate_customers.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.corporate_contacts.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.reports.document_expiry' AS name
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` existing
    WHERE existing.name = p.name AND existing.guard_name = 'web'
);


-- ============================================================
-- ATN_004_AIRLINE_TICKETING_NEW_QUOTES_RESERVATIONS_PNR :: 01_CREATE/004_create_atn_transaction_tables.sql
-- ============================================================
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


-- ============================================================
-- ATN_004_AIRLINE_TICKETING_NEW_QUOTES_RESERVATIONS_PNR :: 03_INSERT/004_insert_atn_transaction_permissions.sql
-- ============================================================
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


-- ============================================================
-- ATN_005_AIRLINE_TICKETING_NEW_TICKETS_INVOICES_PAYMENTS :: 01_CREATE/005_create_atn_ticketing_payment_tables.sql
-- ============================================================
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


-- ============================================================
-- ATN_005_AIRLINE_TICKETING_NEW_TICKETS_INVOICES_PAYMENTS :: 03_INSERT/005_insert_atn_ticketing_permissions.sql
-- ============================================================
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


-- ============================================================
-- ATN_006_AIRLINE_TICKETING_NEW_REISSUE_VOID_REFUND :: 01_CREATE/006_create_atn_post_ticket_tables.sql
-- ============================================================
-- ATN-006 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_ticket_reissues` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `reissue_no` VARCHAR(40) NOT NULL,
  `original_ticket_id` BIGINT UNSIGNED NOT NULL,
  `new_ticket_id` BIGINT UNSIGNED NULL,
  `reservation_id` BIGINT UNSIGNED NULL,
  `request_date` DATE NOT NULL,
  `processed_date` DATE NULL,
  `reason` TEXT NOT NULL,
  `fare_difference` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_difference` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `service_fee` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `penalty_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `total_collectable` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `requested_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `processed_by` BIGINT UNSIGNED NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_reissue_no_unique` (`business_id`,`reissue_no`),
  KEY `atn_reissue_ticket_idx` (`business_id`,`original_ticket_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_ticket_voids` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `void_no` VARCHAR(40) NOT NULL,
  `ticket_id` BIGINT UNSIGNED NOT NULL,
  `request_date` DATE NOT NULL,
  `void_date` DATE NULL,
  `reason` TEXT NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `requested_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `processed_by` BIGINT UNSIGNED NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_void_no_unique` (`business_id`,`void_no`),
  KEY `atn_void_ticket_idx` (`business_id`,`ticket_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_ticket_cancellations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `cancellation_no` VARCHAR(40) NOT NULL,
  `ticket_id` BIGINT UNSIGNED NOT NULL,
  `request_date` DATE NOT NULL,
  `cancellation_date` DATE NULL,
  `reason` TEXT NOT NULL,
  `cancellation_fee` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `refundable_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `requested_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `processed_by` BIGINT UNSIGNED NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_cancellation_no_unique` (`business_id`,`cancellation_no`),
  KEY `atn_cancellation_ticket_idx` (`business_id`,`ticket_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_refunds` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `refund_no` VARCHAR(40) NOT NULL,
  `ticket_id` BIGINT UNSIGNED NOT NULL,
  `invoice_id` BIGINT UNSIGNED NULL,
  `payment_id` BIGINT UNSIGNED NULL,
  `cancellation_id` BIGINT UNSIGNED NULL,
  `request_date` DATE NOT NULL,
  `approved_date` DATE NULL,
  `refund_date` DATE NULL,
  `currency_code` VARCHAR(3) NOT NULL,
  `gross_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `cancellation_fee` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `service_fee` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `other_deductions` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `refund_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `refund_method` VARCHAR(30) NULL,
  `reference_no` VARCHAR(100) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `requested_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `processed_by` BIGINT UNSIGNED NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_refund_no_unique` (`business_id`,`refund_no`),
  KEY `atn_refund_ticket_idx` (`business_id`,`ticket_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_credit_notes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `credit_note_no` VARCHAR(40) NOT NULL,
  `credit_note_date` DATE NOT NULL,
  `invoice_id` BIGINT UNSIGNED NULL,
  `refund_id` BIGINT UNSIGNED NULL,
  `customer_type` VARCHAR(30) NOT NULL,
  `corporate_customer_id` BIGINT UNSIGNED NULL,
  `passenger_id` BIGINT UNSIGNED NULL,
  `currency_code` VARCHAR(3) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL,
  `reason` TEXT NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'issued',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `atn_credit_note_no_unique` (`business_id`,`credit_note_no`),
  KEY `atn_credit_note_refund_idx` (`business_id`,`refund_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_ticket_action_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `ticket_id` BIGINT UNSIGNED NOT NULL,
  `action_type` VARCHAR(50) NOT NULL,
  `reference_type` VARCHAR(190) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `from_status` VARCHAR(30) NULL,
  `to_status` VARCHAR(30) NULL,
  `reason` TEXT NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `action_by` BIGINT UNSIGNED NULL,
  `action_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `atn_ticket_action_history_idx` (`business_id`,`ticket_id`,`action_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_006_AIRLINE_TICKETING_NEW_REISSUE_VOID_REFUND :: 03_INSERT/006_insert_atn_post_ticket_permissions.sql
-- ============================================================
-- ATN-006 permissions
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name,'web',NOW(),NOW()
FROM (
    SELECT 'airline_ticketing_new.reissues.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.reissues.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.reissues.approve' AS name
    UNION ALL SELECT 'airline_ticketing_new.voids.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.voids.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.voids.approve' AS name
    UNION ALL SELECT 'airline_ticketing_new.cancellations.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.cancellations.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.cancellations.approve' AS name
    UNION ALL SELECT 'airline_ticketing_new.refunds.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.refunds.approve' AS name
    UNION ALL SELECT 'airline_ticketing_new.refunds.process' AS name
    UNION ALL SELECT 'airline_ticketing_new.credit_notes.view' AS name
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` existing
    WHERE existing.name=p.name AND existing.guard_name='web'
);


-- ============================================================
-- ATN_007_TO_011_AIRLINE_TICKETING_NEW_LARGE_PARCEL :: 01_CREATE/007_create_supplier_profit_tables.sql
-- ============================================================
-- ATN-007 TABLES
CREATE TABLE IF NOT EXISTS `atn_supplier_settlements` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`settlement_no` VARCHAR(40) NOT NULL,`supplier_id` BIGINT UNSIGNED NOT NULL,`settlement_date` DATE NOT NULL,`period_from` DATE NOT NULL,`period_to` DATE NOT NULL,
`currency_code` VARCHAR(3) NOT NULL,`gross_payable` DECIMAL(22,4) NOT NULL DEFAULT 0,`commission_deduction` DECIMAL(22,4) NOT NULL DEFAULT 0,
`tax_deduction` DECIMAL(22,4) NOT NULL DEFAULT 0,`other_deduction` DECIMAL(22,4) NOT NULL DEFAULT 0,`net_payable` DECIMAL(22,4) NOT NULL DEFAULT 0,
`paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`due_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`status` VARCHAR(30) NOT NULL DEFAULT 'unpaid',
`remarks` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_supplier_settlement_no_uq` (`business_id`,`settlement_no`),KEY `atn_supplier_settlement_idx` (`business_id`,`supplier_id`,`settlement_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `atn_supplier_settlement_lines` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`settlement_id` BIGINT UNSIGNED NOT NULL,`ticket_id` BIGINT UNSIGNED NOT NULL,`ticket_no` VARCHAR(30) NOT NULL,`travel_date` DATE NULL,
`sale_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`supplier_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,`commission_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`net_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`status` VARCHAR(30) NOT NULL DEFAULT 'included',
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_supplier_settlement_line_idx` (`business_id`,`settlement_id`,`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `atn_agent_commissions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`commission_no` VARCHAR(40) NOT NULL,`agent_id` BIGINT UNSIGNED NOT NULL,`ticket_id` BIGINT UNSIGNED NULL,`invoice_id` BIGINT UNSIGNED NULL,
`commission_date` DATE NOT NULL,`calculation_type` VARCHAR(20) NOT NULL,`basis_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`rate` DECIMAL(12,4) NOT NULL DEFAULT 0,
`commission_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`due_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`status` VARCHAR(30) NOT NULL DEFAULT 'unpaid',`remarks` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_agent_commission_no_uq` (`business_id`,`commission_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `atn_ticket_profits` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`ticket_id` BIGINT UNSIGNED NOT NULL,`invoice_id` BIGINT UNSIGNED NULL,`sale_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`supplier_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
`tax_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,`agent_commission` DECIMAL(22,4) NOT NULL DEFAULT 0,`staff_incentive` DECIMAL(22,4) NOT NULL DEFAULT 0,
`other_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,`gross_profit` DECIMAL(22,4) NOT NULL DEFAULT 0,`net_profit` DECIMAL(22,4) NOT NULL DEFAULT 0,
`calculated_at` DATETIME NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_ticket_profit_uq` (`business_id`,`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_007_TO_011_AIRLINE_TICKETING_NEW_LARGE_PARCEL :: 01_CREATE/008_create_operations_notification_tables.sql
-- ============================================================
-- ATN-008 TABLES
CREATE TABLE IF NOT EXISTS `atn_operational_tasks` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`task_no` VARCHAR(40) NOT NULL,`task_type` VARCHAR(50) NOT NULL,`reference_type` VARCHAR(80) NULL,`reference_id` BIGINT UNSIGNED NULL,
`title` VARCHAR(190) NOT NULL,`description` TEXT NULL,`priority` VARCHAR(20) NOT NULL DEFAULT 'normal',`due_at` DATETIME NULL,
`assigned_to` BIGINT UNSIGNED NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'open',`completed_at` DATETIME NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_operational_task_uq` (`business_id`,`task_type`,`reference_type`,`reference_id`),KEY `atn_operational_due_idx` (`business_id`,`due_at`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `atn_notification_rules` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`event_code` VARCHAR(80) NOT NULL,`channel` VARCHAR(30) NOT NULL,`recipient_type` VARCHAR(30) NOT NULL,`recipient_value` VARCHAR(190) NULL,
`template_subject` VARCHAR(190) NULL,`template_body` TEXT NOT NULL,`lead_minutes` INT NOT NULL DEFAULT 0,`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_notification_rule_idx` (`business_id`,`event_code`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `atn_notification_logs` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`event_code` VARCHAR(80) NOT NULL,`reference_type` VARCHAR(80) NULL,`reference_id` BIGINT UNSIGNED NULL,`channel` VARCHAR(30) NOT NULL,
`recipient` VARCHAR(190) NOT NULL,`subject` VARCHAR(190) NULL,`message` TEXT NOT NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'queued',
`provider_reference` VARCHAR(190) NULL,`sent_at` DATETIME NULL,`error_message` TEXT NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_notification_log_idx` (`business_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_007_TO_011_AIRLINE_TICKETING_NEW_LARGE_PARCEL :: 03_INSERT/007_to_011_insert_permissions.sql
-- ============================================================
-- ATN-007-to-011 PERMISSIONS
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name,'web',NOW(),NOW() FROM (
    SELECT 'airline_ticketing_new.supplier_settlements.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.supplier_settlements.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.agent_commissions.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.profitability.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.operations.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.operations.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.notifications.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.reports.ticket_sales' AS name
    UNION ALL SELECT 'airline_ticketing_new.reports.profitability' AS name
    UNION ALL SELECT 'airline_ticketing_new.admin.health' AS name
) p
WHERE NOT EXISTS (
 SELECT 1 FROM `permissions` x WHERE x.name=p.name AND x.guard_name='web'
);


-- ============================================================
-- ATN_012_TO_016_AIRLINE_TICKETING_NEW_LARGE_PARCEL :: 01_CREATE/012_to_016_create_tables.sql
-- ============================================================
-- ATN-012 TO ATN-016 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_supplier_payments` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`payment_no` VARCHAR(40) NOT NULL,`supplier_id` BIGINT UNSIGNED NOT NULL,`settlement_id` BIGINT UNSIGNED NULL,`payment_date` DATE NOT NULL,
`payment_method` VARCHAR(30) NOT NULL,`payment_account` VARCHAR(150) NULL,`reference_no` VARCHAR(100) NULL,`currency_code` VARCHAR(3) NOT NULL,
`exchange_rate` DECIMAL(20,8) NOT NULL DEFAULT 1,`amount` DECIMAL(22,4) NOT NULL,`base_amount` DECIMAL(22,4) NOT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'posted',`remarks` TEXT NULL,`approved_by` BIGINT UNSIGNED NULL,`paid_by` BIGINT UNSIGNED NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_supplier_payment_no_uq` (`business_id`,`payment_no`),KEY `atn_supplier_payment_idx` (`business_id`,`supplier_id`,`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_bsp_remittances` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`remittance_no` VARCHAR(40) NOT NULL,`period_from` DATE NOT NULL,`period_to` DATE NOT NULL,`remittance_date` DATE NOT NULL,`currency_code` VARCHAR(3) NOT NULL,
`gross_sales` DECIMAL(22,4) NOT NULL DEFAULT 0,`refunds` DECIMAL(22,4) NOT NULL DEFAULT 0,`commissions` DECIMAL(22,4) NOT NULL DEFAULT 0,
`taxes` DECIMAL(22,4) NOT NULL DEFAULT 0,`adjustments` DECIMAL(22,4) NOT NULL DEFAULT 0,`net_payable` DECIMAL(22,4) NOT NULL DEFAULT 0,
`paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`due_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`status` VARCHAR(30) NOT NULL DEFAULT 'unpaid',
`reference_no` VARCHAR(100) NULL,`remarks` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_bsp_remittance_no_uq` (`business_id`,`remittance_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_staff_incentives` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`incentive_no` VARCHAR(40) NOT NULL,`user_id` BIGINT UNSIGNED NOT NULL,`ticket_id` BIGINT UNSIGNED NULL,`invoice_id` BIGINT UNSIGNED NULL,
`incentive_date` DATE NOT NULL,`calculation_type` VARCHAR(20) NOT NULL,`basis_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`rate` DECIMAL(12,4) NOT NULL DEFAULT 0,
`incentive_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,`due_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`status` VARCHAR(30) NOT NULL DEFAULT 'unpaid',`remarks` TEXT NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_staff_incentive_no_uq` (`business_id`,`incentive_no`),KEY `atn_staff_incentive_idx` (`business_id`,`user_id`,`incentive_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_012_TO_016_AIRLINE_TICKETING_NEW_LARGE_PARCEL :: 03_INSERT/012_to_016_insert_permissions.sql
-- ============================================================
-- ATN-012 TO ATN-016 PERMISSIONS
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name,'web',NOW(),NOW() FROM (
    SELECT 'airline_ticketing_new.supplier_payments.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.supplier_payments.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.bsp_remittances.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.bsp_remittances.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.staff_incentives.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.staff_incentives.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.reports.airline_sales' AS name
    UNION ALL SELECT 'airline_ticketing_new.reports.outstanding' AS name
    UNION ALL SELECT 'airline_ticketing_new.notifications.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.notifications.dispatch' AS name
    UNION ALL SELECT 'airline_ticketing_new.testing.run' AS name
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` x WHERE x.name=p.name AND x.guard_name='web'
);


-- ============================================================
-- ATN_017_TO_021_AIRLINE_TICKETING_NEW_LARGE_PARCEL :: 01_CREATE/017_to_021_create_tables.sql
-- ============================================================
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


-- ============================================================
-- ATN_017_TO_021_AIRLINE_TICKETING_NEW_LARGE_PARCEL :: 03_INSERT/017_to_021_insert_permissions.sql
-- ============================================================
-- ATN-017 TO ATN-021 PERMISSIONS
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name,'web',NOW(),NOW() FROM (
    SELECT 'airline_ticketing_new.corporate_agreements.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.corporate_agreements.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.corporate_ledger.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.visa_applications.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.visa_applications.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.tour_packages.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.tour_packages.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.tour_bookings.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.hotels.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.hotels.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.hotel_reservations.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.transport_vehicles.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.transport_vehicles.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.transfer_bookings.create' AS name
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` x WHERE x.name=p.name AND x.guard_name='web'
);


-- ============================================================
-- ATN_022_TO_026_AIRLINE_TICKETING_NEW_ENTERPRISE_PARCEL :: 01_CREATE/022_to_026_create_tables.sql
-- ============================================================
-- ATN-022 TO ATN-026
CREATE TABLE IF NOT EXISTS `atn_account_mappings` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`event_code` VARCHAR(80) NOT NULL,`debit_account_id` BIGINT UNSIGNED NOT NULL,`credit_account_id` BIGINT UNSIGNED NOT NULL,
`description_template` VARCHAR(500) NULL,`is_active` TINYINT(1) NOT NULL DEFAULT 1,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_account_mapping_uq` (`business_id`,`event_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_journal_entries` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`journal_no` VARCHAR(40) NOT NULL,`journal_date` DATE NOT NULL,`reference_type` VARCHAR(80) NULL,`reference_id` BIGINT UNSIGNED NULL,`reference_no` VARCHAR(80) NULL,
`description` TEXT NOT NULL,`currency_code` VARCHAR(3) NOT NULL,`exchange_rate` DECIMAL(20,8) NOT NULL DEFAULT 1,
`total_debit` DECIMAL(22,4) NOT NULL DEFAULT 0,`total_credit` DECIMAL(22,4) NOT NULL DEFAULT 0,`status` VARCHAR(30) NOT NULL DEFAULT 'draft',
`posted_by` BIGINT UNSIGNED NULL,`posted_at` DATETIME NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_journal_no_uq` (`business_id`,`journal_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_journal_lines` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`journal_entry_id` BIGINT UNSIGNED NOT NULL,`account_id` BIGINT UNSIGNED NOT NULL,`line_description` VARCHAR(500) NULL,
`debit` DECIMAL(22,4) NOT NULL DEFAULT 0,`credit` DECIMAL(22,4) NOT NULL DEFAULT 0,`currency_code` VARCHAR(3) NOT NULL,
`base_debit` DECIMAL(22,4) NOT NULL DEFAULT 0,`base_credit` DECIMAL(22,4) NOT NULL DEFAULT 0,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_exchange_rates` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`rate_date` DATE NOT NULL,`from_currency` VARCHAR(3) NOT NULL,`to_currency` VARCHAR(3) NOT NULL,`rate` DECIMAL(20,8) NOT NULL,
`source` VARCHAR(100) NULL,`is_locked` TINYINT(1) NOT NULL DEFAULT 0,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_exchange_rate_uq` (`business_id`,`rate_date`,`from_currency`,`to_currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_currency_revaluations` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`revaluation_no` VARCHAR(40) NOT NULL,`revaluation_date` DATE NOT NULL,`currency_code` VARCHAR(3) NOT NULL,
`old_rate` DECIMAL(20,8) NOT NULL,`new_rate` DECIMAL(20,8) NOT NULL,`foreign_balance` DECIMAL(22,4) NOT NULL,
`old_base_value` DECIMAL(22,4) NOT NULL,`new_base_value` DECIMAL(22,4) NOT NULL,`gain_loss` DECIMAL(22,4) NOT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'draft',`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_bsp_periods` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`period_code` VARCHAR(40) NOT NULL,`period_from` DATE NOT NULL,`period_to` DATE NOT NULL,`payment_due_date` DATE NOT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'open',`closed_by` BIGINT UNSIGNED NULL,`closed_at` DATETIME NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_bsp_adjustments` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`adjustment_no` VARCHAR(40) NOT NULL,`adjustment_type` VARCHAR(20) NOT NULL,`airline_id` BIGINT UNSIGNED NULL,`ticket_id` BIGINT UNSIGNED NULL,
`remittance_id` BIGINT UNSIGNED NULL,`adjustment_date` DATE NOT NULL,`currency_code` VARCHAR(3) NOT NULL,`amount` DECIMAL(22,4) NOT NULL,
`reason` TEXT NOT NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'open',`document_reference` VARCHAR(100) NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_crm_interactions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`customer_type` VARCHAR(30) NOT NULL,`passenger_id` BIGINT UNSIGNED NULL,`corporate_customer_id` BIGINT UNSIGNED NULL,
`interaction_type` VARCHAR(50) NOT NULL,`interaction_at` DATETIME NOT NULL,`subject` VARCHAR(190) NOT NULL,`notes` TEXT NULL,
`follow_up_at` DATETIME NULL,`assigned_to` BIGINT UNSIGNED NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'open',
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_loyalty_transactions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`passenger_id` BIGINT UNSIGNED NOT NULL,`transaction_date` DATE NOT NULL,`transaction_type` VARCHAR(30) NOT NULL,
`reference_type` VARCHAR(80) NULL,`reference_id` BIGINT UNSIGNED NULL,`points` DECIMAL(18,2) NOT NULL,`balance_after` DECIMAL(18,2) NOT NULL,
`description` VARCHAR(500) NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_022_TO_026_AIRLINE_TICKETING_NEW_ENTERPRISE_PARCEL :: 03_INSERT/022_to_026_insert_permissions.sql
-- ============================================================
INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.accounting.journals.view' AS name
UNION ALL SELECT 'airline_ticketing_new.accounting.journals.post' AS name
UNION ALL SELECT 'airline_ticketing_new.account_mappings.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.currency_rates.view' AS name
UNION ALL SELECT 'airline_ticketing_new.currency_rates.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.currency_revaluation.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.bsp.periods.view' AS name
UNION ALL SELECT 'airline_ticketing_new.bsp.periods.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.bsp.adjustments.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.crm.interactions.view' AS name
UNION ALL SELECT 'airline_ticketing_new.crm.interactions.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.loyalty.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.management_dashboard.view' AS name) p
WHERE NOT EXISTS(SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web');


-- ============================================================
-- ATN_027_TO_031_AIRLINE_TICKETING_NEW_ENTERPRISE_PARCEL :: 01_CREATE/027_to_031_create_tables.sql
-- ============================================================
-- ATN-027 TO ATN-031 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_gds_provider_settings` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`provider_code` VARCHAR(50) NOT NULL,`display_name` VARCHAR(150) NOT NULL,`credentials_json` LONGTEXT NULL,`options_json` JSON NULL,
`priority` INT NOT NULL DEFAULT 100,`is_active` TINYINT(1) NOT NULL DEFAULT 0,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_gds_provider_uq` (`business_id`,`provider_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_gds_request_logs` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`provider_code` VARCHAR(50) NOT NULL,
`operation` VARCHAR(80) NOT NULL,`request_payload` JSON NULL,`response_payload` JSON NULL,`status` VARCHAR(30) NOT NULL,
`error_message` TEXT NULL,`started_at` DATETIME NULL,`completed_at` DATETIME NULL,`duration_ms` INT NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,KEY `atn_gds_log_idx` (`business_id`,`provider_code`,`operation`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_flight_schedules` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`airline_id` BIGINT UNSIGNED NOT NULL,`flight_number` VARCHAR(20) NOT NULL,`origin_airport_id` BIGINT UNSIGNED NOT NULL,`destination_airport_id` BIGINT UNSIGNED NOT NULL,
`departure_at` DATETIME NOT NULL,`arrival_at` DATETIME NOT NULL,`aircraft_type_id` BIGINT UNSIGNED NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'scheduled',
`is_active` TINYINT(1) NOT NULL DEFAULT 1,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_flight_schedule_idx` (`business_id`,`departure_at`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_flight_disruptions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`flight_schedule_id` BIGINT UNSIGNED NULL,`flight_number` VARCHAR(20) NOT NULL,`disruption_type` VARCHAR(30) NOT NULL,`description` TEXT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'open',`reported_at` DATETIME NULL,`resolved_at` DATETIME NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_managed_documents` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`document_type` VARCHAR(80) NOT NULL,`owner_type` VARCHAR(190) NULL,`owner_id` BIGINT UNSIGNED NULL,`file_name` VARCHAR(255) NOT NULL,
`mime_type` VARCHAR(100) NULL,`file_size` BIGINT UNSIGNED NULL,`storage_disk` VARCHAR(50) NOT NULL,`storage_path` VARCHAR(500) NOT NULL,
`version_no` INT NOT NULL DEFAULT 1,`expiry_date` DATE NULL,`is_confidential` TINYINT(1) NOT NULL DEFAULT 0,`metadata_json` JSON NULL,`notes` TEXT NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_managed_document_idx` (`business_id`,`owner_type`,`owner_id`,`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_managed_document_versions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`document_id` BIGINT UNSIGNED NOT NULL,
`version_no` INT NOT NULL,`file_name` VARCHAR(255) NOT NULL,`storage_disk` VARCHAR(50) NOT NULL,`storage_path` VARCHAR(500) NOT NULL,
`uploaded_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_document_version_uq` (`document_id`,`version_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_workflow_definitions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`event_code` VARCHAR(80) NOT NULL,
`name` VARCHAR(150) NOT NULL,`priority` INT NOT NULL DEFAULT 100,`conditions_json` JSON NULL,`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_workflow_definition_idx` (`business_id`,`event_code`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_workflow_instances` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`workflow_definition_id` BIGINT UNSIGNED NOT NULL,
`reference_type` VARCHAR(190) NOT NULL,`reference_id` BIGINT UNSIGNED NOT NULL,`current_step` INT NOT NULL DEFAULT 1,
`status` VARCHAR(30) NOT NULL DEFAULT 'pending',`started_at` DATETIME NULL,`completed_at` DATETIME NULL,
`completion_comment` TEXT NULL,`completed_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_workflow_instance_idx` (`business_id`,`status`,`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_feature_switches` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`feature_code` VARCHAR(100) NOT NULL,
`is_enabled` TINYINT(1) NOT NULL DEFAULT 0,`config_json` JSON NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_feature_switch_uq` (`business_id`,`feature_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_api_credentials` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`credential_code` VARCHAR(100) NOT NULL,
`provider_name` VARCHAR(100) NOT NULL,`credentials_json` LONGTEXT NULL,`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`expires_at` DATETIME NULL,`last_used_at` DATETIME NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_api_credential_uq` (`business_id`,`credential_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_027_TO_031_AIRLINE_TICKETING_NEW_ENTERPRISE_PARCEL :: 03_INSERT/027_to_031_insert_permissions.sql
-- ============================================================
INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.gds.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.flight_schedules.view' AS name
UNION ALL SELECT 'airline_ticketing_new.flight_schedules.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.flight_disruptions.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.documents.view' AS name
UNION ALL SELECT 'airline_ticketing_new.documents.create' AS name
UNION ALL SELECT 'airline_ticketing_new.documents.delete' AS name
UNION ALL SELECT 'airline_ticketing_new.workflow.view' AS name
UNION ALL SELECT 'airline_ticketing_new.workflow.approve' AS name
UNION ALL SELECT 'airline_ticketing_new.workflow.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.admin.features' AS name
UNION ALL SELECT 'airline_ticketing_new.admin.api_credentials' AS name) p
WHERE NOT EXISTS(SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web');


-- ============================================================
-- ATN_032_TO_036_AIRLINE_TICKETING_NEW_ENTERPRISE_PARCEL :: 01_CREATE/032_to_036_create_tables.sql
-- ============================================================
-- ATN-032 TO ATN-036 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_portal_users` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`passenger_id` BIGINT UNSIGNED NULL,`corporate_customer_id` BIGINT UNSIGNED NULL,
`name` VARCHAR(150) NOT NULL,`email` VARCHAR(190) NOT NULL,`password` VARCHAR(255) NOT NULL,`portal_type` VARCHAR(30) NOT NULL DEFAULT 'customer',
`email_verified_at` DATETIME NULL,`remember_token` VARCHAR(100) NULL,`last_login_at` DATETIME NULL,`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_portal_user_uq` (`business_id`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_portal_requests` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`portal_user_id` BIGINT UNSIGNED NOT NULL,
`request_type` VARCHAR(80) NOT NULL,`reference_type` VARCHAR(190) NULL,`reference_id` BIGINT UNSIGNED NULL,`payload_json` JSON NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'pending',`requested_at` DATETIME NULL,`completed_at` DATETIME NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_portal_request_idx` (`business_id`,`portal_user_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_b2b_agents` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`agent_code` VARCHAR(40) NOT NULL,`name` VARCHAR(180) NOT NULL,`email` VARCHAR(190) NULL,`phone` VARCHAR(40) NULL,
`credit_limit` DECIMAL(22,4) NOT NULL DEFAULT 0,`available_credit` DECIMAL(22,4) NOT NULL DEFAULT 0,`currency_code` VARCHAR(3) NOT NULL,
`commission_rate` DECIMAL(12,4) NOT NULL DEFAULT 0,`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_b2b_agent_uq` (`business_id`,`agent_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_b2b_wallet_transactions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`b2b_agent_id` BIGINT UNSIGNED NOT NULL,
`transaction_date` DATETIME NOT NULL,`reference_type` VARCHAR(190) NULL,`reference_id` BIGINT UNSIGNED NULL,`description` VARCHAR(500) NULL,
`debit` DECIMAL(22,4) NOT NULL DEFAULT 0,`credit` DECIMAL(22,4) NOT NULL DEFAULT 0,`balance_after` DECIMAL(22,4) NOT NULL DEFAULT 0,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_b2b_wallet_idx` (`business_id`,`b2b_agent_id`,`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_analytics_snapshots` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`snapshot_date` DATE NOT NULL,
`metrics_json` JSON NOT NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_analytics_snapshot_uq` (`business_id`,`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_032_TO_036_AIRLINE_TICKETING_NEW_ENTERPRISE_PARCEL :: 03_INSERT/032_to_036_insert_permissions.sql
-- ============================================================
INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.portal.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.b2b_agents.view' AS name
UNION ALL SELECT 'airline_ticketing_new.b2b_agents.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.b2b_wallet.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.api.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.mobile.view' AS name
UNION ALL SELECT 'airline_ticketing_new.analytics.view' AS name
UNION ALL SELECT 'airline_ticketing_new.analytics.generate' AS name) p
WHERE NOT EXISTS(SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web');


-- ============================================================
-- ATN_042_TO_046_AIRLINE_TICKETING_NEW_PRODUCTION_PARCEL :: 01_CREATE/042_to_046_create_tables.sql
-- ============================================================
-- ATN-042 TO ATN-046 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_backup_records` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`backup_type` VARCHAR(50) NOT NULL,
`storage_disk` VARCHAR(50) NOT NULL,
`file_path` VARCHAR(500) NULL,
`file_size` BIGINT UNSIGNED NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'pending',
`error_message` TEXT NULL,
`metadata_json` JSON NULL,
`started_at` DATETIME NULL,
`completed_at` DATETIME NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_backup_records_idx` (`business_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_scheduled_task_logs` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`task_code` VARCHAR(100) NOT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'pending',
`context_json` JSON NULL,
`error_message` TEXT NULL,
`started_at` DATETIME NULL,
`completed_at` DATETIME NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_scheduled_task_logs_idx` (`business_id`,`task_code`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_performance_metrics` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`metric_code` VARCHAR(100) NOT NULL,
`metric_value` DECIMAL(22,4) NOT NULL DEFAULT 0,
`context_json` JSON NULL,
`recorded_at` DATETIME NOT NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_performance_metrics_idx` (`business_id`,`metric_code`,`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_042_TO_046_AIRLINE_TICKETING_NEW_PRODUCTION_PARCEL :: 03_INSERT/042_to_046_insert_permissions.sql
-- ============================================================
INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.backups.view' AS name
UNION ALL SELECT 'airline_ticketing_new.backups.create' AS name
UNION ALL SELECT 'airline_ticketing_new.monitoring.view' AS name
UNION ALL SELECT 'airline_ticketing_new.deployment.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.scheduler.run' AS name
UNION ALL SELECT 'airline_ticketing_new.certification.run' AS name) p
WHERE NOT EXISTS(
    SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);


-- ============================================================
-- ATN_047_TO_054_AIRLINE_TICKETING_NEW_8_SECTION_PARCEL :: 01_CREATE/047_to_054_create_tables.sql
-- ============================================================
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


-- ============================================================
-- ATN_047_TO_054_AIRLINE_TICKETING_NEW_8_SECTION_PARCEL :: 03_INSERT/047_to_054_insert_permissions.sql
-- ============================================================
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


-- ============================================================
-- ATN_055_TO_062_AIRLINE_TICKETING_NEW_8_SECTION_PARCEL :: 01_CREATE/055_to_062_create_tables.sql
-- ============================================================
-- ATN-055 TO ATN-062 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_reissue_quotes` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`business_location_id` BIGINT UNSIGNED NULL,
`store_id` BIGINT UNSIGNED NULL,
`quote_no` VARCHAR(40) NOT NULL,
`ticket_id` BIGINT UNSIGNED NOT NULL,
`quoted_at` DATETIME NOT NULL,
`fare_difference` DECIMAL(22,4) NOT NULL DEFAULT 0,
`tax_difference` DECIMAL(22,4) NOT NULL DEFAULT 0,
`penalty_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`service_fee` DECIMAL(22,4) NOT NULL DEFAULT 0,
`total_collectable` DECIMAL(22,4) NOT NULL DEFAULT 0,
`status` VARCHAR(30) NOT NULL DEFAULT 'quoted',
`details_json` JSON NULL,
`created_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_reissue_quote_uq` (`business_id`,`quote_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_ticket_exchanges` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`business_location_id` BIGINT UNSIGNED NULL,
`store_id` BIGINT UNSIGNED NULL,
`exchange_no` VARCHAR(40) NOT NULL,
`original_ticket_id` BIGINT UNSIGNED NOT NULL,
`replacement_ticket_id` BIGINT UNSIGNED NOT NULL,
`exchange_date` DATE NOT NULL,
`original_value` DECIMAL(22,4) NOT NULL DEFAULT 0,
`new_value` DECIMAL(22,4) NOT NULL DEFAULT 0,
`exchange_difference` DECIMAL(22,4) NOT NULL DEFAULT 0,
`status` VARCHAR(30) NOT NULL DEFAULT 'completed',
`remarks` TEXT NULL,
`created_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_ticket_exchange_uq` (`business_id`,`exchange_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_refund_rules` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`airline_id` BIGINT UNSIGNED NULL,
`rule_name` VARCHAR(180) NOT NULL,
`effective_from` DATE NOT NULL,
`effective_to` DATE NULL,
`penalty_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`penalty_percent` DECIMAL(12,4) NOT NULL DEFAULT 0,
`priority` INT NOT NULL DEFAULT 100,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_refund_rule_idx` (`business_id`,`airline_id`,`is_active`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_agency_debit_memos` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`business_location_id` BIGINT UNSIGNED NULL,
`store_id` BIGINT UNSIGNED NULL,
`adm_no` VARCHAR(40) NOT NULL,
`airline_id` BIGINT UNSIGNED NULL,
`ticket_id` BIGINT UNSIGNED NULL,
`memo_date` DATE NOT NULL,
`due_date` DATE NULL,
`currency_code` VARCHAR(3) NOT NULL,
`amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`disputed_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`reason` TEXT NOT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'open',
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_adm_uq` (`business_id`,`adm_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_agency_credit_memos` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`business_location_id` BIGINT UNSIGNED NULL,
`store_id` BIGINT UNSIGNED NULL,
`acm_no` VARCHAR(40) NOT NULL,
`airline_id` BIGINT UNSIGNED NULL,
`ticket_id` BIGINT UNSIGNED NULL,
`memo_date` DATE NOT NULL,
`currency_code` VARCHAR(3) NOT NULL,
`amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`reason` TEXT NOT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'open',
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_acm_uq` (`business_id`,`acm_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_electronic_misc_documents` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`business_location_id` BIGINT UNSIGNED NULL,
`store_id` BIGINT UNSIGNED NULL,
`emd_no` VARCHAR(40) NOT NULL,
`reservation_id` BIGINT UNSIGNED NULL,
`ticket_id` BIGINT UNSIGNED NULL,
`passenger_id` BIGINT UNSIGNED NULL,
`service_type` VARCHAR(80) NOT NULL,
`issue_date` DATE NOT NULL,
`currency_code` VARCHAR(3) NOT NULL,
`amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`status` VARCHAR(30) NOT NULL DEFAULT 'issued',
`remarks` TEXT NULL,
`created_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_emd_uq` (`business_id`,`emd_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_travel_policies` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`corporate_customer_id` BIGINT UNSIGNED NOT NULL,
`policy_code` VARCHAR(40) NOT NULL,
`name` VARCHAR(180) NOT NULL,
`effective_from` DATE NOT NULL,
`effective_to` DATE NULL,
`rules_json` JSON NOT NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_travel_policy_uq` (`business_id`,`policy_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_approval_matrices` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`business_location_id` BIGINT UNSIGNED NULL,
`event_code` VARCHAR(80) NOT NULL,
`approval_level` INT NOT NULL DEFAULT 1,
`approver_type` VARCHAR(30) NOT NULL,
`approver_id` BIGINT UNSIGNED NULL,
`minimum_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
`maximum_amount` DECIMAL(22,4) NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_approval_matrix_idx` (`business_id`,`event_code`,`approval_level`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_operational_exceptions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`business_location_id` BIGINT UNSIGNED NULL,
`store_id` BIGINT UNSIGNED NULL,
`exception_type` VARCHAR(80) NOT NULL,
`severity` VARCHAR(20) NOT NULL DEFAULT 'normal',
`reference_type` VARCHAR(190) NULL,
`reference_id` BIGINT UNSIGNED NULL,
`reference_no` VARCHAR(100) NULL,
`description` TEXT NOT NULL,
`context_json` JSON NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'open',
`detected_by` BIGINT UNSIGNED NULL,
`detected_at` DATETIME NOT NULL,
`resolved_by` BIGINT UNSIGNED NULL,
`resolved_at` DATETIME NULL,
`resolution_note` TEXT NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_operational_exception_idx` (`business_id`,`severity`,`status`,`detected_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_055_TO_062_AIRLINE_TICKETING_NEW_8_SECTION_PARCEL :: 03_INSERT/055_to_062_insert_permissions.sql
-- ============================================================
INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.reissue_quotes.create' AS name
UNION ALL SELECT 'airline_ticketing_new.ticket_exchanges.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.refund_rules.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.adm_acm.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.emd.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.travel_policies.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.approval_matrices.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.exceptions.view' AS name
UNION ALL SELECT 'airline_ticketing_new.exceptions.resolve' AS name) p
WHERE NOT EXISTS (
    SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);


-- ============================================================
-- ATN_071_TO_078_AIRLINE_TICKETING_NEW_8_SECTION_PARCEL :: 01_CREATE/071_to_078_create_tables.sql
-- ============================================================
-- ATN-071 TO ATN-078 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_saved_reports` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`user_id` BIGINT UNSIGNED NULL,
`report_code` VARCHAR(100) NOT NULL,
`name` VARCHAR(180) NOT NULL,
`filters_json` JSON NULL,
`columns_json` JSON NULL,
`is_shared` TINYINT(1) NOT NULL DEFAULT 0,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_saved_report_idx` (`business_id`,`user_id`,`report_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_forecast_models` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`model_code` VARCHAR(100) NOT NULL,
`name` VARCHAR(180) NOT NULL,
`metric_code` VARCHAR(100) NOT NULL,
`algorithm` VARCHAR(80) NOT NULL,
`parameters_json` JSON NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_forecast_model_uq` (`business_id`,`model_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_scheduled_reports` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`user_id` BIGINT UNSIGNED NULL,
`report_code` VARCHAR(100) NOT NULL,
`name` VARCHAR(180) NOT NULL,
`format` VARCHAR(20) NOT NULL DEFAULT 'pdf',
`frequency` VARCHAR(30) NOT NULL,
`filters_json` JSON NULL,
`recipients_json` JSON NULL,
`last_run_at` DATETIME NULL,
`next_run_at` DATETIME NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_scheduled_report_idx` (`business_id`,`next_run_at`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_report_templates` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`user_id` BIGINT UNSIGNED NULL,
`template_code` VARCHAR(100) NOT NULL,
`name` VARCHAR(180) NOT NULL,
`data_source_json` JSON NOT NULL,
`filters_json` JSON NULL,
`columns_json` JSON NULL,
`sorting_json` JSON NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_report_template_uq` (`business_id`,`template_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_kpi_alert_rules` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`name` VARCHAR(180) NOT NULL,
`metric_code` VARCHAR(100) NOT NULL,
`operator` VARCHAR(10) NOT NULL,
`threshold_value` DECIMAL(22,4) NOT NULL DEFAULT 0,
`severity` VARCHAR(20) NOT NULL DEFAULT 'normal',
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_kpi_alert_rule_idx` (`business_id`,`metric_code`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_071_TO_078_AIRLINE_TICKETING_NEW_8_SECTION_PARCEL :: 03_INSERT/071_to_078_insert_permissions.sql
-- ============================================================
INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.reporting.executive' AS name
UNION ALL SELECT 'airline_ticketing_new.reporting.centre' AS name
UNION ALL SELECT 'airline_ticketing_new.reporting.bi' AS name
UNION ALL SELECT 'airline_ticketing_new.reporting.forecasting' AS name
UNION ALL SELECT 'airline_ticketing_new.reporting.scheduled' AS name
UNION ALL SELECT 'airline_ticketing_new.reporting.builder' AS name
UNION ALL SELECT 'airline_ticketing_new.reporting.kpi_alerts' AS name
UNION ALL SELECT 'airline_ticketing_new.reporting.export' AS name) p
WHERE NOT EXISTS(
 SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);


-- ============================================================
-- ATN_079_TO_086_AIRLINE_TICKETING_NEW_8_SECTION_PARCEL :: 01_CREATE/079_to_086_create_tables.sql
-- ============================================================
-- ATN-079 TO ATN-086 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_permission_profiles` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`profile_code` VARCHAR(80) NOT NULL,
`name` VARCHAR(150) NOT NULL,
`permissions_json` JSON NOT NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_by` BIGINT UNSIGNED NULL,
`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_permission_profile_uq` (`business_id`,`profile_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_security_audit_logs` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NULL,
`user_id` BIGINT UNSIGNED NULL,
`event_code` VARCHAR(100) NOT NULL,
`ip_address` VARCHAR(64) NULL,
`user_agent` TEXT NULL,
`route_name` VARCHAR(190) NULL,
`context_json` JSON NULL,
`recorded_at` DATETIME NOT NULL,
KEY `atn_security_audit_idx` (`business_id`,`event_code`,`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_encrypted_settings` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`setting_key` VARCHAR(120) NOT NULL,
`encrypted_value` LONGTEXT NOT NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_encrypted_setting_uq` (`business_id`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_api_rate_limits` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`api_key_id` BIGINT UNSIGNED NULL,
`endpoint` VARCHAR(190) NOT NULL,
`request_count` INT NOT NULL DEFAULT 0,
`window_started_at` DATETIME NOT NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_api_rate_limit_idx` (`business_id`,`endpoint`,`window_started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_enterprise_settings` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`setting_group` VARCHAR(80) NOT NULL,
`setting_key` VARCHAR(120) NOT NULL,
`setting_value_json` JSON NULL,
`is_locked` TINYINT(1) NOT NULL DEFAULT 0,
`created_by` BIGINT UNSIGNED NULL,
`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_enterprise_setting_uq` (`business_id`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_079_TO_086_AIRLINE_TICKETING_NEW_8_SECTION_PARCEL :: 03_INSERT/079_to_086_insert_permissions.sql
-- ============================================================
INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.ui.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.permission_profiles.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.security_audit.view' AS name
UNION ALL SELECT 'airline_ticketing_new.encrypted_settings.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.api_security.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.mobile.view' AS name
UNION ALL SELECT 'airline_ticketing_new.enterprise_settings.view' AS name
UNION ALL SELECT 'airline_ticketing_new.enterprise_settings.manage' AS name) p
WHERE NOT EXISTS(
 SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);


-- ============================================================
-- ATN_087_TO_094_AIRLINE_TICKETING_NEW_8_SECTION_PARCEL :: 01_CREATE/087_to_094_create_tables.sql
-- ============================================================
-- ATN-087 TO ATN-094 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_queue_executions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`job_code` VARCHAR(100) NOT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'pending',
`payload_json` JSON NULL,
`message` TEXT NULL,
`duration_ms` INT NULL,
`started_at` DATETIME NULL,
`completed_at` DATETIME NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_queue_execution_idx` (`business_id`,`job_code`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_health_incidents` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`incident_code` VARCHAR(100) NOT NULL,
`severity` VARCHAR(20) NOT NULL DEFAULT 'warning',
`description` TEXT NOT NULL,
`context_json` JSON NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'open',
`detected_at` DATETIME NOT NULL,
`resolved_at` DATETIME NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_health_incident_idx` (`business_id`,`incident_code`,`status`,`detected_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_module_upgrades` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`from_version` VARCHAR(40) NOT NULL,
`to_version` VARCHAR(40) NOT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'pending',
`steps_json` JSON NULL,
`error_message` TEXT NULL,
`started_at` DATETIME NULL,
`completed_at` DATETIME NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_module_upgrade_idx` (`to_version`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ATN_087_TO_094_AIRLINE_TICKETING_NEW_8_SECTION_PARCEL :: 02_ALTER/087_to_094_add_performance_indexes.sql
-- ============================================================
-- ATN-087 TO ATN-094 PERFORMANCE INDEXES
ALTER TABLE `atn_tickets`
    ADD INDEX `atn_tickets_perf_idx` (`business_id`,`business_location_id`,`store_id`,`issue_date`,`status`);

ALTER TABLE `atn_reservations`
    ADD INDEX `atn_reservations_perf_idx` (`business_id`,`business_location_id`,`store_id`,`reservation_date`,`status`);

ALTER TABLE `atn_invoices`
    ADD INDEX `atn_invoices_perf_idx` (`business_id`,`invoice_date`,`status`,`due_total`);

ALTER TABLE `atn_operational_tasks`
    ADD INDEX `atn_operational_tasks_perf_idx` (`business_id`,`assigned_to`,`status`,`due_at`);


-- ============================================================
-- ATN_087_TO_094_AIRLINE_TICKETING_NEW_8_SECTION_PARCEL :: 03_INSERT/087_to_094_insert_permissions.sql
-- ============================================================
INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.performance.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.cache.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.queue.monitor' AS name
UNION ALL SELECT 'airline_ticketing_new.diagnostics.view' AS name
UNION ALL SELECT 'airline_ticketing_new.health_monitor.view' AS name
UNION ALL SELECT 'airline_ticketing_new.upgrade.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.installer.run' AS name
UNION ALL SELECT 'airline_ticketing_new.deployment.validate' AS name) p
WHERE NOT EXISTS (
    SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);


-- ============================================================
-- ATN_095_TO_102_AIRLINE_TICKETING_NEW_FINAL_ENTERPRISE_RELEASE :: 03_INSERT/095_to_102_insert_permissions.sql
-- ============================================================
INSERT INTO permissions(name,guard_name,created_at,updated_at) SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.certification.run' AS name
UNION ALL SELECT 'airline_ticketing_new.final_install.run' AS name
UNION ALL SELECT 'airline_ticketing_new.production_readiness.run' AS name
UNION ALL SELECT 'airline_ticketing_new.final_optimization.run' AS name) p WHERE NOT EXISTS (SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web');


-- ============================================================
-- ATN_095_TO_102_AIRLINE_TICKETING_NEW_FINAL_ENTERPRISE_RELEASE :: 04_INDEXES/095_final_performance_indexes.sql
-- ============================================================
-- Execute only when indexes do not already exist.
ALTER TABLE `atn_tickets` ADD INDEX `atn_final_ticket_lookup_idx` (`business_id`,`business_location_id`,`store_id`,`ticket_no`,`issue_date`,`status`);
ALTER TABLE `atn_reservations` ADD INDEX `atn_final_reservation_lookup_idx` (`business_id`,`business_location_id`,`store_id`,`reservation_no`,`reservation_date`,`status`);
ALTER TABLE `atn_payments` ADD INDEX `atn_final_payment_lookup_idx` (`business_id`,`payment_date`,`payment_method`,`status`);
ALTER TABLE `atn_refunds` ADD INDEX `atn_final_refund_lookup_idx` (`business_id`,`request_date`,`status`,`ticket_id`);


-- ============================================================
-- ATN_095_TO_102_AIRLINE_TICKETING_NEW_FINAL_ENTERPRISE_RELEASE :: 05_VIEWS/095_create_reporting_views.sql
-- ============================================================
CREATE OR REPLACE VIEW `atn_v_ticket_financial_summary` AS
SELECT t.business_id,t.business_location_id,t.store_id,t.id ticket_id,t.ticket_no,t.issue_date,t.currency_code,t.grand_total sale_amount,COALESCE(p.net_profit,0) net_profit
FROM atn_tickets t LEFT JOIN atn_ticket_profits p ON p.business_id=t.business_id AND p.ticket_id=t.id;


SET FOREIGN_KEY_CHECKS = 1;
