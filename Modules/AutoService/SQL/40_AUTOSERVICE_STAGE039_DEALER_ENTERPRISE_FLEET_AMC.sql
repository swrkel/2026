-- AutoService Stage 039 - Dealer Enterprise, Fleet, AMC, Corporate Pricing, Driver Management
-- Run this SQL on each tenant database that uses the Auto Service module.
-- All changes are additive and business/location-safe.

CREATE TABLE IF NOT EXISTS auto_service_fleet_customers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    fleet_name VARCHAR(191) NOT NULL,
    contract_no VARCHAR(100) NULL,
    credit_limit DECIMAL(22,4) NOT NULL DEFAULT 0,
    billing_cycle VARCHAR(50) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    note TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_as_fleet_business (business_id),
    INDEX idx_as_fleet_location (location_id),
    INDEX idx_as_fleet_contact (contact_id),
    INDEX idx_as_fleet_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_fleet_contracts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    fleet_customer_id BIGINT UNSIGNED NOT NULL,
    contract_no VARCHAR(100) NOT NULL,
    contract_type VARCHAR(100) NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    vehicle_limit INT NULL,
    monthly_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    status VARCHAR(50) NOT NULL DEFAULT 'draft',
    note TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_as_fleet_contract_business (business_id),
    INDEX idx_as_fleet_contract_location (location_id),
    INDEX idx_as_fleet_contract_customer (fleet_customer_id),
    INDEX idx_as_fleet_contract_status (status),
    INDEX idx_as_fleet_contract_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_fleet_drivers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    fleet_customer_id BIGINT UNSIGNED NOT NULL,
    assigned_vehicle_id BIGINT UNSIGNED NULL,
    driver_name VARCHAR(191) NOT NULL,
    mobile VARCHAR(50) NULL,
    nic_no VARCHAR(100) NULL,
    license_no VARCHAR(100) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_as_fleet_driver_business (business_id),
    INDEX idx_as_fleet_driver_location (location_id),
    INDEX idx_as_fleet_driver_customer (fleet_customer_id),
    INDEX idx_as_fleet_driver_vehicle (assigned_vehicle_id),
    INDEX idx_as_fleet_driver_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_corporate_pricing (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    fleet_customer_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    item_type VARCHAR(50) NOT NULL DEFAULT 'part',
    item_id BIGINT UNSIGNED NULL,
    item_code VARCHAR(100) NULL,
    item_name VARCHAR(191) NOT NULL,
    normal_price DECIMAL(22,4) NOT NULL DEFAULT 0,
    special_price DECIMAL(22,4) NOT NULL DEFAULT 0,
    discount_type VARCHAR(20) NULL,
    discount_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    effective_from DATE NULL,
    effective_to DATE NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_as_corp_price_business (business_id),
    INDEX idx_as_corp_price_location (location_id),
    INDEX idx_as_corp_price_fleet (fleet_customer_id),
    INDEX idx_as_corp_price_contact (contact_id),
    INDEX idx_as_corp_price_item (item_type, item_id),
    INDEX idx_as_corp_price_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_dealer_trade_ins (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    registration_no VARCHAR(100) NULL,
    make VARCHAR(100) NULL,
    model VARCHAR(100) NULL,
    year VARCHAR(20) NULL,
    odometer DECIMAL(22,4) NOT NULL DEFAULT 0,
    appraisal_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    agreed_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    status VARCHAR(50) NOT NULL DEFAULT 'draft',
    note TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_as_tradein_business (business_id),
    INDEX idx_as_tradein_location (location_id),
    INDEX idx_as_tradein_contact (contact_id),
    INDEX idx_as_tradein_vehicle (vehicle_id),
    INDEX idx_as_tradein_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
DROP PROCEDURE IF EXISTS autoservice_stage039_add_column$$
CREATE PROCEDURE autoservice_stage039_add_column(IN p_table VARCHAR(191), IN p_column VARCHAR(191), IN p_sql TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
    ) THEN
        SET @ddl = p_sql;
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS autoservice_stage039_add_index$$
CREATE PROCEDURE autoservice_stage039_add_index(IN p_table VARCHAR(191), IN p_index VARCHAR(191), IN p_sql TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index
    ) THEN
        SET @ddl = p_sql;
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL autoservice_stage039_add_column('auto_service_jobs', 'fleet_customer_id', 'ALTER TABLE auto_service_jobs ADD COLUMN fleet_customer_id BIGINT UNSIGNED NULL AFTER contact_id');
CALL autoservice_stage039_add_column('auto_service_jobs', 'fleet_contract_id', 'ALTER TABLE auto_service_jobs ADD COLUMN fleet_contract_id BIGINT UNSIGNED NULL AFTER fleet_customer_id');
CALL autoservice_stage039_add_column('auto_service_jobs', 'driver_id', 'ALTER TABLE auto_service_jobs ADD COLUMN driver_id BIGINT UNSIGNED NULL AFTER fleet_contract_id');
CALL autoservice_stage039_add_column('auto_service_jobs', 'corporate_pricing_applied', 'ALTER TABLE auto_service_jobs ADD COLUMN corporate_pricing_applied TINYINT(1) NOT NULL DEFAULT 0 AFTER driver_id');

CALL autoservice_stage039_add_index('auto_service_jobs', 'idx_as_jobs_fleet_customer', 'CREATE INDEX idx_as_jobs_fleet_customer ON auto_service_jobs (fleet_customer_id)');
CALL autoservice_stage039_add_index('auto_service_jobs', 'idx_as_jobs_fleet_contract', 'CREATE INDEX idx_as_jobs_fleet_contract ON auto_service_jobs (fleet_contract_id)');
CALL autoservice_stage039_add_index('auto_service_jobs', 'idx_as_jobs_driver', 'CREATE INDEX idx_as_jobs_driver ON auto_service_jobs (driver_id)');

DROP PROCEDURE IF EXISTS autoservice_stage039_add_column;
DROP PROCEDURE IF EXISTS autoservice_stage039_add_index;

-- Permission keys for permission seeders/importers. If your permission table is named differently,
-- add the following permission keys through Super Admin permission management:
-- autoservice.dealer_enterprise.view
-- autoservice.dealer_enterprise.manage
-- autoservice.fleet_customer.view
-- autoservice.fleet_customer.manage
-- autoservice.fleet_contract.view
-- autoservice.fleet_contract.manage
-- autoservice.corporate_pricing.view
-- autoservice.corporate_pricing.manage
