-- DISNEW_018 Warehouse Scanner + Barcode/QR Operations
CREATE TABLE IF NOT EXISTS disnew_barcode_profiles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(191) NOT NULL,
 profile_type ENUM('barcode','qr','both') DEFAULT 'both',
 prefix VARCHAR(50) NULL,
 is_default TINYINT(1) DEFAULT 0,
 is_active TINYINT(1) DEFAULT 1,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX disnew_barcode_profiles_business_idx (business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_barcode_labels (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 business_location_id BIGINT UNSIGNED NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 variation_id BIGINT UNSIGNED NULL,
 batch_no VARCHAR(100) NULL,
 barcode_value VARCHAR(191) NOT NULL,
 qr_value TEXT NULL,
 label_status ENUM('active','void','used') DEFAULT 'active',
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 UNIQUE KEY disnew_barcode_labels_barcode_unique (barcode_value),
 INDEX disnew_barcode_labels_lookup_idx (business_id, product_id, batch_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_scanner_sessions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 business_location_id BIGINT UNSIGNED NULL,
 session_type ENUM('loading','unloading','bin','verification','dispatch','delivery') NOT NULL,
 reference_type VARCHAR(80) NULL,
 reference_id BIGINT UNSIGNED NULL,
 vehicle_id BIGINT UNSIGNED NULL,
 warehouse_id BIGINT UNSIGNED NULL,
 status ENUM('open','closed','cancelled') DEFAULT 'open',
 started_by BIGINT UNSIGNED NULL,
 started_at DATETIME NULL,
 closed_at DATETIME NULL,
 notes TEXT NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX disnew_scanner_sessions_ref_idx (business_id, session_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_scanner_scans (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 scanner_session_id BIGINT UNSIGNED NULL,
 resolved_label_id BIGINT UNSIGNED NULL,
 barcode_value VARCHAR(191) NOT NULL,
 product_id BIGINT UNSIGNED NULL,
 variation_id BIGINT UNSIGNED NULL,
 batch_no VARCHAR(100) NULL,
 qty DECIMAL(22,4) DEFAULT 1.0000,
 scan_status ENUM('accepted','duplicate','exception','reversed') DEFAULT 'accepted',
 exception_reason VARCHAR(191) NULL,
 scanned_by BIGINT UNSIGNED NULL,
 scanned_at DATETIME NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX disnew_scanner_scans_session_idx (scanner_session_id),
 INDEX disnew_scanner_scans_barcode_idx (barcode_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_bin_locations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 business_location_id BIGINT UNSIGNED NULL,
 warehouse_id BIGINT UNSIGNED NULL,
 code VARCHAR(80) NOT NULL,
 name VARCHAR(191) NOT NULL,
 aisle VARCHAR(80) NULL,
 rack VARCHAR(80) NULL,
 shelf VARCHAR(80) NULL,
 capacity_qty DECIMAL(22,4) NULL,
 is_active TINYINT(1) DEFAULT 1,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 UNIQUE KEY disnew_bin_locations_code_unique (business_id, warehouse_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_bin_stock_movements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 business_location_id BIGINT UNSIGNED NULL,
 warehouse_id BIGINT UNSIGNED NULL,
 bin_location_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 variation_id BIGINT UNSIGNED NULL,
 batch_no VARCHAR(100) NULL,
 qty DECIMAL(22,4) NOT NULL,
 movement_type ENUM('in','out','return','adjustment') NOT NULL,
 reference_type VARCHAR(80) NULL,
 reference_id BIGINT UNSIGNED NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX disnew_bin_stock_movements_bal_idx (business_id, bin_location_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_stock_verifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 business_location_id BIGINT UNSIGNED NULL,
 warehouse_id BIGINT UNSIGNED NULL,
 vehicle_id BIGINT UNSIGNED NULL,
 verification_no VARCHAR(80) NULL,
 verification_type ENUM('warehouse','vehicle','bin') DEFAULT 'warehouse',
 status ENUM('draft','counting','submitted','approved','cancelled') DEFAULT 'draft',
 counted_by BIGINT UNSIGNED NULL,
 approved_by BIGINT UNSIGNED NULL,
 started_at DATETIME NULL,
 submitted_at DATETIME NULL,
 approved_at DATETIME NULL,
 notes TEXT NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX disnew_stock_verifications_business_idx (business_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_stock_verification_lines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 stock_verification_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 variation_id BIGINT UNSIGNED NULL,
 batch_no VARCHAR(100) NULL,
 system_qty DECIMAL(22,4) DEFAULT 0.0000,
 counted_qty DECIMAL(22,4) DEFAULT 0.0000,
 variance_qty DECIMAL(22,4) DEFAULT 0.0000,
 last_barcode_value VARCHAR(191) NULL,
 remarks TEXT NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX disnew_stock_verification_lines_parent_idx (stock_verification_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permissions seed names used by menu/routes:
-- disnew.scanner.view, disnew.scanner.loading, disnew.scanner.unloading, disnew.bins.view, disnew.stock.verify, disnew.labels.manage
