-- AutoService Stage 038 - Business Intelligence & Profitability
-- Run on each tenant database. Global/tenant-safe SQL; no database name is hardcoded.

CREATE TABLE IF NOT EXISTS auto_service_business_intelligence_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NULL,
    location_id INT UNSIGNED NULL,
    snapshot_date DATE NOT NULL,
    total_jobs INT NOT NULL DEFAULT 0,
    total_invoices INT NOT NULL DEFAULT 0,
    gross_revenue DECIMAL(22,4) NOT NULL DEFAULT 0,
    parts_revenue DECIMAL(22,4) NOT NULL DEFAULT 0,
    labour_revenue DECIMAL(22,4) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    estimated_cost DECIMAL(22,4) NOT NULL DEFAULT 0,
    estimated_profit DECIMAL(22,4) NOT NULL DEFAULT 0,
    warranty_cost DECIMAL(22,4) NOT NULL DEFAULT 0,
    repeat_customers INT NOT NULL DEFAULT 0,
    retention_percent DECIMAL(8,2) NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_as_bi_snapshot_business_date (business_id, snapshot_date),
    INDEX idx_as_bi_snapshot_location_date (location_id, snapshot_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_business_intelligence_exports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NULL,
    location_id INT UNSIGNED NULL,
    export_type VARCHAR(80) NOT NULL DEFAULT 'job_profitability',
    date_from DATE NULL,
    date_to DATE NULL,
    filters_json JSON NULL,
    exported_by BIGINT UNSIGNED NULL,
    exported_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_as_bi_export_business_date (business_id, exported_at),
    INDEX idx_as_bi_export_type (export_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.business_intelligence.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.business_intelligence.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.business_intelligence.export', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.business_intelligence.export');
