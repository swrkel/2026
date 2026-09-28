-- Statement 1
-- ProductsNew_STAGE001_PRODUCTSNEW_001.sql
-- Run inside each tenant database. No database name is specified.
CREATE TABLE IF NOT EXISTS `products_new_product_meta` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NULL,`product_id` INT UNSIGNED NOT NULL,`primary_image` VARCHAR(191) NULL,`gallery` JSON NULL,`attachments` JSON NULL,`health_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,`health_payload` JSON NULL,`settings` JSON NULL,`created_by` INT UNSIGNED NULL,`updated_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL DEFAULT NULL,`updated_at` TIMESTAMP NULL DEFAULT NULL,PRIMARY KEY (`id`),UNIQUE KEY `products_new_meta_product_unique` (`product_id`),KEY `products_new_meta_business_idx` (`business_id`),KEY `products_new_meta_health_idx` (`health_score`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Statement 2
CREATE TABLE IF NOT EXISTS `products_new_timeline` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NULL,`product_id` INT UNSIGNED NOT NULL,`event` VARCHAR(80) NOT NULL,`payload` JSON NULL,`created_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL DEFAULT NULL,`updated_at` TIMESTAMP NULL DEFAULT NULL,PRIMARY KEY (`id`),KEY `products_new_timeline_product_idx` (`product_id`),KEY `products_new_timeline_business_idx` (`business_id`),KEY `products_new_timeline_event_idx` (`event`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Statement 3
CREATE TABLE IF NOT EXISTS `products_new_price_history` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NULL,`product_id` INT UNSIGNED NOT NULL,`variation_id` INT UNSIGNED NULL,`price_type` VARCHAR(80) NOT NULL DEFAULT 'selling',`old_price` DECIMAL(22,4) NULL,`new_price` DECIMAL(22,4) NULL,`currency` VARCHAR(10) NULL,`effective_from` DATE NULL,`created_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL DEFAULT NULL,`updated_at` TIMESTAMP NULL DEFAULT NULL,PRIMARY KEY (`id`),KEY `products_new_price_product_idx` (`product_id`),KEY `products_new_price_business_idx` (`business_id`),KEY `products_new_price_effective_idx` (`effective_from`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Statement 4
CREATE TABLE IF NOT EXISTS `products_new_barcode_queue` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NULL,`product_id` INT UNSIGNED NOT NULL,`variation_id` INT UNSIGNED NULL,`template_code` VARCHAR(80) NULL,`qty` DECIMAL(22,3) NOT NULL DEFAULT 1.000,`status` VARCHAR(30) NOT NULL DEFAULT 'pending',`created_by` INT UNSIGNED NULL,`printed_at` TIMESTAMP NULL DEFAULT NULL,`created_at` TIMESTAMP NULL DEFAULT NULL,`updated_at` TIMESTAMP NULL DEFAULT NULL,PRIMARY KEY (`id`),KEY `products_new_barcode_queue_business_idx` (`business_id`),KEY `products_new_barcode_queue_product_idx` (`product_id`),KEY `products_new_barcode_queue_status_idx` (`status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Statement 19
-- ============================================================
-- STAGE 004 - STOCK & PRICE CENTER
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

-- Statement 20
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

-- Statement 21
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

-- Statement 22
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

-- Statement 25
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

-- Statement 31
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

-- Statement 33
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

-- Statement 34
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

-- Statement 35
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

-- Statement 36
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

-- Statement 37
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

-- Statement 39
-- =========================================================
-- Stage 007 Batch / Lot / Expiry Centre
-- =========================================================

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

-- Statement 40
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

-- Statement 41
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

-- Statement 42
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

-- Statement 44
-- =============================================================
-- STAGE 008 - SERIAL, WARRANTY AND OWNERSHIP CENTRE
-- =============================================================

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

-- Statement 45
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

-- Statement 46
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

-- Statement 47
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

-- Statement 48
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

-- Statement 50
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

-- Statement 51
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

-- Statement 52
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

-- Statement 53
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

-- Statement 54
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

-- Statement 55
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

-- Statement 58
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

-- Statement 59
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

-- Statement 73
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

-- Statement 74
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

-- Statement 81
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

-- Statement 82
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

-- Statement 83
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

-- Statement 91
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

-- Statement 92
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

-- Statement 93
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

-- Statement 94
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

-- Statement 95
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

-- Statement 96
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

-- Statement 97
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

-- Statement 98
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

-- Statement 100
-- =====================================================================
-- PRODUCTSNEW_017 - Product Command Center
-- =====================================================================
CREATE TABLE IF NOT EXISTS `products_new_command_center_preferences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `visible_widgets` JSON NULL,
  `quick_actions` JSON NULL,
  `saved_searches` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pcc_preferences_business_user_idx` (`business_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Statement 101
CREATE TABLE IF NOT EXISTS `products_new_command_center_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `summary_payload` JSON NULL,
  `inventory_payload` JSON NULL,
  `finance_payload` JSON NULL,
  `sales_payload` JSON NULL,
  `purchase_payload` JSON NULL,
  `alerts_payload` JSON NULL,
  `integration_payload` JSON NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pcc_snapshots_business_product_idx` (`business_id`, `product_id`),
  KEY `pcc_snapshots_created_by_idx` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
