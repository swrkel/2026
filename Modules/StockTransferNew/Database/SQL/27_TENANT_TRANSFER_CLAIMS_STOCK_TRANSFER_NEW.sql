-- StockTransferNew STN_027 - Tenant SQL: Transfer discrepancy claims and recovery tracking
-- Run this on every tenant database where StockTransfer-New is enabled.

CREATE TABLE IF NOT EXISTS stock_transfer_new_claims (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    claim_no VARCHAR(50) NOT NULL,
    transfer_id BIGINT UNSIGNED NOT NULL,
    transfer_no VARCHAR(80) NULL,
    claim_date DATE NOT NULL,
    claim_type VARCHAR(40) NOT NULL DEFAULT 'shortage',
    claim_status VARCHAR(30) NOT NULL DEFAULT 'draft',
    from_business_id BIGINT UNSIGNED NULL,
    to_business_id BIGINT UNSIGNED NULL,
    from_location_id BIGINT UNSIGNED NULL,
    to_location_id BIGINT UNSIGNED NULL,
    from_store_id BIGINT UNSIGNED NULL,
    to_store_id BIGINT UNSIGNED NULL,
    claimed_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    claimed_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    recovered_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    recovered_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    writeoff_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    responsible_party VARCHAR(80) NULL,
    driver_name VARCHAR(120) NULL,
    vehicle_no VARCHAR(80) NULL,
    remarks TEXT NULL,
    submitted_by BIGINT UNSIGNED NULL,
    submitted_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    closed_by BIGINT UNSIGNED NULL,
    closed_at TIMESTAMP NULL,
    cancelled_by BIGINT UNSIGNED NULL,
    cancelled_at TIMESTAMP NULL,
    cancel_reason TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY stn_claim_no_unique (claim_no),
    KEY stn_claim_transfer_idx (transfer_id),
    KEY stn_claim_status_idx (claim_status),
    KEY stn_claim_date_idx (claim_date),
    KEY stn_claim_business_idx (from_business_id, to_business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_transfer_new_claim_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    claim_id BIGINT UNSIGNED NOT NULL,
    transfer_line_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NULL,
    variation_id BIGINT UNSIGNED NULL,
    sku VARCHAR(120) NULL,
    product_name VARCHAR(255) NULL,
    batch_no VARCHAR(120) NULL,
    expiry_date DATE NULL,
    transferred_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    received_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    shortage_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    damage_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    excess_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    unit_cost DECIMAL(22,4) NOT NULL DEFAULT 0,
    claim_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    recovered_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    recovered_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    remarks TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY stn_claim_lines_claim_idx (claim_id),
    KEY stn_claim_lines_product_idx (product_id, variation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_transfer_new_claim_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    claim_id BIGINT UNSIGNED NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    action_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    reference_no VARCHAR(120) NULL,
    remarks TEXT NULL,
    action_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY stn_claim_actions_claim_idx (claim_id),
    KEY stn_claim_actions_type_idx (action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.claims.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.claims.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.claims.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.claims.create');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.claims.approve', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.claims.approve');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.claims.close', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.claims.close');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.claims.cancel', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.claims.cancel');
