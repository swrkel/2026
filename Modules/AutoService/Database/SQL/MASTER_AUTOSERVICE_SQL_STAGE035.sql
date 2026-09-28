-- AutoService Master SQL up to Stage 035
-- Includes Stage 035 additions. Apply incremental SQL files in stage order.

-- AutoService Stage 035: Advanced Vehicle History
-- Run on each tenant database. No database name is specified intentionally.

ALTER TABLE auto_service_vehicles
    ADD COLUMN IF NOT EXISTS fuel_type VARCHAR(100) NULL AFTER year,
    ADD COLUMN IF NOT EXISTS transmission VARCHAR(100) NULL AFTER fuel_type,
    ADD COLUMN IF NOT EXISTS vehicle_colour VARCHAR(100) NULL AFTER transmission,
    ADD COLUMN IF NOT EXISTS ownership_status VARCHAR(100) NULL AFTER contact_id,
    ADD COLUMN IF NOT EXISTS purchase_date DATE NULL AFTER ownership_status,
    ADD COLUMN IF NOT EXISTS lifetime_service_cost DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER next_service_odometer;

ALTER TABLE auto_service_part_movements
    ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER discount_amount,
    ADD COLUMN IF NOT EXISTS warranty_days INT NULL AFTER tax_amount,
    ADD COLUMN IF NOT EXISTS supplier_id BIGINT UNSIGNED NULL AFTER product_id;

CREATE INDEX IF NOT EXISTS auto_service_part_movements_supplier_id_index ON auto_service_part_movements (supplier_id);

CREATE TABLE IF NOT EXISTS auto_service_vehicle_history_snapshots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    job_id BIGINT UNSIGNED NULL,
    snapshot_date DATE NULL,
    odometer INT UNSIGNED NULL,
    job_total DECIMAL(22,4) NOT NULL DEFAULT 0,
    parts_total DECIMAL(22,4) NOT NULL DEFAULT 0,
    labour_total DECIMAL(22,4) NOT NULL DEFAULT 0,
    health_status VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY as_vehicle_history_vehicle_job_unique (vehicle_id, job_id),
    KEY as_vehicle_history_business_idx (business_id),
    KEY as_vehicle_history_location_idx (location_id),
    KEY as_vehicle_history_vehicle_idx (vehicle_id),
    KEY as_vehicle_history_job_idx (job_id),
    KEY as_vehicle_history_date_idx (snapshot_date),
    KEY as_vehicle_history_status_idx (health_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO auto_service_vehicle_history_snapshots
    (business_id, location_id, vehicle_id, job_id, snapshot_date, odometer, job_total, health_status, created_at, updated_at)
SELECT business_id, location_id, vehicle_id, id, job_date, odometer, total_amount, status, NOW(), NOW()
FROM auto_service_jobs
WHERE vehicle_id IS NOT NULL;

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('autoservice.advanced_vehicle_history.view', 'web', NOW(), NOW()),
('autoservice.advanced_vehicle_history.export', 'web', NOW(), NOW());
