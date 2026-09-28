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
