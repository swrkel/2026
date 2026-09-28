-- StockTransfer-New STN_013 - Tenant DB Performance Optimization
-- Run on each tenant database.

CREATE TABLE IF NOT EXISTS stn_performance_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    metric_key VARCHAR(150) NOT NULL,
    metric_value DECIMAL(20,6) DEFAULT 0,
    metric_date DATE NULL,
    meta JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stn_perf_business_metric_idx (business_id, metric_key),
    INDEX stn_perf_date_idx (metric_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stn_export_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    report_type VARCHAR(100) NOT NULL,
    filters JSON NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'queued',
    file_path VARCHAR(255) NULL,
    requested_by BIGINT UNSIGNED NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stn_export_business_status_idx (business_id, status),
    INDEX stn_export_report_idx (report_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Safe index additions. If your MySQL version does not support duplicate index checks,
-- run once only or check existing indexes first.
ALTER TABLE stn_transfers ADD INDEX stn_transfers_perf_status_idx (business_id, status, transfer_date);
ALTER TABLE stn_transfers ADD INDEX stn_transfers_perf_locations_idx (business_id, from_location_id, to_location_id);
ALTER TABLE stn_transfer_lines ADD INDEX stn_lines_perf_product_idx (transfer_id, product_id);
ALTER TABLE stn_transfer_lines ADD INDEX stn_lines_perf_stores_idx (from_store_id, to_store_id);
ALTER TABLE stn_transfer_movements ADD INDEX stn_movements_perf_idx (business_id, product_id, movement_type, movement_date);
ALTER TABLE stn_approval_steps ADD INDEX stn_approval_perf_idx (transfer_id, status, approval_level);
ALTER TABLE stn_scan_sessions ADD INDEX stn_scan_sessions_perf_idx (business_id, transfer_id, scan_type, status);
ALTER TABLE stn_scan_lines ADD INDEX stn_scan_lines_perf_idx (session_id, product_id, scanned_code);
ALTER TABLE stn_audit_logs ADD INDEX stn_audit_perf_idx (business_id, transfer_id, event, created_at);

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.performance', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='stocktransfernew.performance');
