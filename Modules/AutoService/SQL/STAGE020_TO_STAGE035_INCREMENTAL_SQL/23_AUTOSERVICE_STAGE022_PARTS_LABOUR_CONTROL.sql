/*
 AutoService Stage 022 - Parts & Labour Control
 Tenant database SQL only. Execute against each tenant database that uses Auto Service.
 This script is safe to run more than once where MySQL supports IF NOT EXISTS.
*/

CREATE TABLE IF NOT EXISTS auto_service_part_movements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    movement_type VARCHAR(30) NOT NULL DEFAULT 'reserved',
    description VARCHAR(255) NULL,
    quantity DECIMAL(20,4) NOT NULL DEFAULT 0,
    unit_cost DECIMAL(20,4) NOT NULL DEFAULT 0,
    line_total DECIMAL(20,4) NOT NULL DEFAULT 0,
    movement_date DATE NULL,
    reference_no VARCHAR(100) NULL,
    note TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_aspm_business_job (business_id, job_id),
    INDEX idx_aspm_job_type (job_id, movement_type),
    INDEX idx_aspm_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE auto_service_job_lines
    ADD COLUMN IF NOT EXISTS line_type VARCHAR(30) NULL DEFAULT 'service' AFTER job_id,
    ADD COLUMN IF NOT EXISTS product_id BIGINT UNSIGNED NULL AFTER line_type,
    ADD INDEX IF NOT EXISTS idx_asjl_job_type (job_id, line_type),
    ADD INDEX IF NOT EXISTS idx_asjl_product (product_id);

ALTER TABLE auto_service_jobs
    ADD COLUMN IF NOT EXISTS subtotal DECIMAL(20,4) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(20,4) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(20,4) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS total_amount DECIMAL(20,4) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(20,4) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS balance_amount DECIMAL(20,4) NOT NULL DEFAULT 0;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.parts_labour.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.parts_labour.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.parts_labour.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.parts_labour.manage');

/* Optional verification */
SELECT 'AutoService Stage 022 Parts & Labour SQL completed' AS status;
