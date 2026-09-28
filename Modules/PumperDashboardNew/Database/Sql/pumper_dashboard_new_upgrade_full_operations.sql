-- Existing-installation upgrade and foundation-schema repair.
-- This file intentionally contains the complete idempotent installer.
-- Pumper Dashboard-New 2.0.0 Full Operations Production Release
-- MySQL 5.7+/MariaDB compatible tenant-database installer.
-- Safe to execute repeatedly. All module-owned tables use the pone_ prefix.
-- No foreign keys are added to legacy/core/Petro PD tables; integration is application-service based.

SET NAMES utf8mb4;
SET time_zone = '+05:30';

-- -----------------------------------------------------------------------------
-- FOUNDATION-SCHEMA COMPATIBILITY
-- The first Pumper Dashboard-New foundation used several current table names
-- with incompatible columns (for example session_uuid instead of session_key).
-- Preserve those tables before creating the current schema. No data is deleted.
-- -----------------------------------------------------------------------------
SET @pone_legacy_foundation := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'pone_operator_sessions'
              AND column_name = 'session_uuid'
        )
        OR EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'pone_pd_operators'
              AND column_name = 'petro_pd_operator_id'
        )
        OR EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'pone_shifts'
              AND column_name = 'shift_uuid'
        ),
        1,
        0
    )
);

DROP PROCEDURE IF EXISTS `pone_archive_legacy_table`;
DELIMITER $$
CREATE PROCEDURE `pone_archive_legacy_table`(
    IN p_table_name VARCHAR(64),
    IN p_backup_name VARCHAR(64)
)
BEGIN
    IF @pone_legacy_foundation = 1
       AND EXISTS (
           SELECT 1 FROM information_schema.tables
           WHERE table_schema = DATABASE() AND table_name = p_table_name
       ) THEN
        IF EXISTS (
            SELECT 1 FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = p_backup_name
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'PONE legacy backup table already exists. Review pone_v1_* tables before rerunning.';
        END IF;

        SET @pone_archive_sql = CONCAT(
            'RENAME TABLE `', REPLACE(p_table_name, '`', '``'),
            '` TO `', REPLACE(p_backup_name, '`', '``'), '`'
        );
        PREPARE pone_archive_stmt FROM @pone_archive_sql;
        EXECUTE pone_archive_stmt;
        DEALLOCATE PREPARE pone_archive_stmt;
    END IF;
END$$
DELIMITER ;

CALL `pone_archive_legacy_table`('pone_pd_operators', 'pone_v1_pd_operators');
CALL `pone_archive_legacy_table`('pone_operator_sessions', 'pone_v1_operator_sessions');
CALL `pone_archive_legacy_table`('pone_number_sequences', 'pone_v1_number_sequences');
CALL `pone_archive_legacy_table`('pone_shifts', 'pone_v1_shifts');
CALL `pone_archive_legacy_table`('pone_pump_assignments', 'pone_v1_pump_assignments');
CALL `pone_archive_legacy_table`('pone_payments', 'pone_v1_payments');
CALL `pone_archive_legacy_table`('pone_other_sales', 'pone_v1_other_sales');
CALL `pone_archive_legacy_table`('pone_unload_stocks', 'pone_v1_unload_stocks');
CALL `pone_archive_legacy_table`('pone_integration_outbox', 'pone_v1_integration_outbox');
CALL `pone_archive_legacy_table`('pone_audit_logs', 'pone_v1_audit_logs');
DROP PROCEDURE IF EXISTS `pone_archive_legacy_table`;


CREATE TABLE IF NOT EXISTS `pone_login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `company_number` VARCHAR(100) NULL,
  `ip_address` VARCHAR(64) NOT NULL,
  `passcode_fingerprint` VARCHAR(64) NULL,
  `attempt_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('active','blocked') NOT NULL DEFAULT 'active',
  `blocked_until` TIMESTAMP NULL DEFAULT NULL,
  `last_attempt_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_login_company_ip_uq` (`company_number`,`ip_address`),
  KEY `pone_login_business_status_idx` (`business_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_pd_operators` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `display_name` VARCHAR(191) NOT NULL,
  `login_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `settings` LONGTEXT NULL,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `last_login_ip` VARCHAR(64) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_pd_operator_business_uq` (`business_id`,`pd_operator_id`),
  UNIQUE KEY `pone_pd_operator_user_uq` (`business_id`,`user_id`),
  KEY `pone_pd_operator_scope_idx` (`business_id`,`location_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Restore operator mappings from the retained foundation table.
DROP PROCEDURE IF EXISTS `pone_restore_v1_operator_profiles`;
DELIMITER $$
CREATE PROCEDURE `pone_restore_v1_operator_profiles`()
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = 'pone_v1_pd_operators'
    ) THEN
        INSERT INTO `pone_pd_operators`
            (`business_id`, `location_id`, `pd_operator_id`, `user_id`, `display_name`,
             `login_enabled`, `status`, `settings`, `created_at`, `updated_at`)
        SELECT
            `business_id`,
            COALESCE(NULLIF(`force_location_id`, 0), `location_id`),
            `petro_pd_operator_id`,
            `user_id`,
            COALESCE(NULLIF(TRIM(`operator_code`), ''), CONCAT('Pump Operator #', `petro_pd_operator_id`)),
            IF(COALESCE(`enabled`, 1) = 1 AND COALESCE(`can_login`, 1) = 1, 1, 0),
            IF(COALESCE(`enabled`, 1) = 1 AND COALESCE(`can_login`, 1) = 1, 'active', 'inactive'),
            `settings`,
            `created_at`,
            CURRENT_TIMESTAMP
        FROM `pone_v1_pd_operators`
        ON DUPLICATE KEY UPDATE
            `location_id` = VALUES(`location_id`),
            `user_id` = VALUES(`user_id`),
            `display_name` = VALUES(`display_name`),
            `login_enabled` = VALUES(`login_enabled`),
            `status` = VALUES(`status`),
            `settings` = VALUES(`settings`),
            `updated_at` = CURRENT_TIMESTAMP;
    END IF;
END$$
DELIMITER ;
CALL `pone_restore_v1_operator_profiles`();
DROP PROCEDURE IF EXISTS `pone_restore_v1_operator_profiles`;

CREATE TABLE IF NOT EXISTS `pone_operator_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_key` VARCHAR(64) NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `shift_id` BIGINT UNSIGNED NULL,
  `shift_number` VARCHAR(80) NULL,
  `logged_in_at` TIMESTAMP NOT NULL,
  `last_seen_at` TIMESTAMP NULL DEFAULT NULL,
  `logged_out_at` TIMESTAMP NULL DEFAULT NULL,
  `status` ENUM('active','logged_out','expired') NOT NULL DEFAULT 'active',
  `ip_address` VARCHAR(64) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_operator_sessions_session_key_unique` (`session_key`),
  KEY `pone_session_operator_status_idx` (`business_id`,`operator_profile_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_module_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope_key` VARCHAR(100) NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `integration_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `integration_mode` ENUM('petro_pd_new','petropd','local_only') NOT NULL DEFAULT 'petro_pd_new',
  `sync_during_operation` TINYINT(1) NOT NULL DEFAULT 1,
  `require_clean_sync_before_close` TINYINT(1) NOT NULL DEFAULT 1,
  `allow_operator_open_shift` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_close_with_open_pumps` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_close_with_pending_sync` TINYINT(1) NOT NULL DEFAULT 0,
  `amount_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 4,
  `quantity_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `meter_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `shift_prefix` VARCHAR(30) NOT NULL DEFAULT 'PONE-SH-',
  `payment_prefix` VARCHAR(30) NOT NULL DEFAULT 'PONE-PAY-',
  `other_sale_prefix` VARCHAR(30) NOT NULL DEFAULT 'PONE-OS-',
  `unload_prefix` VARCHAR(30) NOT NULL DEFAULT 'PONE-UL-',
  `settings` LONGTEXT NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_module_settings_scope_key_unique` (`scope_key`),
  KEY `pone_settings_scope_idx` (`business_id`,`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_number_sequences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope_key` VARCHAR(150) NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `sequence_type` VARCHAR(40) NOT NULL,
  `prefix` VARCHAR(30) NOT NULL,
  `next_number` BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `padding` TINYINT UNSIGNED NOT NULL DEFAULT 6,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_number_sequences_scope_key_unique` (`scope_key`),
  KEY `pone_sequence_business_type_idx` (`business_id`,`sequence_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_shifts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(36) NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `shift_number` VARCHAR(80) NOT NULL,
  `status` ENUM('planned','open','closing','closed','cancelled') NOT NULL DEFAULT 'open',
  `opened_at` TIMESTAMP NULL DEFAULT NULL,
  `closed_at` TIMESTAMP NULL DEFAULT NULL,
  `meter_sales_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `other_sales_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `payments_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `expected_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `shortage_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `excess_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `petropd_shift_id` BIGINT UNSIGNED NULL,
  `petropd_shift_number` BIGINT UNSIGNED NULL,
  `petropd_meter_sale_id` BIGINT UNSIGNED NULL,
  `integration_status` ENUM('not_required','pending','synced','failed') NOT NULL DEFAULT 'pending',
  `integration_error` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `closed_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_shifts_uuid_unique` (`uuid`),
  UNIQUE KEY `pone_shift_business_number_uq` (`business_id`,`shift_number`),
  KEY `pone_shift_operator_status_idx` (`business_id`,`pd_operator_id`,`status`),
  KEY `pone_shift_scope_date_idx` (`business_id`,`location_id`,`opened_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_pump_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `pump_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NULL,
  `opening_meter` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `current_meter` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `closing_meter` DECIMAL(22,6) NULL,
  `testing_quantity` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `sold_quantity` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` ENUM('assigned','open','closed','cancelled') NOT NULL DEFAULT 'assigned',
  `assigned_at` TIMESTAMP NULL DEFAULT NULL,
  `accepted_at` TIMESTAMP NULL DEFAULT NULL,
  `closed_at` TIMESTAMP NULL DEFAULT NULL,
  `confirmed_at` TIMESTAMP NULL DEFAULT NULL,
  `petropd_assignment_id` BIGINT UNSIGNED NULL,
  `petropd_meter_detail_id` BIGINT UNSIGNED NULL,
  `petropd_day_entry_id` BIGINT UNSIGNED NULL,
  `integration_status` ENUM('not_required','pending','synced','failed') NOT NULL DEFAULT 'pending',
  `integration_error` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_assignment_shift_pump_uq` (`shift_id`,`pump_id`),
  UNIQUE KEY `pone_assignment_petropd_uq` (`petropd_assignment_id`),
  KEY `pone_assignment_operator_status_idx` (`business_id`,`pd_operator_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_meter_readings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `assignment_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pump_id` INT UNSIGNED NOT NULL,
  `reading_type` ENUM('opening','current','closing','testing') NOT NULL,
  `meter_value` DECIMAL(22,6) NULL,
  `testing_quantity` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `source` ENUM('manual','imported','system') NOT NULL DEFAULT 'manual',
  `recorded_at` TIMESTAMP NOT NULL,
  `recorded_by` INT UNSIGNED NULL,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_reading_assignment_type_idx` (`assignment_id`,`reading_type`,`recorded_at`),
  KEY `pone_reading_business_date_idx` (`business_id`,`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(36) NOT NULL,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `payment_number` VARCHAR(80) NOT NULL,
  `payment_type` ENUM('cash','card','cheque','credit','shortage','excess','other') NOT NULL,
  `gross_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `customer_id` INT UNSIGNED NULL,
  `reference_no` VARCHAR(191) NULL,
  `card_type` VARCHAR(80) NULL,
  `card_last_four` VARCHAR(4) NULL,
  `slip_no` VARCHAR(100) NULL,
  `bank_name` VARCHAR(191) NULL,
  `cheque_no` VARCHAR(100) NULL,
  `cheque_date` DATE NULL,
  `transaction_at` TIMESTAMP NOT NULL,
  `note` TEXT NULL,
  `status` ENUM('draft','confirmed','void') NOT NULL DEFAULT 'confirmed',
  `petropd_payment_id` BIGINT UNSIGNED NULL,
  `integration_status` ENUM('not_required','pending','synced','failed') NOT NULL DEFAULT 'pending',
  `integration_error` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `voided_by` INT UNSIGNED NULL,
  `voided_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_payments_uuid_unique` (`uuid`),
  UNIQUE KEY `pone_payment_business_number_uq` (`business_id`,`payment_number`),
  UNIQUE KEY `pone_payment_petropd_uq` (`petropd_payment_id`),
  KEY `pone_payment_shift_type_idx` (`shift_id`,`payment_type`,`status`),
  KEY `pone_payment_business_date_idx` (`business_id`,`transaction_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_credit_sales` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `order_number` VARCHAR(191) NOT NULL,
  `bill_number` VARCHAR(191) NULL,
  `vehicle_number` VARCHAR(100) NOT NULL,
  `customer_reference` VARCHAR(191) NULL,
  `order_date` DATE NOT NULL,
  `due_date` DATE NULL,
  `customer_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
  `order_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
  `vehicle_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
  `customer_confirmed_at` TIMESTAMP NULL DEFAULT NULL,
  `order_confirmed_at` TIMESTAMP NULL DEFAULT NULL,
  `vehicle_confirmed_at` TIMESTAMP NULL DEFAULT NULL,
  `confirmation_rounds` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_at` TIMESTAMP NULL DEFAULT NULL,
  `confirmed_by` INT UNSIGNED NULL,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_credit_sales_payment_id_unique` (`payment_id`),
  KEY `pone_credit_customer_date_idx` (`business_id`,`customer_id`,`order_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_credit_sale_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `credit_sale_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` DECIMAL(22,6) NOT NULL,
  `unit_price` DECIMAL(22,6) NOT NULL,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL,
  `petropd_credit_detail_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_credit_line_sale_product_idx` (`credit_sale_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_other_sales` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(36) NOT NULL,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `store_id` INT UNSIGNED NULL,
  `sale_number` VARCHAR(80) NOT NULL,
  `sale_at` TIMESTAMP NOT NULL,
  `gross_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `net_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` ENUM('draft','confirmed','void') NOT NULL DEFAULT 'confirmed',
  `note` TEXT NULL,
  `integration_status` ENUM('not_required','pending','synced','failed') NOT NULL DEFAULT 'pending',
  `integration_error` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_other_sales_uuid_unique` (`uuid`),
  UNIQUE KEY `pone_other_sale_business_number_uq` (`business_id`,`sale_number`),
  KEY `pone_other_sale_shift_status_idx` (`shift_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_other_sale_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `other_sale_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` DECIMAL(22,6) NOT NULL,
  `unit_price` DECIMAL(22,6) NOT NULL,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL,
  `balance_stock_snapshot` DECIMAL(22,6) NULL,
  `petropd_other_sale_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_other_line_sale_product_idx` (`other_sale_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_unload_stocks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(36) NOT NULL,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `store_id` INT UNSIGNED NULL,
  `receipt_number` VARCHAR(80) NOT NULL,
  `bill_number` VARCHAR(191) NULL,
  `supplier_reference` VARCHAR(191) NULL,
  `unloaded_at` TIMESTAMP NOT NULL,
  `total_quantity` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` ENUM('draft','confirmed','void') NOT NULL DEFAULT 'confirmed',
  `note` TEXT NULL,
  `integration_status` ENUM('not_required','pending','synced','failed') NOT NULL DEFAULT 'pending',
  `integration_error` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_unload_stocks_uuid_unique` (`uuid`),
  UNIQUE KEY `pone_unload_business_number_uq` (`business_id`,`receipt_number`),
  KEY `pone_unload_shift_status_idx` (`shift_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_unload_stock_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `unload_stock_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `tank_id` INT UNSIGNED NULL,
  `quantity` DECIMAL(22,6) NOT NULL,
  `unit_cost` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `dip_reading` DECIMAL(22,6) NULL,
  `current_stock` DECIMAL(22,6) NULL,
  `petropd_unload_stock_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_unload_line_product_idx` (`unload_stock_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_day_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `assignment_id` BIGINT UNSIGNED NULL,
  `pump_id` INT UNSIGNED NULL,
  `entry_type` ENUM('note','incident','expense','deposit','meter','testing','other') NOT NULL,
  `reference_no` VARCHAR(191) NULL,
  `quantity` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `starting_meter` DECIMAL(22,6) NULL,
  `closing_meter` DECIMAL(22,6) NULL,
  `testing_quantity` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `entry_at` TIMESTAMP NOT NULL,
  `note` TEXT NULL,
  `status` ENUM('active','void') NOT NULL DEFAULT 'active',
  `petropd_day_entry_id` BIGINT UNSIGNED NULL,
  `integration_status` ENUM('not_required','pending','synced','failed') NOT NULL DEFAULT 'pending',
  `integration_error` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_day_entry_shift_type_idx` (`shift_id`,`entry_type`,`entry_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_integration_outbox` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `idempotency_key` VARCHAR(191) NOT NULL,
  `aggregate_type` VARCHAR(80) NOT NULL,
  `aggregate_id` BIGINT UNSIGNED NOT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `payload` LONGTEXT NULL,
  `status` ENUM('pending','processing','processed','failed') NOT NULL DEFAULT 'pending',
  `attempts` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `available_at` TIMESTAMP NULL DEFAULT NULL,
  `processed_at` TIMESTAMP NULL DEFAULT NULL,
  `last_error` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_integration_outbox_idempotency_key_unique` (`idempotency_key`),
  KEY `pone_outbox_status_idx` (`business_id`,`status`,`available_at`),
  KEY `pone_outbox_aggregate_idx` (`aggregate_type`,`aggregate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_integration_links` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `source_type` VARCHAR(80) NOT NULL,
  `source_id` BIGINT UNSIGNED NOT NULL,
  `target_table` VARCHAR(100) NOT NULL,
  `target_id` BIGINT UNSIGNED NULL,
  `sync_hash` VARCHAR(64) NULL,
  `status` ENUM('pending','synced','failed','retired') NOT NULL DEFAULT 'pending',
  `last_error` TEXT NULL,
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_integration_source_target_uq` (`source_type`,`source_id`,`target_table`),
  KEY `pone_integration_link_status_idx` (`business_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NULL,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(100) NOT NULL,
  `entity_id` BIGINT UNSIGNED NULL,
  `before_data` LONGTEXT NULL,
  `after_data` LONGTEXT NULL,
  `ip_address` VARCHAR(64) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pone_audit_entity_idx` (`business_id`,`entity_type`,`entity_id`),
  KEY `pone_audit_business_date_idx` (`business_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Full-operations and production-hardening upgrade section.
-- Add every full-operations column without failing on older tenant databases.
DROP PROCEDURE IF EXISTS `pone_add_column_if_missing`;
DELIMITER $$
CREATE PROCEDURE `pone_add_column_if_missing`(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_column_definition TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = p_table_name
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
          AND column_name = p_column_name
    ) THEN
        SET @pone_alter_sql = CONCAT(
            'ALTER TABLE `', REPLACE(p_table_name, '`', '``'),
            '` ADD COLUMN `', REPLACE(p_column_name, '`', '``'),
            '` ', p_column_definition
        );
        PREPARE pone_alter_stmt FROM @pone_alter_sql;
        EXECUTE pone_alter_stmt;
        DEALLOCATE PREPARE pone_alter_stmt;
    END IF;
END$$
DELIMITER ;

CALL `pone_add_column_if_missing`('pone_operator_sessions', 'session_key', 'VARCHAR(64) NULL');
CALL `pone_add_column_if_missing`('pone_operator_sessions', 'pd_operator_id', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_operator_sessions', 'shift_id', 'BIGINT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_operator_sessions', 'shift_number', 'VARCHAR(80) NULL');
CALL `pone_add_column_if_missing`('pone_operator_sessions', 'last_seen_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL `pone_add_column_if_missing`('pone_module_settings', 'cash_denomination_enabled', 'TINYINT(1) NOT NULL DEFAULT 1');
CALL `pone_add_column_if_missing`('pone_module_settings', 'multi_card_enabled', 'TINYINT(1) NOT NULL DEFAULT 1');
CALL `pone_add_column_if_missing`('pone_module_settings', 'cheque_enabled', 'TINYINT(1) NOT NULL DEFAULT 1');
CALL `pone_add_column_if_missing`('pone_module_settings', 'credit_sale_enabled', 'TINYINT(1) NOT NULL DEFAULT 1');
CALL `pone_add_column_if_missing`('pone_module_settings', 'require_credit_dual_confirmation', 'TINYINT(1) NOT NULL DEFAULT 1');
CALL `pone_add_column_if_missing`('pone_module_settings', 'require_collection_before_close', 'TINYINT(1) NOT NULL DEFAULT 0');
CALL `pone_add_column_if_missing`('pone_module_settings', 'allow_payment_edit', 'TINYINT(1) NOT NULL DEFAULT 1');
CALL `pone_add_column_if_missing`('pone_module_settings', 'payment_edit_lock_minutes', 'SMALLINT UNSIGNED NOT NULL DEFAULT 1440');
CALL `pone_add_column_if_missing`('pone_module_settings', 'auto_logout_minutes', 'SMALLINT UNSIGNED NOT NULL DEFAULT 0');
CALL `pone_add_column_if_missing`('pone_module_settings', 'receipt_paper_size', 'VARCHAR(20) NOT NULL DEFAULT ''80mm''');
CALL `pone_add_column_if_missing`('pone_module_settings', 'default_store_id', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_module_settings', 'settlement_prefix', 'VARCHAR(30) NOT NULL DEFAULT ''PONE-SET-''');
CALL `pone_add_column_if_missing`('pone_module_settings', 'collection_prefix', 'VARCHAR(30) NOT NULL DEFAULT ''PONE-COL-''');
CALL `pone_add_column_if_missing`('pone_module_settings', 'recovery_prefix', 'VARCHAR(30) NOT NULL DEFAULT ''PONE-REC-''');
CALL `pone_add_column_if_missing`('pone_module_settings', 'commission_prefix', 'VARCHAR(30) NOT NULL DEFAULT ''PONE-COM-''');
CALL `pone_add_column_if_missing`('pone_shifts', 'petropd_shift_id', 'BIGINT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_shifts', 'petropd_shift_number', 'BIGINT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_shifts', 'collection_form_no', 'VARCHAR(100) NULL');
CALL `pone_add_column_if_missing`('pone_shifts', 'settlement_no', 'VARCHAR(100) NULL');
CALL `pone_add_column_if_missing`('pone_shifts', 'declared_total', 'DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pone_add_column_if_missing`('pone_shifts', 'reconciliation_status', 'ENUM(''pending'',''balanced'',''shortage'',''excess'') NOT NULL DEFAULT ''pending''');
CALL `pone_add_column_if_missing`('pone_shifts', 'reconciled_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL `pone_add_column_if_missing`('pone_shifts', 'reconciled_by', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_shifts', 'closed_statement_printed_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL `pone_add_column_if_missing`('pone_pump_assignments', 'accepted_by', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_pump_assignments', 'confirmed_by', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_pump_assignments', 'closed_by', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_pump_assignments', 'closing_note', 'TEXT NULL');
CALL `pone_add_column_if_missing`('pone_payments', 'collection_form_no', 'VARCHAR(100) NULL');
CALL `pone_add_column_if_missing`('pone_payments', 'parent_payment_id', 'BIGINT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_payments', 'account_id', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_payments', 'edit_version', 'SMALLINT UNSIGNED NOT NULL DEFAULT 1');
CALL `pone_add_column_if_missing`('pone_payments', 'edit_reason', 'TEXT NULL');
CALL `pone_add_column_if_missing`('pone_payments', 'edited_by', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_payments', 'edited_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL `pone_add_column_if_missing`('pone_payments', 'print_copy_option', 'VARCHAR(30) NULL');
CALL `pone_add_column_if_missing`('pone_payments', 'printed_count', 'SMALLINT UNSIGNED NOT NULL DEFAULT 0');
CALL `pone_add_column_if_missing`('pone_payments', 'last_printed_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL `pone_add_column_if_missing`('pone_credit_sales', 'print_copy_option', 'VARCHAR(30) NULL');
CALL `pone_add_column_if_missing`('pone_credit_sales', 'printed_count', 'SMALLINT UNSIGNED NOT NULL DEFAULT 0');
CALL `pone_add_column_if_missing`('pone_credit_sales', 'last_printed_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL `pone_add_column_if_missing`('pone_other_sales', 'customer_id', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_other_sales', 'payment_method', 'VARCHAR(40) NOT NULL DEFAULT ''cash''');
CALL `pone_add_column_if_missing`('pone_other_sales', 'collection_form_no', 'VARCHAR(100) NULL');
CALL `pone_add_column_if_missing`('pone_other_sales', 'edited_by', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_other_sales', 'edited_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL `pone_add_column_if_missing`('pone_other_sales', 'voided_by', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_other_sales', 'voided_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL `pone_add_column_if_missing`('pone_other_sales', 'printed_count', 'SMALLINT UNSIGNED NOT NULL DEFAULT 0');
CALL `pone_add_column_if_missing`('pone_other_sales', 'last_printed_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL `pone_add_column_if_missing`('pone_unload_stocks', 'supplier_id', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_unload_stocks', 'edited_by', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_unload_stocks', 'edited_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL `pone_add_column_if_missing`('pone_unload_stocks', 'voided_by', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_unload_stocks', 'voided_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL `pone_add_column_if_missing`('pone_unload_stocks', 'printed_count', 'SMALLINT UNSIGNED NOT NULL DEFAULT 0');
CALL `pone_add_column_if_missing`('pone_unload_stocks', 'last_printed_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL `pone_add_column_if_missing`('pone_day_entries', 'settlement_no', 'VARCHAR(100) NULL');
CALL `pone_add_column_if_missing`('pone_day_entries', 'edited_by', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_day_entries', 'edited_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL `pone_add_column_if_missing`('pone_day_entries', 'voided_by', 'INT UNSIGNED NULL');
CALL `pone_add_column_if_missing`('pone_day_entries', 'voided_at', 'TIMESTAMP NULL DEFAULT NULL');
DROP PROCEDURE IF EXISTS `pone_add_column_if_missing`;

-- Full-operations supporting tables.
CREATE TABLE IF NOT EXISTS `pone_assignment_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assignment_id` BIGINT UNSIGNED NOT NULL,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `event_type` ENUM('assigned','accepted','confirmed','current_meter','closed','reopened','cancelled') NOT NULL,
  `meter_value` DECIMAL(22,6) NULL,
  `testing_quantity` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `metadata` LONGTEXT NULL,
  `occurred_at` TIMESTAMP NOT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_assignment_event_assignment_idx` (`assignment_id`,`occurred_at`),
  KEY `pone_assignment_event_scope_idx` (`business_id`,`event_type`,`occurred_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_payment_cash_denominations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` BIGINT UNSIGNED NOT NULL,
  `denomination` DECIMAL(16,2) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_cash_denom_payment_value_uq` (`payment_id`,`denomination`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_payment_card_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` BIGINT UNSIGNED NOT NULL,
  `card_type` VARCHAR(80) NULL,
  `last_four` VARCHAR(4) NULL,
  `slip_no` VARCHAR(100) NULL,
  `account_id` INT UNSIGNED NULL,
  `reference_no` VARCHAR(191) NULL,
  `amount` DECIMAL(22,4) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_card_line_payment_slip_idx` (`payment_id`,`slip_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_payment_edit_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `version_no` SMALLINT UNSIGNED NOT NULL,
  `reason` TEXT NOT NULL,
  `before_data` LONGTEXT NOT NULL,
  `after_data` LONGTEXT NOT NULL,
  `edited_by` INT UNSIGNED NULL,
  `edited_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_payment_history_version_uq` (`payment_id`,`version_no`),
  KEY `pone_payment_history_business_idx` (`business_id`,`edited_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_daily_collections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(36) NOT NULL,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `collection_number` VARCHAR(80) NOT NULL,
  `collection_at` TIMESTAMP NOT NULL,
  `expected_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `cash_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `card_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `cheque_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `credit_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `other_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `declared_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `difference_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` ENUM('draft','confirmed','void') NOT NULL DEFAULT 'confirmed',
  `note` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `confirmed_by` INT UNSIGNED NULL,
  `voided_by` INT UNSIGNED NULL,
  `voided_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_daily_collections_uuid_unique` (`uuid`),
  UNIQUE KEY `pone_collection_business_number_uq` (`business_id`,`collection_number`),
  KEY `pone_collection_shift_idx` (`shift_id`,`status`,`collection_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_shift_settlement_references` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_no` VARCHAR(100) NOT NULL,
  `settlement_date` DATE NOT NULL,
  `note` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_settlement_business_shift_uq` (`business_id`,`shift_id`),
  UNIQUE KEY `pone_settlement_shift_number_uq` (`shift_id`,`settlement_no`),
  KEY `pone_settlement_business_date_idx` (`business_id`,`settlement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_shortage_recoveries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shift_id` BIGINT UNSIGNED NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `recovery_number` VARCHAR(80) NOT NULL,
  `recovery_date` DATE NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL,
  `payment_method` VARCHAR(40) NOT NULL DEFAULT 'cash',
  `reference_no` VARCHAR(191) NULL,
  `note` TEXT NULL,
  `status` ENUM('confirmed','void') NOT NULL DEFAULT 'confirmed',
  `created_by` INT UNSIGNED NULL,
  `voided_by` INT UNSIGNED NULL,
  `voided_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_recovery_business_number_uq` (`business_id`,`recovery_number`),
  KEY `pone_recovery_operator_idx` (`operator_profile_id`,`recovery_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_excess_commissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shift_id` BIGINT UNSIGNED NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `commission_number` VARCHAR(80) NOT NULL,
  `commission_date` DATE NOT NULL,
  `base_excess_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `commission_type` ENUM('fixed','percentage') NOT NULL DEFAULT 'percentage',
  `commission_rate` DECIMAL(14,6) NOT NULL DEFAULT 0,
  `commission_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `status` ENUM('confirmed','void') NOT NULL DEFAULT 'confirmed',
  `created_by` INT UNSIGNED NULL,
  `voided_by` INT UNSIGNED NULL,
  `voided_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_commission_business_number_uq` (`business_id`,`commission_number`),
  KEY `pone_commission_operator_idx` (`operator_profile_id`,`commission_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_operator_ledger_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entry_key` VARCHAR(191) NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `shift_id` BIGINT UNSIGNED NULL,
  `source_type` VARCHAR(80) NOT NULL,
  `source_id` BIGINT UNSIGNED NULL,
  `entry_at` TIMESTAMP NOT NULL,
  `reference_no` VARCHAR(100) NULL,
  `description` VARCHAR(500) NOT NULL,
  `debit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `credit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` ENUM('active','void') NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_operator_ledger_entries_entry_key_unique` (`entry_key`),
  KEY `pone_ledger_operator_date_idx` (`operator_profile_id`,`entry_at`,`status`),
  KEY `pone_ledger_source_idx` (`source_type`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_operator_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `shift_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(191) NOT NULL,
  `category` VARCHAR(80) NOT NULL DEFAULT 'general',
  `original_name` VARCHAR(255) NOT NULL,
  `disk` VARCHAR(40) NOT NULL DEFAULT 'public',
  `path` VARCHAR(500) NOT NULL,
  `mime_type` VARCHAR(120) NULL,
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `visibility` ENUM('operator','management') NOT NULL DEFAULT 'operator',
  `uploaded_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_document_operator_category_idx` (`operator_profile_id`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_operator_notes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `shift_id` BIGINT UNSIGNED NULL,
  `note_type` ENUM('general','incident','hand_over','settlement') NOT NULL DEFAULT 'general',
  `title` VARCHAR(191) NOT NULL,
  `body` TEXT NOT NULL,
  `status` ENUM('active','archived') NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_note_operator_idx` (`operator_profile_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pone_print_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `operator_profile_id` BIGINT UNSIGNED NULL,
  `shift_id` BIGINT UNSIGNED NULL,
  `printable_type` VARCHAR(80) NOT NULL,
  `printable_id` BIGINT UNSIGNED NOT NULL,
  `template_name` VARCHAR(100) NOT NULL,
  `paper_size` VARCHAR(20) NOT NULL DEFAULT '80mm',
  `copy_type` VARCHAR(30) NOT NULL DEFAULT 'original',
  `printed_at` TIMESTAMP NOT NULL,
  `printed_by` INT UNSIGNED NULL,
  `ip_address` VARCHAR(64) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_print_log_entity_idx` (`printable_type`,`printable_id`,`printed_at`),
  KEY `pone_print_log_business_idx` (`business_id`,`printed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Support retired integration links produced when edited detail lines are superseded.
SET @pone_link_status_sql = IF(
  EXISTS(
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pone_integration_links'
      AND column_name = 'status'
  ),
  'ALTER TABLE `pone_integration_links` MODIFY `status` ENUM(''pending'',''synced'',''failed'',''retired'') NOT NULL DEFAULT ''pending''',
  'SELECT 1'
);
PREPARE pone_link_status_stmt FROM @pone_link_status_sql;
EXECUTE pone_link_status_stmt;
DEALLOCATE PREPARE pone_link_status_stmt;

-- Idempotent permission registration. Skipped when the host permissions table is absent.
SET @pone_permission_sql = IF(
  EXISTS(
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'permissions'
  ),
  'INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
   SELECT p.name, ''web'', NOW(), NOW()
   FROM (
     SELECT ''pumper_dashboard_new.access'' AS name
     UNION ALL SELECT ''pumper_dashboard_new.dashboard.view''
     UNION ALL SELECT ''pumper_dashboard_new.operators.view''
     UNION ALL SELECT ''pumper_dashboard_new.operators.manage''
     UNION ALL SELECT ''pumper_dashboard_new.shifts.view''
     UNION ALL SELECT ''pumper_dashboard_new.shifts.manage''
     UNION ALL SELECT ''pumper_dashboard_new.assignments.manage''
     UNION ALL SELECT ''pumper_dashboard_new.collections.view''
     UNION ALL SELECT ''pumper_dashboard_new.collections.manage''
     UNION ALL SELECT ''pumper_dashboard_new.reconciliation.view''
     UNION ALL SELECT ''pumper_dashboard_new.reconciliation.manage''
     UNION ALL SELECT ''pumper_dashboard_new.ledger.view''
     UNION ALL SELECT ''pumper_dashboard_new.documents.view''
     UNION ALL SELECT ''pumper_dashboard_new.documents.manage''
     UNION ALL SELECT ''pumper_dashboard_new.login_attempts.view''
     UNION ALL SELECT ''pumper_dashboard_new.login_attempts.manage''
     UNION ALL SELECT ''pumper_dashboard_new.print_logs.view''
     UNION ALL SELECT ''pumper_dashboard_new.reports.view''
     UNION ALL SELECT ''pumper_dashboard_new.reports.export''
     UNION ALL SELECT ''pumper_dashboard_new.reports.print''
     UNION ALL SELECT ''pumper_dashboard_new.reports.shifts''
     UNION ALL SELECT ''pumper_dashboard_new.reports.payments''
     UNION ALL SELECT ''pumper_dashboard_new.reports.meters''
     UNION ALL SELECT ''pumper_dashboard_new.reports.other_sales''
     UNION ALL SELECT ''pumper_dashboard_new.reports.unloads''
     UNION ALL SELECT ''pumper_dashboard_new.reports.day_entries''
     UNION ALL SELECT ''pumper_dashboard_new.reports.collections''
     UNION ALL SELECT ''pumper_dashboard_new.reports.ledger''
     UNION ALL SELECT ''pumper_dashboard_new.reports.shortages''
     UNION ALL SELECT ''pumper_dashboard_new.reports.commissions''
     UNION ALL SELECT ''pumper_dashboard_new.reports.print_logs''
     UNION ALL SELECT ''pumper_dashboard_new.reports.audit''
     UNION ALL SELECT ''pumper_dashboard_new.integration.view''
     UNION ALL SELECT ''pumper_dashboard_new.integration.manage''
     UNION ALL SELECT ''pumper_dashboard_new.settings.manage''
     UNION ALL SELECT ''pumper_dashboard_new.operator.use''
   ) p
   WHERE NOT EXISTS (
     SELECT 1 FROM `permissions` existing
     WHERE existing.`name` = p.name AND existing.`guard_name` = ''web''
   )',
  'SELECT ''Pumper Dashboard-New: permissions table not found; schema installation completed without permission rows.'' AS message'
);
PREPARE pone_permission_stmt FROM @pone_permission_sql;
EXECUTE pone_permission_stmt;
DEALLOCATE PREPARE pone_permission_stmt;

SELECT 'Pumper Dashboard-New 2.0.0 full schema is ready.' AS message;
