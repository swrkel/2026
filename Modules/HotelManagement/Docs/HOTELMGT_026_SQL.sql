-- HOTELMGT_026 - Mini Bar & Room Consumption
-- Apply this SQL in each tenant database that uses the Hotel Management module.

CREATE TABLE IF NOT EXISTS hm_minibar_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    item_code VARCHAR(80) NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    category VARCHAR(80) NULL,
    unit VARCHAR(30) NULL,
    selling_price DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    cost_price DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    current_stock DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    reorder_level DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY hm_minibar_items_business_code_unique (business_id, item_code),
    KEY hm_minibar_items_business_location_index (business_id, business_location_id),
    KEY hm_minibar_items_category_index (category),
    KEY hm_minibar_items_active_index (is_active),
    KEY hm_minibar_items_stock_index (current_stock, reorder_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hm_minibar_consumptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    consumption_no VARCHAR(80) NOT NULL,
    room_id BIGINT UNSIGNED NULL,
    room_no VARCHAR(30) NULL,
    reservation_id BIGINT UNSIGNED NULL,
    folio_id BIGINT UNSIGNED NULL,
    guest_name VARCHAR(150) NULL,
    item_id BIGINT UNSIGNED NULL,
    item_code VARCHAR(80) NULL,
    item_name VARCHAR(150) NULL,
    consumption_date DATE NULL,
    qty DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    unit_price DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    discount_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    total_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    posted_charge_id BIGINT UNSIGNED NULL,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    posted_by BIGINT UNSIGNED NULL,
    posted_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY hm_minibar_consumptions_business_no_unique (business_id, consumption_no),
    KEY hm_minibar_consumptions_business_location_index (business_id, business_location_id),
    KEY hm_minibar_consumptions_room_index (room_id, room_no),
    KEY hm_minibar_consumptions_folio_index (folio_id),
    KEY hm_minibar_consumptions_item_index (item_id),
    KEY hm_minibar_consumptions_date_status_index (consumption_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'hotel.minibar.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'hotel.minibar.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'hotel.minibar.manage', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'hotel.minibar.manage');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'hotel.minibar.post_to_folio', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'hotel.minibar.post_to_folio');
