-- HOTELMGT_032_SQL.sql
-- Hotel Management Parcel 032 only
-- Advanced Revenue Management, Corporate/Agent Contracts, Group Blocks, Split Folios
-- Global tenant SQL: run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_revenue_seasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `season_code` varchar(40) NOT NULL,
  `season_name` varchar(160) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `rate_adjustment_type` varchar(30) NOT NULL DEFAULT 'percent',
  `rate_adjustment_value` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_revenue_seasons_business_code_unique` (`business_id`,`season_code`),
  KEY `hm_revenue_seasons_business_location_idx` (`business_id`,`business_location_id`),
  KEY `hm_revenue_seasons_date_idx` (`date_from`,`date_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_yield_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `rule_name` varchar(160) NOT NULL,
  `occupancy_from` decimal(10,2) NOT NULL DEFAULT 0.00,
  `occupancy_to` decimal(10,2) NOT NULL DEFAULT 100.00,
  `adjustment_type` varchar(30) NOT NULL DEFAULT 'percent',
  `adjustment_value` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `priority` int NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_yield_rules_business_location_idx` (`business_id`,`business_location_id`),
  KEY `hm_yield_rules_occupancy_idx` (`occupancy_from`,`occupancy_to`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_corporate_contracts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `contract_no` varchar(80) NOT NULL,
  `company_name` varchar(180) NOT NULL,
  `contact_person` varchar(160) DEFAULT NULL,
  `mobile` varchar(60) DEFAULT NULL,
  `email` varchar(160) DEFAULT NULL,
  `contract_from` date DEFAULT NULL,
  `contract_to` date DEFAULT NULL,
  `rate_type` varchar(60) DEFAULT 'contracted',
  `discount_percent` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `credit_limit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `current_balance` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `direct_billing_allowed` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(40) NOT NULL DEFAULT 'active',
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_corporate_contracts_business_no_unique` (`business_id`,`contract_no`),
  KEY `hm_corporate_contracts_business_location_idx` (`business_id`,`business_location_id`),
  KEY `hm_corporate_contracts_status_idx` (`status`,`contract_from`,`contract_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_travel_agent_contracts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `agent_name` varchar(180) NOT NULL,
  `agent_code` varchar(60) NOT NULL,
  `commission_type` varchar(30) NOT NULL DEFAULT 'percent',
  `commission_value` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `contract_from` date DEFAULT NULL,
  `contract_to` date DEFAULT NULL,
  `credit_limit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(40) NOT NULL DEFAULT 'active',
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_travel_agent_contracts_business_code_unique` (`business_id`,`agent_code`),
  KEY `hm_travel_agent_contracts_business_location_idx` (`business_id`,`business_location_id`),
  KEY `hm_travel_agent_contracts_status_idx` (`status`,`contract_from`,`contract_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_group_room_blocks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `block_no` varchar(80) NOT NULL,
  `group_name` varchar(180) NOT NULL,
  `arrival_date` date NOT NULL,
  `departure_date` date NOT NULL,
  `blocked_rooms` int NOT NULL DEFAULT 0,
  `released_rooms` int NOT NULL DEFAULT 0,
  `rate_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `cutoff_date` date DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'blocked',
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_group_room_blocks_business_no_unique` (`business_id`,`block_no`),
  KEY `hm_group_room_blocks_business_location_idx` (`business_id`,`business_location_id`),
  KEY `hm_group_room_blocks_dates_idx` (`arrival_date`,`departure_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_split_folio_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `rule_name` varchar(160) NOT NULL,
  `payer_type` varchar(60) NOT NULL,
  `charge_category` varchar(80) NOT NULL,
  `split_type` varchar(40) NOT NULL DEFAULT 'percent',
  `split_value` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_split_folio_rules_business_location_idx` (`business_id`,`business_location_id`),
  KEY `hm_split_folio_rules_active_idx` (`is_active`,`payer_type`,`charge_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('hotel.revenue_management.view', 'web', NOW(), NOW()),
('hotel.revenue_management.create', 'web', NOW(), NOW()),
('hotel.revenue_management.update', 'web', NOW(), NOW()),
('hotel.revenue_management.delete', 'web', NOW(), NOW()),
('hotel.corporate_contracts.view', 'web', NOW(), NOW()),
('hotel.group_blocks.manage', 'web', NOW(), NOW());
