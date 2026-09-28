-- STN_011 - TENANT SQL - Warehouse / Mobile Scan Operations
-- Run in each tenant database that will use StockTransferNew.

CREATE TABLE IF NOT EXISTS stock_transfer_new_scan_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NULL,
    store_id INT UNSIGNED NULL,
    transfer_id BIGINT UNSIGNED NULL,
    session_no VARCHAR(50) NOT NULL,
    scan_type ENUM('dispatch','receive','audit') NOT NULL DEFAULT 'dispatch',
    device_code VARCHAR(100) NULL,
    operator_id BIGINT UNSIGNED NULL,
    status ENUM('open','submitted','cancelled') NOT NULL DEFAULT 'open',
    started_at TIMESTAMP NULL,
    submitted_at TIMESTAMP NULL,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY stn_scan_sessions_session_no_unique (business_id, session_no),
    KEY stn_scan_sessions_transfer_idx (business_id, transfer_id, scan_type, status),
    KEY stn_scan_sessions_location_store_idx (business_id, location_id, store_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_transfer_new_scan_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    scan_session_id BIGINT UNSIGNED NOT NULL,
    transfer_id BIGINT UNSIGNED NULL,
    transfer_line_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NULL,
    sku VARCHAR(191) NULL,
    barcode VARCHAR(191) NULL,
    batch_no VARCHAR(100) NULL,
    expiry_date DATE NULL,
    expected_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    scanned_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    variance_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    scan_count INT NOT NULL DEFAULT 0,
    last_scanned_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY stn_scan_lines_session_idx (business_id, scan_session_id),
    KEY stn_scan_lines_transfer_line_idx (business_id, transfer_id, transfer_line_id),
    KEY stn_scan_lines_product_idx (business_id, product_id),
    KEY stn_scan_lines_barcode_idx (business_id, barcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE stock_transfer_new_transfers
    ADD COLUMN IF NOT EXISTS qr_token VARCHAR(100) NULL AFTER transfer_no,
    ADD COLUMN IF NOT EXISTS mobile_locked_at TIMESTAMP NULL AFTER received_at,
    ADD COLUMN IF NOT EXISTS mobile_locked_by BIGINT UNSIGNED NULL AFTER mobile_locked_at;

CREATE INDEX IF NOT EXISTS stn_transfers_qr_token_idx ON stock_transfer_new_transfers (business_id, qr_token);

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('stocktransfernew.warehouse_mobile', 'web', NOW(), NOW()),
('stocktransfernew.scan_dispatch', 'web', NOW(), NOW()),
('stocktransfernew.scan_receive', 'web', NOW(), NOW()),
('stocktransfernew.qr_lookup', 'web', NOW(), NOW());
