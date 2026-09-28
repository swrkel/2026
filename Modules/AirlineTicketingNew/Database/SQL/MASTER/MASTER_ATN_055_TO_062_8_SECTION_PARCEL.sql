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
