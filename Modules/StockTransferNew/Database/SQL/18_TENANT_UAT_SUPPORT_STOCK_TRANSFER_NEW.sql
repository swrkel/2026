-- StockTransferNew STN_018 - Tenant UAT support SQL
-- Run once per tenant database.

CREATE TABLE IF NOT EXISTS stn_uat_signoffs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    area VARCHAR(191) NOT NULL,
    signed_by VARCHAR(191) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    remarks TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stn_uat_signoffs_status_idx (status),
    INDEX stn_uat_signoffs_area_idx (area)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.uat', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.uat');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.uat_signoff', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.uat_signoff');
