-- StockTransferNew STN_022 - Tenant DB SQL
-- Run in each tenant database after STN_021.

CREATE TABLE IF NOT EXISTS stn_archive_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NULL,
    store_id BIGINT UNSIGNED NULL,
    archive_year INT NOT NULL,
    archive_until DATE NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'preview',
    total_completed_transfers INT NOT NULL DEFAULT 0,
    total_lines INT NOT NULL DEFAULT 0,
    total_qty DECIMAL(22,6) NOT NULL DEFAULT 0,
    total_value DECIMAL(22,6) NOT NULL DEFAULT 0,
    export_file VARCHAR(255) NULL,
    checksum VARCHAR(128) NULL,
    preview_by BIGINT UNSIGNED NULL,
    preview_at TIMESTAMP NULL,
    archived_by BIGINT UNSIGNED NULL,
    archived_at TIMESTAMP NULL,
    cancelled_by BIGINT UNSIGNED NULL,
    cancelled_at TIMESTAMP NULL,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stn_archive_scope_idx (business_id, location_id, store_id, archive_year, status),
    INDEX stn_archive_until_idx (business_id, archive_until, status)
);

CREATE TABLE IF NOT EXISTS stn_archive_run_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    archive_run_id BIGINT UNSIGNED NOT NULL,
    transfer_id BIGINT UNSIGNED NULL,
    transfer_no VARCHAR(100) NULL,
    transfer_date DATE NULL,
    from_location_id BIGINT UNSIGNED NULL,
    from_store_id BIGINT UNSIGNED NULL,
    to_location_id BIGINT UNSIGNED NULL,
    to_store_id BIGINT UNSIGNED NULL,
    status VARCHAR(40) NULL,
    line_count INT NOT NULL DEFAULT 0,
    total_qty DECIMAL(22,6) NOT NULL DEFAULT 0,
    total_value DECIMAL(22,6) NOT NULL DEFAULT 0,
    archive_decision VARCHAR(30) NOT NULL DEFAULT 'eligible',
    warning_message TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stn_archive_line_run_idx (archive_run_id),
    INDEX stn_archive_line_transfer_idx (transfer_id),
    INDEX stn_archive_line_decision_idx (archive_decision)
);

CREATE TABLE IF NOT EXISTS stn_archive_restore_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    archive_run_id BIGINT UNSIGNED NULL,
    transfer_id BIGINT UNSIGNED NULL,
    transfer_no VARCHAR(100) NULL,
    reason TEXT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'requested',
    requested_by BIGINT UNSIGNED NULL,
    requested_at TIMESTAMP NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    review_remarks TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stn_restore_business_status_idx (business_id, status),
    INDEX stn_restore_transfer_idx (transfer_id, transfer_no)
);

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.archive.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='stocktransfernew.archive.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.archive.preview', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='stocktransfernew.archive.preview');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.archive.execute', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='stocktransfernew.archive.execute');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.archive.restore_request', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='stocktransfernew.archive.restore_request');
