-- StockTransferNew STN_010 Tenant SQL - Scheduling and replenishment

CREATE TABLE IF NOT EXISTS stn_transfer_schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    from_location_id BIGINT UNSIGNED NULL,
    from_store_id BIGINT UNSIGNED NULL,
    to_location_id BIGINT UNSIGNED NULL,
    to_store_id BIGINT UNSIGNED NULL,
    schedule_name VARCHAR(191) NOT NULL,
    schedule_type ENUM('one_time','daily','weekly','monthly') NOT NULL DEFAULT 'one_time',
    next_run_date DATE NOT NULL,
    run_time TIME NULL,
    status ENUM('active','paused','completed','cancelled') NOT NULL DEFAULT 'active',
    auto_create_draft TINYINT(1) NOT NULL DEFAULT 0,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stn_ts_business_status_idx (business_id, status),
    INDEX stn_ts_next_run_idx (business_id, next_run_date)
);

CREATE TABLE IF NOT EXISTS stn_transfer_schedule_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variation_id BIGINT UNSIGNED NULL,
    unit_id BIGINT UNSIGNED NULL,
    qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    remarks VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stn_tsi_schedule_idx (schedule_id),
    INDEX stn_tsi_product_idx (product_id, variation_id)
);

CREATE TABLE IF NOT EXISTS stn_min_stock_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    store_id BIGINT UNSIGNED NULL,
    source_location_id BIGINT UNSIGNED NULL,
    source_store_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variation_id BIGINT UNSIGNED NULL,
    min_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    reorder_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    preferred_transfer_qty DECIMAL(22,4) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY stn_msr_unique_rule (business_id, location_id, store_id, product_id, variation_id),
    INDEX stn_msr_business_active_idx (business_id, is_active)
);

CREATE TABLE IF NOT EXISTS stn_replenishment_proposals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    rule_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variation_id BIGINT UNSIGNED NULL,
    from_location_id BIGINT UNSIGNED NULL,
    from_store_id BIGINT UNSIGNED NULL,
    to_location_id BIGINT UNSIGNED NOT NULL,
    to_store_id BIGINT UNSIGNED NULL,
    current_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    min_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    proposed_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    status ENUM('open','converted','ignored','cancelled') NOT NULL DEFAULT 'open',
    transfer_id BIGINT UNSIGNED NULL,
    generated_by BIGINT UNSIGNED NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stn_rp_business_status_idx (business_id, status),
    INDEX stn_rp_product_idx (product_id, variation_id)
);

CREATE TABLE IF NOT EXISTS stn_schedule_execution_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    schedule_id BIGINT UNSIGNED NULL,
    transfer_id BIGINT UNSIGNED NULL,
    run_date DATE NOT NULL,
    run_status ENUM('success','failed','skipped') NOT NULL DEFAULT 'success',
    message TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    INDEX stn_sel_schedule_idx (schedule_id),
    INDEX stn_sel_business_date_idx (business_id, run_date)
);

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.schedule', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stocktransfernew.schedule');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.replenishment', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stocktransfernew.replenishment');
