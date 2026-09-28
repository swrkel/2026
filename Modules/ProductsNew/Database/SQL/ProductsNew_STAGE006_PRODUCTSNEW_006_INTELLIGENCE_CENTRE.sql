-- Products New Stage 006 - Product Intelligence Centre
-- Global SQL: run inside each tenant database. No database name is hardcoded.

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS products_new_status VARCHAR(50) NULL AFTER status,
    ADD INDEX IF NOT EXISTS products_new_products_status_idx (business_id, products_new_status);

CREATE TABLE IF NOT EXISTS products_new_product_statuses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(30) NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    allowed_next_statuses JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY products_new_status_unique (business_id, code),
    KEY products_new_status_active_idx (business_id, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO products_new_product_statuses (business_id, code, name, color, is_default, is_active, sort_order, allowed_next_statuses, created_at, updated_at) VALUES
(NULL, 'draft', 'Draft', '#6c757d', 0, 1, 10, JSON_ARRAY('pending_review','active','archived'), NOW(), NOW()),
(NULL, 'pending_review', 'Pending Review', '#ffc107', 0, 1, 20, JSON_ARRAY('active','draft','suspended'), NOW(), NOW()),
(NULL, 'active', 'Active', '#28a745', 1, 1, 30, JSON_ARRAY('suspended','discontinued','archived'), NOW(), NOW()),
(NULL, 'suspended', 'Suspended', '#fd7e14', 0, 1, 40, JSON_ARRAY('active','discontinued','archived'), NOW(), NOW()),
(NULL, 'discontinued', 'Discontinued', '#dc3545', 0, 1, 50, JSON_ARRAY('active','archived'), NOW(), NOW()),
(NULL, 'archived', 'Archived', '#343a40', 0, 1, 60, JSON_ARRAY('active'), NOW(), NOW());

CREATE TABLE IF NOT EXISTS products_new_status_transitions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    from_status VARCHAR(50) NULL,
    to_status VARCHAR(50) NOT NULL,
    note TEXT NULL,
    metadata JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY products_new_status_transition_product_idx (business_id, product_id),
    KEY products_new_status_transition_status_idx (business_id, to_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_product_relationships (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    related_product_id BIGINT UNSIGNED NOT NULL,
    relationship_type VARCHAR(50) NOT NULL,
    note TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    metadata JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY products_new_relationship_unique (business_id, product_id, related_product_id, relationship_type),
    KEY products_new_relationship_type_idx (business_id, relationship_type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_duplicate_reviews (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    duplicate_product_id BIGINT UNSIGNED NOT NULL,
    score DECIMAL(10,2) NOT NULL DEFAULT 0,
    matched_fields JSON NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'open',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY products_new_duplicate_unique (business_id, product_id, duplicate_product_id),
    KEY products_new_duplicate_status_idx (business_id, status, score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_product_notes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    note_type VARCHAR(50) NOT NULL DEFAULT 'internal',
    title VARCHAR(191) NULL,
    note TEXT NOT NULL,
    is_pinned TINYINT(1) NOT NULL DEFAULT 0,
    metadata JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY products_new_note_product_idx (business_id, product_id, is_pinned),
    KEY products_new_note_type_idx (business_id, note_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_availability_snapshots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variation_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    current_stock DECIMAL(22,4) NOT NULL DEFAULT 0,
    reserved_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    available_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    in_transit_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    on_order_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    reorder_level DECIMAL(22,4) NOT NULL DEFAULT 0,
    last_sale_at TIMESTAMP NULL,
    last_purchase_at TIMESTAMP NULL,
    last_movement_at TIMESTAMP NULL,
    snapshot_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY products_new_availability_unique (business_id, product_id, variation_id, location_id),
    KEY products_new_availability_location_idx (business_id, location_id),
    KEY products_new_availability_reorder_idx (business_id, available_qty, reorder_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('products_new.intelligence.view','web',NOW(),NOW()),
('products_new.relationships.manage','web',NOW(),NOW()),
('products_new.workflow.manage','web',NOW(),NOW()),
('products_new.duplicates.manage','web',NOW(),NOW()),
('products_new.notes.manage','web',NOW(),NOW()),
('products_new.availability.view','web',NOW(),NOW());
