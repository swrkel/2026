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
