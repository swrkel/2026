-- StockTransferNew STN_028 - Tenant SQL: Stock reservation control and release monitoring
-- Run this on every tenant database where StockTransfer-New is enabled.

CREATE TABLE IF NOT EXISTS stock_transfer_new_reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_no VARCHAR(60) NOT NULL,
    transfer_id BIGINT UNSIGNED NOT NULL,
    transfer_line_id BIGINT UNSIGNED NULL,
    transfer_no VARCHAR(80) NULL,
    reservation_date DATE NOT NULL,
    reservation_status VARCHAR(30) NOT NULL DEFAULT 'active',
    from_business_id BIGINT UNSIGNED NULL,
    from_location_id BIGINT UNSIGNED NULL,
    from_store_id BIGINT UNSIGNED NULL,
    to_business_id BIGINT UNSIGNED NULL,
    to_location_id BIGINT UNSIGNED NULL,
    to_store_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NULL,
    variation_id BIGINT UNSIGNED NULL,
    sku VARCHAR(120) NULL,
    product_name VARCHAR(255) NULL,
    batch_no VARCHAR(120) NULL,
    expiry_date DATE NULL,
    reserved_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    dispatched_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    released_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    pending_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    unit_cost DECIMAL(22,4) NOT NULL DEFAULT 0,
    reserved_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    expires_at TIMESTAMP NULL,
    released_at TIMESTAMP NULL,
    release_reason TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY stn_reservation_no_unique (reservation_no),
    KEY stn_reservation_transfer_idx (transfer_id, transfer_line_id),
    KEY stn_reservation_status_idx (reservation_status),
    KEY stn_reservation_scope_idx (from_business_id, from_location_id, from_store_id),
    KEY stn_reservation_product_idx (product_id, variation_id),
    KEY stn_reservation_expiry_idx (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_transfer_new_reservation_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id BIGINT UNSIGNED NOT NULL,
    transfer_id BIGINT UNSIGNED NULL,
    action_type VARCHAR(50) NOT NULL,
    action_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    action_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    reference_no VARCHAR(120) NULL,
    remarks TEXT NULL,
    action_by BIGINT UNSIGNED NULL,
    action_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY stn_reservation_action_reservation_idx (reservation_id),
    KEY stn_reservation_action_transfer_idx (transfer_id),
    KEY stn_reservation_action_type_idx (action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.reservations.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.reservations.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.reservations.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.reservations.create');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.reservations.release', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.reservations.release');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.reservations.export', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.reservations.export');
