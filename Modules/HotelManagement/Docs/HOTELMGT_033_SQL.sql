-- HOTELMGT_033_SQL.sql
-- Hotel Management Parcel 033 only
-- City Ledger / Accounts Receivable for corporate, agent and house account direct billing
-- Global tenant SQL: run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_city_ledger_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `account_code` varchar(60) NOT NULL,
  `account_name` varchar(180) NOT NULL,
  `account_type` varchar(40) NOT NULL DEFAULT 'corporate',
  `contact_person` varchar(160) DEFAULT NULL,
  `mobile` varchar(60) DEFAULT NULL,
  `email` varchar(160) DEFAULT NULL,
  `credit_limit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `current_balance` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `credit_days` int NOT NULL DEFAULT 30,
  `status` varchar(40) NOT NULL DEFAULT 'active',
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_city_ledger_accounts_business_code_unique` (`business_id`,`account_code`),
  KEY `hm_city_ledger_accounts_business_location_idx` (`business_id`,`business_location_id`),
  KEY `hm_city_ledger_accounts_status_idx` (`status`,`account_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_city_ledger_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `ledger_account_id` bigint unsigned NOT NULL,
  `invoice_no` varchar(80) NOT NULL,
  `folio_no` varchar(80) DEFAULT NULL,
  `guest_name` varchar(180) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `invoice_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `paid_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `balance_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(40) NOT NULL DEFAULT 'open',
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_city_ledger_invoices_business_no_unique` (`business_id`,`invoice_no`),
  KEY `hm_city_ledger_invoices_account_idx` (`ledger_account_id`,`status`),
  KEY `hm_city_ledger_invoices_due_idx` (`business_id`,`due_date`,`status`),
  KEY `hm_city_ledger_invoices_location_idx` (`business_id`,`business_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_city_ledger_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `ledger_account_id` bigint unsigned NOT NULL,
  `invoice_id` bigint unsigned DEFAULT NULL,
  `receipt_no` varchar(80) NOT NULL,
  `receipt_date` date NOT NULL,
  `payment_method` varchar(40) NOT NULL DEFAULT 'cash',
  `reference_no` varchar(120) DEFAULT NULL,
  `receipt_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_city_ledger_receipts_business_no_unique` (`business_id`,`receipt_no`),
  KEY `hm_city_ledger_receipts_account_idx` (`ledger_account_id`,`receipt_date`),
  KEY `hm_city_ledger_receipts_invoice_idx` (`invoice_id`),
  KEY `hm_city_ledger_receipts_location_idx` (`business_id`,`business_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_city_ledger_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `ledger_account_id` bigint unsigned NOT NULL,
  `adjustment_no` varchar(80) NOT NULL,
  `adjustment_date` date NOT NULL,
  `adjustment_type` varchar(20) NOT NULL DEFAULT 'debit',
  `adjustment_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `reason` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_city_ledger_adjustments_business_no_unique` (`business_id`,`adjustment_no`),
  KEY `hm_city_ledger_adjustments_account_idx` (`ledger_account_id`,`adjustment_date`),
  KEY `hm_city_ledger_adjustments_location_idx` (`business_id`,`business_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('hotel.city_ledger.view', 'web', NOW(), NOW()),
('hotel.city_ledger.create', 'web', NOW(), NOW()),
('hotel.city_ledger.update', 'web', NOW(), NOW()),
('hotel.city_ledger.receipt', 'web', NOW(), NOW()),
('hotel.city_ledger.adjustment', 'web', NOW(), NOW());
