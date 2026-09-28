CREATE TABLE IF NOT EXISTS disnew_customer_portal_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id INT UNSIGNED NOT NULL,
  location_id INT UNSIGNED NULL,
  customer_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  portal_code VARCHAR(50) NOT NULL UNIQUE,
  mobile VARCHAR(30) NULL,
  email VARCHAR(255) NULL,
  can_place_order TINYINT(1) NOT NULL DEFAULT 1,
  can_view_invoice TINYINT(1) NOT NULL DEFAULT 1,
  can_view_statement TINYINT(1) NOT NULL DEFAULT 1,
  can_request_return TINYINT(1) NOT NULL DEFAULT 1,
  can_raise_complaint TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  INDEX idx_disnew_cpu_business (business_id),
  INDEX idx_disnew_cpu_location (location_id),
  INDEX idx_disnew_cpu_customer (customer_id)
);

CREATE TABLE IF NOT EXISTS disnew_customer_return_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id INT UNSIGNED NOT NULL,
  location_id INT UNSIGNED NULL,
  customer_id INT UNSIGNED NOT NULL,
  sales_order_id BIGINT UNSIGNED NULL,
  sales_invoice_id BIGINT UNSIGNED NULL,
  request_no VARCHAR(60) NOT NULL,
  request_date DATE NOT NULL,
  status ENUM('draft','submitted','approved','rejected','picked','credited','closed') NOT NULL DEFAULT 'submitted',
  reason TEXT NULL,
  customer_note TEXT NULL,
  internal_note TEXT NULL,
  created_by INT UNSIGNED NULL,
  approved_by INT UNSIGNED NULL,
  approved_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  INDEX idx_disnew_crr_business (business_id),
  INDEX idx_disnew_crr_customer (customer_id),
  INDEX idx_disnew_crr_status (status),
  INDEX idx_disnew_crr_request_no (request_no)
);

CREATE TABLE IF NOT EXISTS disnew_customer_return_request_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  return_request_id BIGINT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  approved_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  unit_price DECIMAL(22,4) NOT NULL DEFAULT 0,
  line_note TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_disnew_crrl_request (return_request_id),
  INDEX idx_disnew_crrl_product (product_id)
);

CREATE TABLE IF NOT EXISTS disnew_customer_complaints (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id INT UNSIGNED NOT NULL,
  location_id INT UNSIGNED NULL,
  customer_id INT UNSIGNED NOT NULL,
  complaint_no VARCHAR(60) NOT NULL,
  category ENUM('delivery','invoice','product','payment','service','other') NOT NULL DEFAULT 'other',
  priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  status ENUM('open','assigned','in_progress','resolved','closed','cancelled') NOT NULL DEFAULT 'open',
  subject VARCHAR(255) NOT NULL,
  description TEXT NULL,
  assigned_to INT UNSIGNED NULL,
  resolved_by INT UNSIGNED NULL,
  resolved_at TIMESTAMP NULL,
  resolution_note TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  INDEX idx_disnew_cc_business (business_id),
  INDEX idx_disnew_cc_customer (customer_id),
  INDEX idx_disnew_cc_status (status),
  INDEX idx_disnew_cc_no (complaint_no)
);

CREATE TABLE IF NOT EXISTS disnew_customer_delivery_tracking_views (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  delivery_id BIGINT UNSIGNED NULL,
  sales_order_id BIGINT UNSIGNED NULL,
  viewed_by INT UNSIGNED NULL,
  viewed_at TIMESTAMP NULL,
  ip_address VARCHAR(80) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_disnew_cdtv_business (business_id),
  INDEX idx_disnew_cdtv_customer (customer_id),
  INDEX idx_disnew_cdtv_delivery (delivery_id)
);

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('distributionnew.customer_portal.view','web',NOW(),NOW()),
('distributionnew.customer_portal.order_create','web',NOW(),NOW()),
('distributionnew.customer_portal.invoice_view','web',NOW(),NOW()),
('distributionnew.customer_portal.statement_view','web',NOW(),NOW()),
('distributionnew.customer_portal.delivery_track','web',NOW(),NOW()),
('distributionnew.customer_portal.return_request','web',NOW(),NOW()),
('distributionnew.customer_portal.complaint_create','web',NOW(),NOW());
