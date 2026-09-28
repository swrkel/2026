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
