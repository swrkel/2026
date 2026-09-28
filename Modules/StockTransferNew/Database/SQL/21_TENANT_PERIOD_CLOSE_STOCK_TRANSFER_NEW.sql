-- StockTransferNew STN_021 - Tenant DB SQL
-- Run in each tenant database.

CREATE TABLE IF NOT EXISTS stn_period_closes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NULL,
    store_id BIGINT UNSIGNED NULL,
    period_year INT NOT NULL,
    period_month TINYINT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    total_transfers INT NOT NULL DEFAULT 0,
    pending_transfers INT NOT NULL DEFAULT 0,
    in_transit_transfers INT NOT NULL DEFAULT 0,
    variance_transfers INT NOT NULL DEFAULT 0,
    locked_by BIGINT UNSIGNED NULL,
    locked_at TIMESTAMP NULL,
    reopened_by BIGINT UNSIGNED NULL,
    reopened_at TIMESTAMP NULL,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY stn_period_unique_scope (business_id, location_id, store_id, period_year, period_month),
    INDEX stn_period_status_idx (business_id, status, period_year, period_month)
);

CREATE TABLE IF NOT EXISTS stn_period_close_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    period_close_id BIGINT UNSIGNED NOT NULL,
    transfer_id BIGINT UNSIGNED NULL,
    transfer_no VARCHAR(100) NULL,
    issue_type VARCHAR(60) NOT NULL,
    issue_message TEXT NULL,
    action_required VARCHAR(120) NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'warning',
    is_resolved TINYINT(1) NOT NULL DEFAULT 0,
    resolved_by BIGINT UNSIGNED NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stn_period_line_parent_idx (period_close_id),
    INDEX stn_period_line_issue_idx (issue_type, severity, is_resolved)
);

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.period_close.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='stocktransfernew.period_close.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.period_close.lock', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='stocktransfernew.period_close.lock');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.period_close.reopen', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='stocktransfernew.period_close.reopen');
