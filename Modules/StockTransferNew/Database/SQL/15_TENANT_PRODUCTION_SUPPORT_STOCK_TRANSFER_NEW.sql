-- StockTransferNew STN_015 Tenant SQL - Production Support
-- Run on each tenant database where StockTransferNew is enabled.

CREATE TABLE IF NOT EXISTS stn_support_diagnostic_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    store_id BIGINT UNSIGNED NULL,
    run_type VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'completed',
    summary LONGTEXT NULL,
    checked_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stn_support_diag_business_idx (business_id),
    INDEX stn_support_diag_type_idx (run_type),
    INDEX stn_support_diag_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stn_support_repair_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    repair_key VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'completed',
    message TEXT NULL,
    executed_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stn_support_repair_business_idx (business_id),
    INDEX stn_support_repair_key_idx (repair_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.support', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stocktransfernew.support');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.support.repair', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stocktransfernew.support.repair');
