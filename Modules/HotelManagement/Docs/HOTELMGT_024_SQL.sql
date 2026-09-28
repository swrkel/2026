-- HOTELMGT_024_SQL.sql
-- Parcel 024 only: Spa & Wellness tables and indexes.
-- Run this against each tenant database that uses Hotel Management.

CREATE TABLE IF NOT EXISTS hm_spa_services (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(80) NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 0,
    price DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY hm_spa_services_business_code_unique (business_id, code),
    KEY hm_spa_services_business_location_index (business_id, business_location_id),
    KEY hm_spa_services_category_index (category),
    KEY hm_spa_services_active_index (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hm_spa_appointments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    appointment_no VARCHAR(80) NOT NULL,
    service_id BIGINT UNSIGNED NULL,
    guest_id BIGINT UNSIGNED NULL,
    folio_id BIGINT UNSIGNED NULL,
    guest_name VARCHAR(150) NOT NULL,
    mobile VARCHAR(30) NULL,
    room_no VARCHAR(30) NULL,
    therapist_name VARCHAR(120) NULL,
    appointment_date DATE NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    discount_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    net_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    paid_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    status VARCHAR(30) NOT NULL DEFAULT 'booked',
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY hm_spa_appointments_business_no_unique (business_id, appointment_no),
    KEY hm_spa_appointments_business_location_index (business_id, business_location_id),
    KEY hm_spa_appointments_date_status_index (appointment_date, status),
    KEY hm_spa_appointments_service_index (service_id),
    KEY hm_spa_appointments_guest_index (guest_id),
    KEY hm_spa_appointments_folio_index (folio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hm_spa_payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    appointment_id BIGINT UNSIGNED NOT NULL,
    payment_date DATE NULL,
    method VARCHAR(40) NOT NULL DEFAULT 'cash',
    amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    reference_no VARCHAR(100) NULL,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY hm_spa_payments_business_location_index (business_id, business_location_id),
    KEY hm_spa_payments_appointment_index (appointment_id),
    KEY hm_spa_payments_date_method_index (payment_date, method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'hotel.spa.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'hotel.spa.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'hotel.spa.manage', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'hotel.spa.manage');
