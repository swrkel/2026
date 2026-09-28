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
