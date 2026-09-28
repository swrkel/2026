
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
