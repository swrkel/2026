-- StockTransferNew STN_029 - Tenant SQL: logistics, multi-vehicle dispatch and transfer consolidation
-- Run this on every tenant database where StockTransfer-New is enabled.

CREATE TABLE IF NOT EXISTS stock_transfer_new_routes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    route_code VARCHAR(60) NOT NULL,
    route_name VARCHAR(160) NOT NULL,
    from_business_id BIGINT UNSIGNED NULL,
    from_location_id BIGINT UNSIGNED NULL,
    from_store_id BIGINT UNSIGNED NULL,
    to_business_id BIGINT UNSIGNED NULL,
    to_location_id BIGINT UNSIGNED NULL,
    to_store_id BIGINT UNSIGNED NULL,
    estimated_distance DECIMAL(18,4) NOT NULL DEFAULT 0,
    estimated_minutes INT UNSIGNED NOT NULL DEFAULT 0,
    route_status VARCHAR(30) NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY stn_routes_code_unique (route_code),
    KEY stn_routes_scope_idx (from_business_id, from_location_id, from_store_id, to_business_id, to_location_id, to_store_id),
    KEY stn_routes_status_idx (route_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_transfer_new_vehicle_loads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    load_no VARCHAR(80) NOT NULL,
    transfer_id BIGINT UNSIGNED NOT NULL,
    transfer_no VARCHAR(80) NULL,
    route_id BIGINT UNSIGNED NULL,
    vehicle_no VARCHAR(80) NULL,
    driver_name VARCHAR(160) NULL,
    driver_mobile VARCHAR(60) NULL,
    assistant_name VARCHAR(160) NULL,
    planned_dispatch_at DATETIME NULL,
    actual_dispatch_at DATETIME NULL,
    eta_at DATETIME NULL,
    received_at DATETIME NULL,
    vehicle_capacity_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    loaded_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    capacity_variance_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    load_status VARCHAR(30) NOT NULL DEFAULT 'planned',
    gps_reference VARCHAR(255) NULL,
    dispatch_checklist_json JSON NULL,
    receiving_checklist_json JSON NULL,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY stn_vehicle_load_no_unique (load_no),
    KEY stn_vehicle_load_transfer_idx (transfer_id),
    KEY stn_vehicle_load_route_idx (route_id),
    KEY stn_vehicle_load_status_idx (load_status),
    KEY stn_vehicle_load_eta_idx (eta_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_transfer_new_vehicle_load_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_load_id BIGINT UNSIGNED NOT NULL,
    transfer_id BIGINT UNSIGNED NOT NULL,
    transfer_line_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NULL,
    variation_id BIGINT UNSIGNED NULL,
    sku VARCHAR(120) NULL,
    product_name VARCHAR(255) NULL,
    batch_no VARCHAR(120) NULL,
    expiry_date DATE NULL,
    planned_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    loaded_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    received_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    damaged_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    shortage_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    remarks TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY stn_vehicle_load_line_load_idx (vehicle_load_id),
    KEY stn_vehicle_load_line_transfer_idx (transfer_id, transfer_line_id),
    KEY stn_vehicle_load_line_product_idx (product_id, variation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_transfer_new_consolidations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consolidation_no VARCHAR(80) NOT NULL,
    consolidation_date DATE NOT NULL,
    from_business_id BIGINT UNSIGNED NULL,
    from_location_id BIGINT UNSIGNED NULL,
    from_store_id BIGINT UNSIGNED NULL,
    to_business_id BIGINT UNSIGNED NULL,
    to_location_id BIGINT UNSIGNED NULL,
    to_store_id BIGINT UNSIGNED NULL,
    transfer_count INT UNSIGNED NOT NULL DEFAULT 0,
    total_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    total_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    consolidation_status VARCHAR(30) NOT NULL DEFAULT 'draft',
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY stn_consolidation_no_unique (consolidation_no),
    KEY stn_consolidation_scope_idx (from_business_id, from_location_id, from_store_id, to_business_id, to_location_id, to_store_id),
    KEY stn_consolidation_status_idx (consolidation_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_transfer_new_consolidation_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consolidation_id BIGINT UNSIGNED NOT NULL,
    transfer_id BIGINT UNSIGNED NOT NULL,
    transfer_no VARCHAR(80) NULL,
    transfer_status VARCHAR(30) NULL,
    transfer_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    transfer_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY stn_consolidation_transfer_unique (consolidation_id, transfer_id),
    KEY stn_consolidation_line_transfer_idx (transfer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.logistics.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.logistics.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.logistics.manage_loads', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.logistics.manage_loads');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.logistics.consolidate', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.logistics.consolidate');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.logistics.export', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.logistics.export');
