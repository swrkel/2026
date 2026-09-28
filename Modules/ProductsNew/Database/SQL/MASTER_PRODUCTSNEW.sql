-- MASTER_PRODUCTSNEW.sql
-- Contains Products New SQL from all stages up to Stage 016.
-- Run inside each tenant database. No database name is specified.


-- ============================================================
-- ProductsNew_STAGE001_PRODUCTSNEW_001.sql
-- ============================================================
-- ProductsNew_STAGE001_PRODUCTSNEW_001.sql
-- Run inside each tenant database. No database name is specified.
CREATE TABLE IF NOT EXISTS `products_new_product_meta` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NULL,`product_id` INT UNSIGNED NOT NULL,`primary_image` VARCHAR(191) NULL,`gallery` JSON NULL,`attachments` JSON NULL,`health_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,`health_payload` JSON NULL,`settings` JSON NULL,`created_by` INT UNSIGNED NULL,`updated_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL DEFAULT NULL,`updated_at` TIMESTAMP NULL DEFAULT NULL,PRIMARY KEY (`id`),UNIQUE KEY `products_new_meta_product_unique` (`product_id`),KEY `products_new_meta_business_idx` (`business_id`),KEY `products_new_meta_health_idx` (`health_score`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `products_new_timeline` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NULL,`product_id` INT UNSIGNED NOT NULL,`event` VARCHAR(80) NOT NULL,`payload` JSON NULL,`created_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL DEFAULT NULL,`updated_at` TIMESTAMP NULL DEFAULT NULL,PRIMARY KEY (`id`),KEY `products_new_timeline_product_idx` (`product_id`),KEY `products_new_timeline_business_idx` (`business_id`),KEY `products_new_timeline_event_idx` (`event`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `products_new_price_history` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NULL,`product_id` INT UNSIGNED NOT NULL,`variation_id` INT UNSIGNED NULL,`price_type` VARCHAR(80) NOT NULL DEFAULT 'selling',`old_price` DECIMAL(22,4) NULL,`new_price` DECIMAL(22,4) NULL,`currency` VARCHAR(10) NULL,`effective_from` DATE NULL,`created_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL DEFAULT NULL,`updated_at` TIMESTAMP NULL DEFAULT NULL,PRIMARY KEY (`id`),KEY `products_new_price_product_idx` (`product_id`),KEY `products_new_price_business_idx` (`business_id`),KEY `products_new_price_effective_idx` (`effective_from`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `products_new_barcode_queue` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NULL,`product_id` INT UNSIGNED NOT NULL,`variation_id` INT UNSIGNED NULL,`template_code` VARCHAR(80) NULL,`qty` DECIMAL(22,3) NOT NULL DEFAULT 1.000,`status` VARCHAR(30) NOT NULL DEFAULT 'pending',`created_by` INT UNSIGNED NULL,`printed_at` TIMESTAMP NULL DEFAULT NULL,`created_at` TIMESTAMP NULL DEFAULT NULL,`updated_at` TIMESTAMP NULL DEFAULT NULL,PRIMARY KEY (`id`),KEY `products_new_barcode_queue_business_idx` (`business_id`),KEY `products_new_barcode_queue_product_idx` (`product_id`),KEY `products_new_barcode_queue_status_idx` (`status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO permissions (`name`,`guard_name`,`created_at`,`updated_at`) SELECT permission_name,'web',NOW(),NOW() FROM (SELECT 'products_new.access' permission_name UNION ALL SELECT 'products_new.view' UNION ALL SELECT 'products_new.create' UNION ALL SELECT 'products_new.update' UNION ALL SELECT 'products_new.delete' UNION ALL SELECT 'products_new.dashboard' UNION ALL SELECT 'products_new.stock_center' UNION ALL SELECT 'products_new.barcode_center' UNION ALL SELECT 'products_new.import_export' UNION ALL SELECT 'products_new.reports' UNION ALL SELECT 'products_new.settings') p WHERE NOT EXISTS (SELECT 1 FROM permissions x WHERE x.name=p.permission_name AND x.guard_name='web');


-- ============================================================
-- ProductsNew_STAGE002_PRODUCTSNEW_002.sql
-- ============================================================
-- ProductsNew_STAGE002_PRODUCTSNEW_002_SETTINGS_CENTRE.sql
-- Purpose: Settings Centre permissions for Products New standalone module.
-- Run inside each tenant database. Do not prefix a database name.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.categories.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.categories.view' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.categories.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.categories.create' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.brands.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.brands.view' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.brands.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.brands.create' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.units.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.units.view' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.units.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.units.create' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.variations.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.variations.view' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.variations.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.variations.create' AND guard_name = 'web');


-- ============================================================
-- ProductsNew_STAGE003_PRODUCTSNEW_003_PRODUCT_MASTER.sql
-- ============================================================
-- ProductsNew_STAGE003_PRODUCTSNEW_003_PRODUCT_MASTER.sql
-- Purpose: Product Master permissions and safe standalone metadata compatibility for Products New.
-- Run inside each tenant database. Do not prefix a database name.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.view' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.create' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.update', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.update' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.disable', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.disable' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.360_view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.360_view' AND guard_name = 'web');


-- ============================================================
-- ProductsNew_STAGE004_PRODUCTSNEW_004_STOCK_PRICE_CENTER.sql
-- ============================================================
-- Products New Stage 004: Stock & Price Center
-- Run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `products_new_inventory_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `variation_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NULL,
  `movement_type` VARCHAR(50) NOT NULL,
  `movement_date` DATETIME NOT NULL,
  `qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `total_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reference_no` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pn_im_business_idx` (`business_id`),
  KEY `pn_im_product_idx` (`product_id`),
  KEY `pn_im_variation_location_idx` (`variation_id`,`location_id`),
  KEY `pn_im_type_date_idx` (`movement_type`,`movement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_opening_stock_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `reference_no` VARCHAR(100) NOT NULL,
  `session_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `notes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `posted_by` INT UNSIGNED NULL,
  `posted_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pn_os_business_idx` (`business_id`),
  KEY `pn_os_location_idx` (`location_id`),
  UNIQUE KEY `pn_os_reference_unique` (`business_id`,`reference_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_price_tiers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `variation_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NULL,
  `price_type` VARCHAR(50) NOT NULL,
  `price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `currency` VARCHAR(10) NULL,
  `starts_at` DATETIME NULL,
  `ends_at` DATETIME NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pn_pt_business_idx` (`business_id`),
  KEY `pn_pt_product_idx` (`product_id`),
  KEY `pn_pt_price_type_idx` (`price_type`),
  KEY `pn_pt_location_idx` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_barcode_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `variation_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NULL,
  `template_code` VARCHAR(100) NULL,
  `qty` INT NOT NULL DEFAULT 1,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pn_bq_business_idx` (`business_id`),
  KEY `pn_bq_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('products_new.inventory.view', 'web', NOW(), NOW()),
('products_new.inventory.create', 'web', NOW(), NOW()),
('products_new.opening_stock.view', 'web', NOW(), NOW()),
('products_new.opening_stock.create', 'web', NOW(), NOW()),
('products_new.price_center.view', 'web', NOW(), NOW()),
('products_new.price_center.create', 'web', NOW(), NOW());


-- ============================================================
-- ProductsNew_STAGE005_PRODUCTSNEW_005_BARCODE_MEDIA_CENTER.sql
-- ============================================================
/* Products New Stage 005 - Barcode, Label Templates and Product Media Center */

CREATE TABLE IF NOT EXISTS `products_new_barcode_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `paper_size` VARCHAR(50) NULL DEFAULT 'A4',
  `label_width` DECIMAL(10,3) NULL DEFAULT 38.000,
  `label_height` DECIMAL(10,3) NULL DEFAULT 25.000,
  `labels_per_row` INT NULL DEFAULT 3,
  `barcode_type` VARCHAR(50) NULL DEFAULT 'CODE128',
  `settings` JSON NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_barcode_templates_business_id_index` (`business_id`),
  KEY `products_new_barcode_templates_default_index` (`business_id`, `is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_media` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `media_type` VARCHAR(50) NOT NULL DEFAULT 'image',
  `title` VARCHAR(191) NULL,
  `file_name` VARCHAR(191) NULL,
  `file_path` TEXT NULL,
  `external_url` TEXT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `metadata` JSON NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_media_business_product_index` (`business_id`, `product_id`),
  KEY `products_new_media_type_index` (`business_id`, `media_type`),
  KEY `products_new_media_primary_index` (`business_id`, `product_id`, `is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `products_new_barcode_queue`
  ADD COLUMN IF NOT EXISTS `template_id` BIGINT UNSIGNED NULL AFTER `variation_id`,
  ADD COLUMN IF NOT EXISTS `barcode_value` VARCHAR(191) NULL AFTER `template_id`;

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.media.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.media.view');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.media.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.media.create');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.barcode.templates', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.barcode.templates');


-- ============================================================
-- ProductsNew_STAGE006_PRODUCTSNEW_006_INTELLIGENCE_CENTRE.sql
-- ============================================================
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


-- ============================================================
-- ProductsNew_STAGE007_PRODUCTSNEW_007_BATCH_LOT_EXPIRY.sql
-- ============================================================
-- ProductsNew_STAGE007_PRODUCTSNEW_007_BATCH_LOT_EXPIRY.sql
-- Run on every tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `products_new_batches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `variation_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NOT NULL,
  `batch_no` VARCHAR(191) NOT NULL,
  `lot_no` VARCHAR(191) NULL,
  `supplier_batch_no` VARCHAR(191) NULL,
  `manufactured_at` DATE NULL,
  `expiry_at` DATE NULL,
  `opening_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `current_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reserved_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `available_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `cost_price` DECIMAL(22,4) NULL,
  `selling_price` DECIMAL(22,4) NULL,
  `note` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_new_batches_unique` (`business_id`,`product_id`,`location_id`,`batch_no`,`lot_no`),
  KEY `products_new_batches_expiry_idx` (`business_id`,`expiry_at`),
  KEY `products_new_batches_product_idx` (`business_id`,`product_id`,`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_batch_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `variation_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NOT NULL,
  `batch_id` BIGINT UNSIGNED NOT NULL,
  `movement_type` VARCHAR(50) NOT NULL,
  `transaction_date` DATETIME NOT NULL,
  `qty_in` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `qty_out` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `balance_after` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reference_type` VARCHAR(100) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `note` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_batch_movements_batch_idx` (`batch_id`,`transaction_date`),
  KEY `products_new_batch_movements_product_idx` (`business_id`,`product_id`,`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_expiry_alerts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `variation_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NOT NULL,
  `batch_id` BIGINT UNSIGNED NOT NULL,
  `batch_no` VARCHAR(191) NOT NULL,
  `expiry_at` DATE NOT NULL,
  `alert_type` VARCHAR(50) NOT NULL,
  `alert_date` DATE NOT NULL,
  `severity` VARCHAR(50) NOT NULL DEFAULT 'warning',
  `is_resolved` TINYINT(1) NOT NULL DEFAULT 0,
  `resolved_at` DATETIME NULL,
  `resolved_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_new_expiry_alerts_unique` (`business_id`,`batch_id`,`alert_type`),
  KEY `products_new_expiry_alerts_due_idx` (`business_id`,`expiry_at`,`is_resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_recalls` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `batch_id` BIGINT UNSIGNED NULL,
  `recall_no` VARCHAR(191) NOT NULL,
  `reason` TEXT NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'open',
  `started_at` DATETIME NULL,
  `closed_at` DATETIME NULL,
  `created_by` INT UNSIGNED NULL,
  `closed_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_new_recalls_unique` (`business_id`,`recall_no`),
  KEY `products_new_recalls_status_idx` (`business_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('products_new.batch.view','web',NOW(),NOW()),
('products_new.batch.create','web',NOW(),NOW()),
('products_new.batch.adjust','web',NOW(),NOW()),
('products_new.expiry.view','web',NOW(),NOW()),
('products_new.expiry.resolve','web',NOW(),NOW()),
('products_new.recall.view','web',NOW(),NOW()),
('products_new.recall.create','web',NOW(),NOW()),
('products_new.recall.close','web',NOW(),NOW()),
('products_new.reports.batch','web',NOW(),NOW());


-- ============================================================
-- ProductsNew_STAGE008_PRODUCTSNEW_008_SERIAL_WARRANTY.sql
-- ============================================================
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


-- ============================================================
-- ProductsNew_STAGE009_PRODUCTSNEW_009_IMPORT_EXPORT_DATA_QUALITY.sql
-- ============================================================
-- ProductsNew Stage 009: Import, Export & Data Quality Centre
-- Run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS products_new_import_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  business_location_id BIGINT UNSIGNED NULL,
  file_name VARCHAR(255) NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'draft',
  total_rows INT NOT NULL DEFAULT 0,
  valid_rows INT NOT NULL DEFAULT 0,
  invalid_rows INT NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  completed_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_pn_import_business (business_id, business_location_id),
  INDEX idx_pn_import_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_import_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  import_session_id BIGINT UNSIGNED NOT NULL,
  line_no INT NOT NULL,
  product_name VARCHAR(255) NULL,
  sku VARCHAR(191) NULL,
  barcode VARCHAR(191) NULL,
  payload_json LONGTEXT NULL,
  validation_errors LONGTEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_pn_import_lines_session (import_session_id),
  INDEX idx_pn_import_lines_sku (sku),
  INDEX idx_pn_import_lines_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_import_validation_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  rule_code VARCHAR(100) NOT NULL,
  rule_name VARCHAR(191) NOT NULL,
  is_required TINYINT(1) NOT NULL DEFAULT 1,
  severity VARCHAR(20) NOT NULL DEFAULT 'error',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_pn_import_rule (business_id, rule_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_export_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  business_location_id BIGINT UNSIGNED NULL,
  export_type VARCHAR(60) NOT NULL DEFAULT 'products',
  filters_json LONGTEXT NULL,
  file_path VARCHAR(255) NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'pending',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_pn_export_business (business_id, business_location_id),
  INDEX idx_pn_export_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_data_cleanup_tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  task_type VARCHAR(100) NOT NULL,
  payload_json LONGTEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'pending',
  result_json LONGTEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  completed_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_pn_cleanup_business (business_id),
  INDEX idx_pn_cleanup_type (task_type),
  INDEX idx_pn_cleanup_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_duplicate_merges (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  primary_product_id BIGINT UNSIGNED NOT NULL,
  duplicate_product_id BIGINT UNSIGNED NOT NULL,
  merge_status VARCHAR(40) NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  completed_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_pn_duplicate_merge_business (business_id),
  INDEX idx_pn_duplicate_merge_products (primary_product_id, duplicate_product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO products_new_import_validation_rules (business_id, rule_code, rule_name, is_required, severity, created_at, updated_at) VALUES
(NULL, 'product_name_required', 'Product name is required', 1, 'error', NOW(), NOW()),
(NULL, 'sku_required', 'SKU is required', 1, 'error', NOW(), NOW()),
(NULL, 'selling_price_numeric', 'Selling price must be numeric', 1, 'error', NOW(), NOW()),
(NULL, 'purchase_price_numeric', 'Purchase price must be numeric', 1, 'error', NOW(), NOW());

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('products_new.import_export.view', 'web', NOW(), NOW()),
('products_new.import_export.import', 'web', NOW(), NOW()),
('products_new.import_export.export', 'web', NOW(), NOW()),
('products_new.data_cleanup.view', 'web', NOW(), NOW()),
('products_new.data_cleanup.manage', 'web', NOW(), NOW());


-- ============================================================
-- ProductsNew_STAGE010_PRODUCTSNEW_010_REPORTS_ANALYTICS.sql
-- ============================================================
-- ProductsNew_STAGE010_PRODUCTSNEW_010_REPORTS_ANALYTICS.sql
-- Global tenant SQL. Do not prefix database name.
CREATE TABLE IF NOT EXISTS products_new_report_presets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  report_key VARCHAR(120) NOT NULL,
  name VARCHAR(191) NOT NULL,
  filters LONGTEXT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY products_new_report_presets_business_report_idx (business_id, report_key),
  KEY products_new_report_presets_location_idx (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_report_snapshots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  report_key VARCHAR(120) NOT NULL,
  snapshot_date DATE NOT NULL,
  metric_key VARCHAR(120) NOT NULL,
  metric_value DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  extra_data LONGTEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY products_new_report_snapshots_business_report_idx (business_id, report_key, snapshot_date),
  KEY products_new_report_snapshots_metric_idx (metric_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.index', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.index' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.movement', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.movement' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.profitability', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.profitability' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.aging', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.aging' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.fast_slow_dead', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.fast_slow_dead' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.negative_overstock', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.negative_overstock' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.expiry', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.expiry' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.serial', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.serial' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.category_brand', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.category_brand' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.price_history', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.price_history' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.inventory_turnover', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.inventory_turnover' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.abc_xyz', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.abc_xyz' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.reorder_recommendation', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.reorder_recommendation' AND guard_name = 'web');


-- ============================================================
-- ProductsNew_STAGE011_PRODUCTSNEW_011_DASHBOARD_KPI_CENTRE.sql
-- ============================================================
-- ProductsNew_STAGE011_PRODUCTSNEW_011_DASHBOARD_KPI_CENTRE.sql
-- Global tenant SQL. Run inside each tenant database. Do not prefix database name.
CREATE TABLE IF NOT EXISTS products_new_dashboard_snapshots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NULL,
  snapshot_date DATE NOT NULL,
  snapshot_payload LONGTEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY products_new_dashboard_snapshots_business_date_idx (business_id, snapshot_date),
  KEY products_new_dashboard_snapshots_location_idx (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_kpi_preferences (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  visible_cards LONGTEXT NULL,
  settings LONGTEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY products_new_kpi_preferences_user_unique (business_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.kpi.index', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.kpi.index' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.kpi.snapshot', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.kpi.snapshot' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.dashboard.management', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.dashboard.management' AND guard_name = 'web');


-- ============================================================
-- ProductsNew_STAGE012_PRODUCTSNEW_012_PRODUCTION_HARDENING.sql
-- ============================================================
/*
PRODUCTSNEW_012 - Production Hardening & Final Standalone Audit
Run this in each tenant database where Products New is enabled.
No database name is hardcoded.
*/

-- Permissions for final audit and integration bridge pages
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.production_audit.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.production_audit.view' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.integration_bridge.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.integration_bridge.view' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.integration_bridge.api', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.integration_bridge.api' AND guard_name = 'web');

-- Optional menu/page registry entries. Safe when the ERP has module/page registries.
CREATE TABLE IF NOT EXISTS products_new_production_audit_results (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    audit_key VARCHAR(120) NOT NULL,
    audit_status VARCHAR(50) NOT NULL DEFAULT 'ready',
    audit_payload LONGTEXT NULL,
    checked_by BIGINT UNSIGNED NULL,
    checked_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY products_new_prod_audit_business_idx (business_id),
    KEY products_new_prod_audit_location_idx (business_location_id),
    KEY products_new_prod_audit_key_idx (audit_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ProductsNew_STAGE013_PRODUCTSNEW_013_MIGRATION_READINESS.sql
-- ============================================================
-- PRODUCTSNEW_013 - Migration readiness, legacy comparison, and testing helpers
-- Global tenant-safe SQL. Do not add database names before tables.

CREATE TABLE IF NOT EXISTS products_new_migration_checks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NOT NULL,
    check_key VARCHAR(120) NOT NULL,
    check_name VARCHAR(191) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'review',
    note TEXT NULL,
    checked_by INT UNSIGNED NULL,
    checked_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY products_new_migration_checks_business_status_idx (business_id, status),
    UNIQUE KEY products_new_migration_checks_business_key_unique (business_id, check_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_testing_results (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NOT NULL,
    test_key VARCHAR(120) NOT NULL,
    test_name VARCHAR(191) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    remarks TEXT NULL,
    tested_by INT UNSIGNED NULL,
    tested_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY products_new_testing_results_business_status_idx (business_id, status),
    UNIQUE KEY products_new_testing_results_business_key_unique (business_id, test_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('products_new.migration_readiness.view', 'web', NOW(), NOW()),
('products_new.legacy_comparison.view', 'web', NOW(), NOW()),
('products_new.testing_checklist.view', 'web', NOW(), NOW());


-- ============================================================
-- ProductsNew_STAGE014_PRODUCTSNEW_014_DEPLOYMENT_SUPPORT.sql
-- ============================================================
-- Products New Stage 014 - Deployment Support
-- Global tenant SQL. Run inside each tenant database only. No database name is hardcoded.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.deployment_readiness', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.deployment_readiness');

-- Optional menu registration table support. Safe for installations using module_menu_items.
SET @products_new_menu_table_exists := (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'module_menu_items'
);
SET @products_new_sql := IF(@products_new_menu_table_exists > 0,
"INSERT INTO module_menu_items (module, label, route, permission, sort_order, is_active, created_at, updated_at)
 SELECT 'ProductsNew', 'Deployment Readiness', 'products-new.deployment-readiness.index', 'products_new.deployment_readiness', 990, 1, NOW(), NOW()
 WHERE NOT EXISTS (SELECT 1 FROM module_menu_items WHERE route = 'products-new.deployment-readiness.index')",
"SELECT 'module_menu_items table not found - skipped Products New Deployment Readiness menu insert' AS products_new_stage014_note"
);
PREPARE stmt FROM @products_new_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ============================================================
-- ProductsNew_STAGE015_PRODUCTSNEW_015_UNIVERSAL_PRODUCT_FRAMEWORK.sql
-- ============================================================
-- ProductsNew Stage 015 - Universal Product Framework
-- Global tenant-safe SQL. Execute inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `products_new_product_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `code` VARCHAR(60) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_new_product_types_business_code_unique` (`business_id`,`code`),
  KEY `products_new_product_types_business_active_index` (`business_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_custom_fields` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_type_id` BIGINT UNSIGNED NULL,
  `label` VARCHAR(120) NOT NULL,
  `field_key` VARCHAR(80) NOT NULL,
  `field_type` VARCHAR(40) NOT NULL DEFAULT 'text',
  `options` TEXT NULL,
  `is_required` TINYINT(1) NOT NULL DEFAULT 0,
  `is_searchable` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_new_custom_fields_business_key_unique` (`business_id`,`field_key`),
  KEY `products_new_custom_fields_type_index` (`product_type_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_custom_field_values` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `field_key` VARCHAR(80) NOT NULL,
  `field_value` TEXT NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_new_custom_field_values_product_key_unique` (`product_id`,`field_key`),
  KEY `products_new_custom_field_values_key_index` (`field_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `rule_group` VARCHAR(80) NOT NULL DEFAULT 'general',
  `condition_json` LONGTEXT NULL,
  `action_json` LONGTEXT NULL,
  `severity` VARCHAR(30) NOT NULL DEFAULT 'warning',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_rules_business_group_index` (`business_id`,`rule_group`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_type_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(160) NOT NULL,
  `template_json` LONGTEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_templates_business_type_index` (`business_id`,`product_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_saved_filters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `name` VARCHAR(160) NOT NULL,
  `filter_json` LONGTEXT NULL,
  `is_shared` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_saved_filters_business_user_index` (`business_id`,`user_id`,`is_shared`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_bulk_operation_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `operation` VARCHAR(80) NOT NULL,
  `criteria_json` LONGTEXT NULL,
  `changes_json` LONGTEXT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'draft',
  `affected_count` INT NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_bulk_operation_sessions_business_status_index` (`business_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_version_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `version_no` INT NOT NULL DEFAULT 1,
  `change_type` VARCHAR(80) NOT NULL DEFAULT 'update',
  `before_json` LONGTEXT NULL,
  `after_json` LONGTEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_version_history_product_index` (`product_id`,`version_no`),
  KEY `products_new_version_history_business_index` (`business_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products_new_product_types` (`business_id`,`name`,`code`,`description`,`is_active`,`created_at`,`updated_at`)
SELECT b.id, x.name, x.code, x.description, 1, NOW(), NOW()
FROM businesses b
JOIN (
  SELECT 'Physical Product' name, 'physical_product' code, 'Standard stock item' description UNION ALL
  SELECT 'Service', 'service', 'Non-stock service item' UNION ALL
  SELECT 'Raw Material', 'raw_material', 'Manufacturing input' UNION ALL
  SELECT 'Finished Goods', 'finished_goods', 'Manufactured sale item' UNION ALL
  SELECT 'Spare Parts', 'spare_parts', 'Service and vehicle spare part' UNION ALL
  SELECT 'Fuel Product', 'fuel_product', 'Fuel or petroleum product' UNION ALL
  SELECT 'Hotel Item', 'hotel_item', 'Hotel, kitchen, housekeeping or room item' UNION ALL
  SELECT 'Medical Item', 'medical_item', 'Medical/pharmacy product with batch and expiry controls' UNION ALL
  SELECT 'Rental Item', 'rental_item', 'Asset or item available for rental' UNION ALL
  SELECT 'Bundle / Package', 'bundle_package', 'Kit, combo, package or bundle'
) x
WHERE NOT EXISTS (SELECT 1 FROM products_new_product_types t WHERE t.business_id=b.id AND t.code=x.code);


-- ============================================================
-- ProductsNew_STAGE016_PRODUCTSNEW_016_ENTERPRISE_INVENTORY_INTELLIGENCE.sql
-- ============================================================
-- ProductsNew_STAGE016_PRODUCTSNEW_016_ENTERPRISE_INVENTORY_INTELLIGENCE.sql
-- Run inside each tenant database. No database name is specified.
CREATE TABLE IF NOT EXISTS `products_new_inventory_planning_profiles` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NOT NULL,`location_id` INT UNSIGNED NULL,`product_id` INT UNSIGNED NOT NULL,`variation_id` INT UNSIGNED NULL,`minimum_qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,`maximum_qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,`safety_stock_qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,`lead_time_days` INT NOT NULL DEFAULT 0,`seasonality_profile` JSON NULL,`planning_method` VARCHAR(50) NOT NULL DEFAULT 'manual',`reorder_qty` DECIMAL(22,3) NULL,`is_active` TINYINT(1) NOT NULL DEFAULT 1,`created_by` INT UNSIGNED NULL,`updated_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL DEFAULT NULL,`updated_at` TIMESTAMP NULL DEFAULT NULL,PRIMARY KEY (`id`),KEY `pn_ipp_business_idx` (`business_id`),KEY `pn_ipp_location_idx` (`location_id`),KEY `pn_ipp_product_idx` (`product_id`),UNIQUE KEY `pn_ipp_unique` (`business_id`,`location_id`,`product_id`,`variation_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `products_new_reorder_proposals` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NOT NULL,`location_id` INT UNSIGNED NULL,`product_id` INT UNSIGNED NOT NULL,`variation_id` INT UNSIGNED NULL,`proposal_date` DATE NOT NULL,`current_stock` DECIMAL(22,3) NOT NULL DEFAULT 0.000,`minimum_qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,`maximum_qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,`safety_stock_qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,`lead_time_days` INT NOT NULL DEFAULT 0,`recommended_qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,`estimated_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,`status` VARCHAR(30) NOT NULL DEFAULT 'pending',`notes` TEXT NULL,`created_by` INT UNSIGNED NULL,`approved_by` INT UNSIGNED NULL,`approved_at` DATETIME NULL,`created_at` TIMESTAMP NULL DEFAULT NULL,`updated_at` TIMESTAMP NULL DEFAULT NULL,PRIMARY KEY (`id`),KEY `pn_rp_business_idx` (`business_id`),KEY `pn_rp_location_idx` (`location_id`),KEY `pn_rp_product_idx` (`product_id`),KEY `pn_rp_status_idx` (`status`),KEY `pn_rp_date_idx` (`proposal_date`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `products_new_cost_snapshots` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NOT NULL,`location_id` INT UNSIGNED NULL,`product_id` INT UNSIGNED NOT NULL,`variation_id` INT UNSIGNED NULL,`snapshot_date` DATE NOT NULL,`last_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,`average_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,`moving_average_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,`standard_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,`replacement_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,`landed_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,`margin_percentage` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,`payload` JSON NULL,`created_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL DEFAULT NULL,`updated_at` TIMESTAMP NULL DEFAULT NULL,PRIMARY KEY (`id`),KEY `pn_cs_business_idx` (`business_id`),KEY `pn_cs_product_idx` (`product_id`),KEY `pn_cs_location_idx` (`location_id`),KEY `pn_cs_snapshot_date_idx` (`snapshot_date`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `products_new_product_classifications` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NOT NULL,`location_id` INT UNSIGNED NULL,`product_id` INT UNSIGNED NOT NULL,`abc_class` VARCHAR(5) NOT NULL DEFAULT 'C',`xyz_class` VARCHAR(5) NOT NULL DEFAULT 'Z',`combined_class` VARCHAR(10) NOT NULL DEFAULT 'CZ',`annual_consumption_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,`demand_variability` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,`movement_rank` INT NULL,`classification_date` DATE NOT NULL,`payload` JSON NULL,`created_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL DEFAULT NULL,`updated_at` TIMESTAMP NULL DEFAULT NULL,PRIMARY KEY (`id`),KEY `pn_pc_business_idx` (`business_id`),KEY `pn_pc_location_idx` (`location_id`),KEY `pn_pc_product_idx` (`product_id`),KEY `pn_pc_abc_xyz_idx` (`abc_class`,`xyz_class`),UNIQUE KEY `pn_pc_unique` (`business_id`,`location_id`,`product_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `products_new_stock_intelligence_snapshots` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NOT NULL,`location_id` INT UNSIGNED NULL,`product_id` INT UNSIGNED NOT NULL,`variation_id` INT UNSIGNED NULL,`snapshot_date` DATE NOT NULL,`current_stock` DECIMAL(22,3) NOT NULL DEFAULT 0.000,`available_stock` DECIMAL(22,3) NOT NULL DEFAULT 0.000,`reserved_stock` DECIMAL(22,3) NOT NULL DEFAULT 0.000,`on_order_qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,`in_transit_qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,`stock_age_days` INT NOT NULL DEFAULT 0,`last_sale_at` DATETIME NULL,`last_purchase_at` DATETIME NULL,`movement_status` VARCHAR(30) NOT NULL DEFAULT 'normal',`recommended_action` VARCHAR(80) NULL,`risk_level` VARCHAR(30) NOT NULL DEFAULT 'low',`payload` JSON NULL,`created_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL DEFAULT NULL,`updated_at` TIMESTAMP NULL DEFAULT NULL,PRIMARY KEY (`id`),KEY `pn_sis_business_idx` (`business_id`),KEY `pn_sis_product_idx` (`product_id`),KEY `pn_sis_location_idx` (`location_id`),KEY `pn_sis_movement_idx` (`movement_status`),KEY `pn_sis_risk_idx` (`risk_level`),KEY `pn_sis_snapshot_date_idx` (`snapshot_date`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT permission_name, 'web', NOW(), NOW() FROM (SELECT 'products_new.inventory_intelligence.view' permission_name UNION ALL SELECT 'products_new.inventory_intelligence.planning' UNION ALL SELECT 'products_new.inventory_intelligence.cost_analysis' UNION ALL SELECT 'products_new.inventory_intelligence.stock_intelligence' UNION ALL SELECT 'products_new.inventory_intelligence.abc_xyz' UNION ALL SELECT 'products_new.inventory_intelligence.executive' UNION ALL SELECT 'products_new.inventory_intelligence.reorder_proposals') p WHERE NOT EXISTS (SELECT 1 FROM permissions x WHERE x.name = p.permission_name AND x.guard_name = 'web');

-- =====================================================================
-- PRODUCTSNEW_017 - Product Command Center
-- =====================================================================
SOURCE ProductsNew_STAGE017_PRODUCTSNEW_017_PRODUCT_COMMAND_CENTER.sql;

-- =====================================================================
-- 03 Aug 2026 - Products New purchase-price visibility permission
-- =====================================================================
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.purchase_price.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1
    FROM `permissions`
    WHERE `name` = 'products_new.purchase_price.view'
      AND `guard_name` = 'web'
);

