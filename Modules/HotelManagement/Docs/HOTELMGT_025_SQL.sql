-- HOTELMGT_025_SQL.sql
-- Parcel 025 only: Hotel Transport / Airport Transfer tables, indexes and permissions.
-- Run this against each tenant database that uses Hotel Management.

CREATE TABLE IF NOT EXISTS hm_transport_vehicles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    vehicle_no VARCHAR(60) NOT NULL,
    vehicle_type VARCHAR(80) NULL,
    driver_name VARCHAR(120) NULL,
    driver_mobile VARCHAR(30) NULL,
    seating_capacity INT UNSIGNED NOT NULL DEFAULT 0,
    base_rate DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY hm_transport_vehicles_business_vehicle_unique (business_id, vehicle_no),
    KEY hm_transport_vehicles_business_location_index (business_id, business_location_id),
    KEY hm_transport_vehicles_type_index (vehicle_type),
    KEY hm_transport_vehicles_active_index (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hm_transport_bookings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    booking_no VARCHAR(80) NOT NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    reservation_id BIGINT UNSIGNED NULL,
    folio_id BIGINT UNSIGNED NULL,
    guest_name VARCHAR(150) NOT NULL,
    mobile VARCHAR(30) NULL,
    room_no VARCHAR(30) NULL,
    trip_type VARCHAR(40) NOT NULL DEFAULT 'airport_pickup',
    pickup_date DATE NULL,
    pickup_time TIME NULL,
    pickup_location VARCHAR(255) NULL,
    drop_location VARCHAR(255) NULL,
    flight_no VARCHAR(80) NULL,
    driver_name VARCHAR(120) NULL,
    rate DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    discount_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    net_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    paid_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    status VARCHAR(30) NOT NULL DEFAULT 'requested',
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY hm_transport_bookings_business_no_unique (business_id, booking_no),
    KEY hm_transport_bookings_business_location_index (business_id, business_location_id),
    KEY hm_transport_bookings_pickup_status_index (pickup_date, status),
    KEY hm_transport_bookings_vehicle_index (vehicle_id),
    KEY hm_transport_bookings_reservation_index (reservation_id),
    KEY hm_transport_bookings_folio_index (folio_id),
    KEY hm_transport_bookings_trip_type_index (trip_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hm_transport_payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    booking_id BIGINT UNSIGNED NOT NULL,
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
    KEY hm_transport_payments_business_location_index (business_id, business_location_id),
    KEY hm_transport_payments_booking_index (booking_id),
    KEY hm_transport_payments_date_method_index (payment_date, method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'hotel.transport.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'hotel.transport.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'hotel.transport.manage', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'hotel.transport.manage');
