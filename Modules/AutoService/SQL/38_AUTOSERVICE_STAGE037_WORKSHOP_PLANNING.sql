-- AutoService Stage 037 - Workshop Planning
-- Tenant database SQL. Safe to run per tenant database.

CREATE TABLE IF NOT EXISTS autoservice_workshop_plans (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    business_location_id BIGINT UNSIGNED NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    planned_start_date DATE NOT NULL,
    planned_completion_date DATE NULL,
    priority VARCHAR(50) NULL,
    planning_notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY autoservice_workshop_plans_business_job_unique (business_id, job_id),
    KEY autoservice_workshop_plans_business_location_idx (business_id, business_location_id),
    KEY autoservice_workshop_plans_dates_idx (planned_start_date, planned_completion_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS autoservice_technician_schedules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    business_location_id BIGINT UNSIGNED NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    technician_id BIGINT UNSIGNED NOT NULL,
    planned_date DATE NOT NULL,
    start_time VARCHAR(20) NULL,
    end_time VARCHAR(20) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'planned',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY autoservice_tech_schedules_business_date_idx (business_id, business_location_id, planned_date),
    KEY autoservice_tech_schedules_technician_idx (technician_id, planned_date),
    KEY autoservice_tech_schedules_job_idx (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS autoservice_bay_schedules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    business_location_id BIGINT UNSIGNED NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    bay_id BIGINT UNSIGNED NOT NULL,
    planned_date DATE NOT NULL,
    start_time VARCHAR(20) NULL,
    end_time VARCHAR(20) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'reserved',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY autoservice_bay_schedules_business_date_idx (business_id, business_location_id, planned_date),
    KEY autoservice_bay_schedules_bay_idx (bay_id, planned_date),
    KEY autoservice_bay_schedules_job_idx (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS autoservice_parts_reservations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    business_location_id BIGINT UNSIGNED NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    part_name VARCHAR(191) NOT NULL,
    quantity DECIMAL(22,6) NOT NULL DEFAULT 0,
    required_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'reserved',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY autoservice_parts_reservations_business_date_idx (business_id, business_location_id, required_date),
    KEY autoservice_parts_reservations_job_idx (job_id),
    KEY autoservice_parts_reservations_product_idx (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('autoservice.workshop_planning.view', 'web', NOW(), NOW()),
('autoservice.workshop_planning.manage', 'web', NOW(), NOW());
