-- Dealer Management - Master SQL
-- Standalone tenant-safe module. Prefix: dlr_
-- Run in every tenant/central business database where Dealer Management will be used.

CREATE TABLE IF NOT EXISTS dlr_dealers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NULL,
  dealer_code VARCHAR(30) NOT NULL,
  name VARCHAR(191) NOT NULL,
  mobile VARCHAR(50) NULL,
  email VARCHAR(191) NULL,
  address TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY dlr_dealers_business_code_unique (business_id, dealer_code),
  KEY dlr_dealers_customer_idx (business_id, customer_id),
  KEY dlr_dealers_status_idx (business_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_outlets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  dealer_id BIGINT UNSIGNED NOT NULL,
  outlet_code VARCHAR(30) NOT NULL,
  name VARCHAR(191) NOT NULL,
  address TEXT NULL,
  mobile VARCHAR(50) NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  notes TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY dlr_outlets_dealer_code_unique (dealer_id, outlet_code),
  KEY dlr_outlets_business_dealer_idx (business_id, dealer_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_roles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  dealer_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  is_system TINYINT(1) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY dlr_roles_dealer_name_unique (dealer_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_role_permissions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id BIGINT UNSIGNED NOT NULL,
  permission_key VARCHAR(120) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY dlr_role_permission_unique (role_id, permission_key),
  KEY dlr_role_permissions_role_idx (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  dealer_id BIGINT UNSIGNED NOT NULL,
  role_id BIGINT UNSIGNED NULL,
  name VARCHAR(191) NOT NULL,
  login_code CHAR(4) NOT NULL,
  email VARCHAR(191) NULL,
  mobile VARCHAR(50) NULL,
  password VARCHAR(255) NOT NULL,
  is_dealer_admin TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  must_change_password TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  password_reset_at DATETIME NULL,
  notes TEXT NULL,
  created_by_dealer_user_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY dlr_users_business_login_code_unique (business_id, login_code),
  KEY dlr_users_dealer_idx (business_id, dealer_id, is_active),
  KEY dlr_users_role_idx (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_user_outlets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  dealer_user_id BIGINT UNSIGNED NOT NULL,
  outlet_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY dlr_user_outlet_unique (dealer_user_id, outlet_id),
  KEY dlr_user_outlets_outlet_idx (outlet_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_stock_balances (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  dealer_id BIGINT UNSIGNED NOT NULL,
  outlet_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  variation_id BIGINT UNSIGNED NULL,
  product_name VARCHAR(191) NULL,
  system_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  confirmed_qty DECIMAL(22,4) NULL,
  effective_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  last_confirmed_at DATETIME NULL,
  last_movement_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY dlr_stock_balance_unique (business_id, dealer_id, outlet_id, product_id, variation_id),
  KEY dlr_stock_balance_dealer_idx (business_id, dealer_id, outlet_id),
  KEY dlr_stock_balance_product_idx (product_id, variation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_stock_movements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  dealer_id BIGINT UNSIGNED NOT NULL,
  outlet_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  variation_id BIGINT UNSIGNED NULL,
  product_name VARCHAR(191) NULL,
  movement_type VARCHAR(40) NOT NULL,
  direction ENUM('in','out','set') NOT NULL,
  qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  qty_before DECIMAL(22,4) NOT NULL DEFAULT 0,
  qty_after DECIMAL(22,4) NOT NULL DEFAULT 0,
  reference_type VARCHAR(80) NULL,
  reference_id BIGINT UNSIGNED NULL,
  reference_no VARCHAR(80) NULL,
  notes TEXT NULL,
  created_by_type VARCHAR(30) NULL,
  created_by_id BIGINT UNSIGNED NULL,
  movement_at DATETIME NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY dlr_stock_movements_scope_idx (business_id, dealer_id, outlet_id, product_id),
  KEY dlr_stock_movements_reference_idx (reference_type, reference_id),
  KEY dlr_stock_movements_date_idx (movement_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_stock_updates (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  dealer_id BIGINT UNSIGNED NOT NULL,
  outlet_id BIGINT UNSIGNED NOT NULL,
  update_no VARCHAR(60) NOT NULL,
  update_date DATE NOT NULL,
  notes TEXT NULL,
  submitted_by BIGINT UNSIGNED NOT NULL,
  submitted_at DATETIME NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY dlr_stock_updates_business_no_unique (business_id, update_no),
  KEY dlr_stock_updates_scope_idx (business_id, dealer_id, outlet_id, update_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_stock_update_lines (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  stock_update_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  variation_id BIGINT UNSIGNED NULL,
  system_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  physical_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  variance_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  sold_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  damaged_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY dlr_stock_update_lines_update_idx (stock_update_id),
  KEY dlr_stock_update_lines_product_idx (product_id, variation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_reorder_rules (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  dealer_id BIGINT UNSIGNED NOT NULL,
  outlet_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  variation_id BIGINT UNSIGNED NULL,
  min_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  max_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  reorder_level DECIMAL(22,4) NOT NULL DEFAULT 0,
  safety_stock_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  stock_cover_days INT UNSIGNED NOT NULL DEFAULT 7,
  avg_daily_usage DECIMAL(22,4) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY dlr_reorder_rule_unique (business_id, dealer_id, outlet_id, product_id, variation_id),
  KEY dlr_reorder_rules_scope_idx (business_id, dealer_id, outlet_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_orders (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  dealer_id BIGINT UNSIGNED NOT NULL,
  outlet_id BIGINT UNSIGNED NOT NULL,
  order_no VARCHAR(60) NOT NULL,
  order_date DATE NOT NULL,
  requested_delivery_date DATE NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'draft',
  source VARCHAR(30) NOT NULL DEFAULT 'dealer_portal',
  notes TEXT NULL,
  submitted_by BIGINT UNSIGNED NULL,
  submitted_at DATETIME NULL,
  distribution_sales_order_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY dlr_orders_business_no_unique (business_id, order_no),
  KEY dlr_orders_scope_idx (business_id, dealer_id, outlet_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_order_lines (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  variation_id BIGINT UNSIGNED NULL,
  product_name VARCHAR(191) NULL,
  current_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  suggested_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  requested_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY dlr_order_lines_order_idx (order_id),
  KEY dlr_order_lines_product_idx (product_id, variation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  dealer_id BIGINT UNSIGNED NOT NULL,
  outlet_id BIGINT UNSIGNED NULL,
  dealer_user_id BIGINT UNSIGNED NULL,
  type VARCHAR(50) NOT NULL,
  severity VARCHAR(20) NOT NULL DEFAULT 'info',
  title VARCHAR(191) NOT NULL,
  message TEXT NOT NULL,
  action_url VARCHAR(500) NULL,
  reference_type VARCHAR(80) NULL,
  reference_id BIGINT UNSIGNED NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  read_at DATETIME NULL,
  expires_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY dlr_notifications_scope_idx (business_id, dealer_id, is_read),
  KEY dlr_notifications_type_idx (type, severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_integration_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  event_key VARCHAR(191) NOT NULL,
  source_module VARCHAR(80) NOT NULL,
  source_type VARCHAR(80) NOT NULL,
  source_id BIGINT UNSIGNED NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'processed',
  payload_json JSON NULL,
  processed_at DATETIME NULL,
  error_message TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY dlr_integration_event_key_unique (event_key),
  KEY dlr_integration_events_business_idx (business_id, source_module, source_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_integration_outbox (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  dealer_id BIGINT UNSIGNED NOT NULL,
  event_type VARCHAR(80) NOT NULL,
  aggregate_type VARCHAR(80) NOT NULL,
  aggregate_id BIGINT UNSIGNED NOT NULL,
  payload_json JSON NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  processed_at DATETIME NULL,
  last_error TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY dlr_integration_outbox_status_idx (business_id, status, event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  dealer_id BIGINT UNSIGNED NULL,
  dealer_user_id BIGINT UNSIGNED NULL,
  event VARCHAR(100) NOT NULL,
  entity_type VARCHAR(100) NULL,
  entity_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(64) NULL,
  user_agent VARCHAR(500) NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY dlr_audit_logs_scope_idx (business_id, dealer_id, event),
  KEY dlr_audit_logs_entity_idx (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
