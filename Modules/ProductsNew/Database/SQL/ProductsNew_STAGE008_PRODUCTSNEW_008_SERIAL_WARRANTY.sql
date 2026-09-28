-- ProductsNew_STAGE008_PRODUCTSNEW_008_SERIAL_WARRANTY.sql
-- Run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS products_new_serial_numbers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variation_id BIGINT UNSIGNED NULL,
    location_id INT UNSIGNED NULL,
    batch_id BIGINT UNSIGNED NULL,
    serial_no VARCHAR(191) NOT NULL,
    imei_no VARCHAR(191) NULL,
    asset_tag VARCHAR(191) NULL,
    purchase_reference VARCHAR(191) NULL,
    purchase_date DATE NULL,
    cost_price DECIMAL(22,4) NULL,
    selling_price DECIMAL(22,4) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'available',
    note TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY products_new_serial_unique (business_id, serial_no),
    KEY products_new_serial_product_idx (business_id, product_id),
    KEY products_new_serial_location_idx (business_id, location_id),
    KEY products_new_serial_status_idx (business_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_serial_movements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    serial_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variation_id BIGINT UNSIGNED NULL,
    movement_type VARCHAR(50) NOT NULL,
    from_location_id INT UNSIGNED NULL,
    to_location_id INT UNSIGNED NULL,
    reference_type VARCHAR(100) NULL,
    reference_id BIGINT UNSIGNED NULL,
    note TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY products_new_serial_movements_serial_idx (business_id, serial_id),
    KEY products_new_serial_movements_product_idx (business_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_warranty_registrations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    serial_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    warranty_code VARCHAR(191) NULL,
    warranty_start_date DATE NOT NULL,
    warranty_end_date DATE NOT NULL,
    invoice_no VARCHAR(191) NULL,
    sale_reference VARCHAR(191) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    terms TEXT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY products_new_warranty_product_idx (business_id, product_id),
    KEY products_new_warranty_serial_idx (business_id, serial_id),
    KEY products_new_warranty_end_idx (business_id, warranty_end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_warranty_claims (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    registration_id BIGINT UNSIGNED NOT NULL,
    claim_no VARCHAR(191) NULL,
    claim_date DATE NOT NULL,
    fault_description TEXT NOT NULL,
    resolution_note TEXT NULL,
    claim_status VARCHAR(50) NOT NULL DEFAULT 'open',
    claim_amount DECIMAL(22,4) NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY products_new_warranty_claim_registration_idx (business_id, registration_id),
    KEY products_new_warranty_claim_status_idx (business_id, claim_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_ownership_histories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    serial_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED NULL,
    transaction_id BIGINT UNSIGNED NULL,
    owner_name VARCHAR(191) NULL,
    owner_mobile VARCHAR(50) NULL,
    ownership_type VARCHAR(50) NOT NULL,
    started_at DATE NULL,
    ended_at DATE NULL,
    note TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY products_new_ownership_serial_idx (business_id, serial_id),
    KEY products_new_ownership_contact_idx (business_id, contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO products_new_permissions (name, display_name, module, created_at, updated_at) VALUES
('products_new.serial.view','Products New Serial View','Products New',NOW(),NOW()),
('products_new.serial.create','Products New Serial Create','Products New',NOW(),NOW()),
('products_new.warranty.view','Products New Warranty View','Products New',NOW(),NOW()),
('products_new.warranty.manage','Products New Warranty Manage','Products New',NOW(),NOW()),
('products_new.ownership.view','Products New Ownership View','Products New',NOW(),NOW());
