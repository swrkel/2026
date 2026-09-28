
-- ===== DISNEW_001_STAGE1_FOUNDATION.sql =====
-- Distribution New Stage 1 SQL only
-- Prefix: disnew_

CREATE TABLE IF NOT EXISTS disnew_number_sequences (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(80) NOT NULL,
  prefix VARCHAR(30) NULL,
  next_number BIGINT UNSIGNED NOT NULL DEFAULT 1,
  padding INT UNSIGNED NOT NULL DEFAULT 6,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY disnew_number_sequences_business_type_unique (business_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_sales_orders (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  sales_order_no VARCHAR(60) NOT NULL,
  source ENUM('user','sales_rep','customer') NOT NULL DEFAULT 'user',
  customer_id BIGINT UNSIGNED NULL,
  sales_rep_id BIGINT UNSIGNED NULL,
  order_date DATE NOT NULL,
  delivery_date DATE NULL,
  status ENUM('draft','confirmed','loaded','partially_invoiced','invoiced','delivered','cancelled') NOT NULL DEFAULT 'draft',
  subtotal DECIMAL(22,4) NOT NULL DEFAULT 0,
  discount_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  tax_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  grand_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  note TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY disnew_sales_orders_business_no_unique (business_id, sales_order_no),
  KEY disnew_sales_orders_business_status_idx (business_id, status),
  KEY disnew_sales_orders_customer_idx (customer_id),
  KEY disnew_sales_orders_location_idx (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_sales_order_lines (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sales_order_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NULL,
  product_name VARCHAR(191) NULL,
  qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  unit_price DECIMAL(22,4) NOT NULL DEFAULT 0,
  discount DECIMAL(22,4) NOT NULL DEFAULT 0,
  tax DECIMAL(22,4) NOT NULL DEFAULT 0,
  line_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY disnew_sales_order_lines_order_idx (sales_order_id),
  KEY disnew_sales_order_lines_product_idx (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_sales_invoices (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  sales_order_id BIGINT UNSIGNED NOT NULL,
  invoice_no VARCHAR(60) NOT NULL,
  invoice_date DATE NOT NULL,
  customer_id BIGINT UNSIGNED NULL,
  sales_rep_id BIGINT UNSIGNED NULL,
  status ENUM('issued','paid','partially_paid','cancelled') NOT NULL DEFAULT 'issued',
  subtotal DECIMAL(22,4) NOT NULL DEFAULT 0,
  discount_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  tax_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  grand_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY disnew_sales_invoices_business_no_unique (business_id, invoice_no),
  KEY disnew_sales_invoices_order_idx (sales_order_id),
  KEY disnew_sales_invoices_business_status_idx (business_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_sales_invoice_lines (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sales_invoice_id BIGINT UNSIGNED NOT NULL,
  sales_order_line_id BIGINT UNSIGNED NULL,
  product_id BIGINT UNSIGNED NULL,
  product_name VARCHAR(191) NULL,
  qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  unit_price DECIMAL(22,4) NOT NULL DEFAULT 0,
  discount DECIMAL(22,4) NOT NULL DEFAULT 0,
  tax DECIMAL(22,4) NOT NULL DEFAULT 0,
  line_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY disnew_sales_invoice_lines_invoice_idx (sales_invoice_id),
  KEY disnew_sales_invoice_lines_product_idx (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_loadings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  loading_no VARCHAR(60) NOT NULL,
  vehicle_id BIGINT UNSIGNED NULL,
  driver_id BIGINT UNSIGNED NULL,
  status ENUM('draft','completed','cancelled') NOT NULL DEFAULT 'draft',
  completed_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY disnew_loadings_business_no_unique (business_id, loading_no),
  KEY disnew_loadings_vehicle_idx (vehicle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_loading_lines (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  loading_id BIGINT UNSIGNED NOT NULL,
  sales_order_id BIGINT UNSIGNED NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY disnew_loading_lines_loading_idx (loading_id),
  KEY disnew_loading_lines_order_idx (sales_order_id),
  KEY disnew_loading_lines_product_idx (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_unloadings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  unloading_no VARCHAR(60) NOT NULL,
  vehicle_id BIGINT UNSIGNED NULL,
  status ENUM('draft','completed','cancelled') NOT NULL DEFAULT 'draft',
  completed_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY disnew_unloadings_business_no_unique (business_id, unloading_no),
  KEY disnew_unloadings_vehicle_idx (vehicle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_vehicle_stocks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  vehicle_id BIGINT UNSIGNED NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY disnew_vehicle_stock_unique (business_id, location_id, vehicle_id, product_id),
  KEY disnew_vehicle_stock_product_idx (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_stock_movements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  vehicle_id BIGINT UNSIGNED NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  direction ENUM('in','out') NOT NULL,
  qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  reference_type VARCHAR(80) NULL,
  reference_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY disnew_stock_movements_business_idx (business_id, location_id),
  KEY disnew_stock_movements_vehicle_idx (vehicle_id),
  KEY disnew_stock_movements_reference_idx (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_sms_officers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(191) NULL,
  mobile VARCHAR(50) NOT NULL,
  event_mask JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY disnew_sms_officers_business_idx (business_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_sms_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  event VARCHAR(80) NOT NULL,
  customer_id BIGINT UNSIGNED NULL,
  officer_name VARCHAR(191) NULL,
  mobile VARCHAR(50) NULL,
  message TEXT NOT NULL,
  payload_json JSON NULL,
  status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
  error_message TEXT NULL,
  sent_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY disnew_sms_logs_business_event_idx (business_id, event),
  KEY disnew_sms_logs_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== DISNEW_002_SMS_ORDER_INVOICE_STOCK.sql =====
-- Distribution New Stage 2 SQL only
-- Prefix: disnew_
-- Purpose: SMS bridge, sales order edit workflow, invoice-from-order, vehicle/store stock controls.

CREATE TABLE IF NOT EXISTS disnew_sms_templates (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  event VARCHAR(80) NOT NULL,
  title VARCHAR(191) NOT NULL,
  message_template TEXT NOT NULL,
  send_to_customer TINYINT(1) NOT NULL DEFAULT 1,
  send_to_officers TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY disnew_sms_templates_business_event_unique (business_id, event),
  KEY disnew_sms_templates_business_active_idx (business_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_sms_bridge_attempts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  disnew_sms_log_id BIGINT UNSIGNED NOT NULL,
  bridge_driver VARCHAR(80) NOT NULL DEFAULT 'existing_sms_module',
  external_reference VARCHAR(191) NULL,
  status ENUM('pending','pushed','failed','skipped') NOT NULL DEFAULT 'pending',
  request_payload JSON NULL,
  response_payload JSON NULL,
  error_message TEXT NULL,
  attempted_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY disnew_sms_bridge_log_idx (disnew_sms_log_id),
  KEY disnew_sms_bridge_status_idx (business_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_sales_order_status_histories (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  sales_order_id BIGINT UNSIGNED NOT NULL,
  old_status VARCHAR(60) NULL,
  new_status VARCHAR(60) NOT NULL,
  note TEXT NULL,
  changed_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY disnew_so_status_order_idx (sales_order_id),
  KEY disnew_so_status_business_idx (business_id, new_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_sales_order_edit_locks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  sales_order_id BIGINT UNSIGNED NOT NULL,
  locked_by BIGINT UNSIGNED NULL,
  locked_reason VARCHAR(191) NULL,
  locked_at DATETIME NOT NULL,
  released_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY disnew_so_edit_locks_order_idx (sales_order_id),
  KEY disnew_so_edit_locks_active_idx (business_id, released_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_vehicle_store_stocks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  store_id BIGINT UNSIGNED NULL,
  vehicle_id BIGINT UNSIGNED NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  reserved_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY disnew_vehicle_store_stock_unique (business_id, location_id, store_id, vehicle_id, product_id),
  KEY disnew_vehicle_store_stock_product_idx (product_id),
  KEY disnew_vehicle_store_stock_vehicle_idx (vehicle_id),
  KEY disnew_vehicle_store_stock_store_idx (store_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_loading_status_histories (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  loading_id BIGINT UNSIGNED NOT NULL,
  old_status VARCHAR(60) NULL,
  new_status VARCHAR(60) NOT NULL,
  note TEXT NULL,
  changed_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY disnew_loading_history_loading_idx (loading_id),
  KEY disnew_loading_history_business_idx (business_id, new_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE disnew_sms_logs
  ADD COLUMN IF NOT EXISTS template_id BIGINT UNSIGNED NULL AFTER event,
  ADD COLUMN IF NOT EXISTS bridge_status ENUM('pending','pushed','failed','skipped') NOT NULL DEFAULT 'pending' AFTER status,
  ADD COLUMN IF NOT EXISTS external_reference VARCHAR(191) NULL AFTER bridge_status;

ALTER TABLE disnew_sales_invoices
  ADD COLUMN IF NOT EXISTS sms_sent_at DATETIME NULL AFTER grand_total,
  ADD COLUMN IF NOT EXISTS source_type VARCHAR(80) NULL AFTER sms_sent_at;

ALTER TABLE disnew_sales_orders
  ADD COLUMN IF NOT EXISTS last_sms_sent_at DATETIME NULL AFTER grand_total,
  ADD COLUMN IF NOT EXISTS editable_until_status VARCHAR(60) NULL AFTER last_sms_sent_at;


-- ===== DISNEW_003_LOADING_UNLOADING_VEHICLE_STOCK.sql =====
-- Distribution New Stage 3 SQL only
-- Prefix: disnew_
-- Purpose: loading plans, loading/unloading workflow hardening, vehicle+store stock tracking, delivery issue logs.

CREATE TABLE IF NOT EXISTS disnew_loading_plans (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  plan_no VARCHAR(60) NOT NULL,
  plan_date DATE NOT NULL,
  vehicle_id BIGINT UNSIGNED NULL,
  driver_id BIGINT UNSIGNED NULL,
  sales_rep_id BIGINT UNSIGNED NULL,
  status ENUM('draft','approved','converted_to_loading','cancelled') NOT NULL DEFAULT 'draft',
  note TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  approved_by BIGINT UNSIGNED NULL,
  approved_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY disnew_loading_plans_business_no_unique (business_id, plan_no),
  KEY disnew_loading_plans_business_status_idx (business_id, status),
  KEY disnew_loading_plans_vehicle_idx (vehicle_id),
  KEY disnew_loading_plans_sales_rep_idx (sales_rep_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_loading_plan_lines (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  loading_plan_id BIGINT UNSIGNED NOT NULL,
  sales_order_id BIGINT UNSIGNED NULL,
  sales_order_line_id BIGINT UNSIGNED NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  product_name VARCHAR(191) NULL,
  ordered_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  planned_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  loaded_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  delivered_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  returned_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  damaged_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY disnew_loading_plan_lines_plan_idx (loading_plan_id),
  KEY disnew_loading_plan_lines_order_idx (sales_order_id),
  KEY disnew_loading_plan_lines_product_idx (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_vehicle_store_stocks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  store_id BIGINT UNSIGNED NULL,
  vehicle_id BIGINT UNSIGNED NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY disnew_vehicle_store_stock_unique (business_id, location_id, store_id, vehicle_id, product_id),
  KEY disnew_vehicle_store_stock_product_idx (product_id),
  KEY disnew_vehicle_store_stock_vehicle_idx (vehicle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_delivery_issue_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  sales_order_id BIGINT UNSIGNED NULL,
  loading_id BIGINT UNSIGNED NULL,
  unloading_id BIGINT UNSIGNED NULL,
  vehicle_id BIGINT UNSIGNED NULL,
  issue_type ENUM('short_loading','excess_loading','short_delivery','excess_return','damage','customer_reject','other') NOT NULL DEFAULT 'other',
  product_id BIGINT UNSIGNED NULL,
  qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  note TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY disnew_delivery_issue_business_idx (business_id, issue_type),
  KEY disnew_delivery_issue_order_idx (sales_order_id),
  KEY disnew_delivery_issue_vehicle_idx (vehicle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE disnew_loadings
  ADD COLUMN IF NOT EXISTS loading_plan_id BIGINT UNSIGNED NULL AFTER id,
  ADD COLUMN IF NOT EXISTS store_id BIGINT UNSIGNED NULL AFTER location_id,
  ADD COLUMN IF NOT EXISTS loaded_by BIGINT UNSIGNED NULL AFTER created_by,
  ADD COLUMN IF NOT EXISTS loaded_at DATETIME NULL AFTER completed_at,
  ADD INDEX IF NOT EXISTS disnew_loadings_plan_idx (loading_plan_id),
  ADD INDEX IF NOT EXISTS disnew_loadings_store_vehicle_idx (store_id, vehicle_id);

ALTER TABLE disnew_loading_lines
  ADD COLUMN IF NOT EXISTS loading_plan_line_id BIGINT UNSIGNED NULL AFTER loading_id,
  ADD COLUMN IF NOT EXISTS product_name VARCHAR(191) NULL AFTER product_id,
  ADD COLUMN IF NOT EXISTS planned_qty DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER product_name,
  ADD COLUMN IF NOT EXISTS loaded_qty DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER qty,
  ADD COLUMN IF NOT EXISTS variance_qty DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER loaded_qty,
  ADD INDEX IF NOT EXISTS disnew_loading_lines_plan_line_idx (loading_plan_line_id);

ALTER TABLE disnew_unloadings
  ADD COLUMN IF NOT EXISTS loading_id BIGINT UNSIGNED NULL AFTER id,
  ADD COLUMN IF NOT EXISTS store_id BIGINT UNSIGNED NULL AFTER location_id,
  ADD COLUMN IF NOT EXISTS unloaded_by BIGINT UNSIGNED NULL AFTER created_by,
  ADD COLUMN IF NOT EXISTS unloaded_at DATETIME NULL AFTER completed_at,
  ADD INDEX IF NOT EXISTS disnew_unloadings_loading_idx (loading_id),
  ADD INDEX IF NOT EXISTS disnew_unloadings_store_vehicle_idx (store_id, vehicle_id);

CREATE TABLE IF NOT EXISTS disnew_unloading_lines (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  unloading_id BIGINT UNSIGNED NOT NULL,
  loading_line_id BIGINT UNSIGNED NULL,
  sales_order_id BIGINT UNSIGNED NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  product_name VARCHAR(191) NULL,
  loaded_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  delivered_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  returned_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  damaged_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  shortage_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  note TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY disnew_unloading_lines_unloading_idx (unloading_id),
  KEY disnew_unloading_lines_loading_line_idx (loading_line_id),
  KEY disnew_unloading_lines_product_idx (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== DISNEW_004_VEHICLES_AND_SUPERADMIN_LIMIT.sql =====
/* DISNEW_004 - Vehicles and Super Admin Vehicle Limit
   Global tenant-safe SQL. Run against each tenant database that needs Distribution New.
*/

CREATE TABLE IF NOT EXISTS `disnew_vehicles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `vehicle_no` VARCHAR(50) NOT NULL,
  `vehicle_name` VARCHAR(100) NULL,
  `vehicle_type` VARCHAR(50) NULL,
  `capacity_qty` DECIMAL(22,4) NULL DEFAULT 0.0000,
  `capacity_volume` DECIMAL(22,4) NULL DEFAULT 0.0000,
  `driver_name` VARCHAR(100) NULL,
  `driver_mobile` VARCHAR(30) NULL,
  `helper_name` VARCHAR(100) NULL,
  `helper_mobile` VARCHAR(30) NULL,
  `status` ENUM('active','maintenance','inactive') NOT NULL DEFAULT 'active',
  `note` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_vehicles_business_vehicle_no_unique` (`business_id`, `vehicle_no`),
  KEY `disnew_vehicles_business_location_index` (`business_id`, `business_location_id`),
  KEY `disnew_vehicles_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_vehicle_limits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `vehicle_limit` INT UNSIGNED NULL,
  `allow_unlimited` TINYINT(1) NOT NULL DEFAULT 0,
  `note` VARCHAR(255) NULL,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_vehicle_limits_business_unique` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `disnew_loading_plans`
  ADD COLUMN IF NOT EXISTS `vehicle_id` BIGINT UNSIGNED NULL AFTER `business_location_id`,
  ADD KEY IF NOT EXISTS `disnew_loading_plans_vehicle_id_index` (`vehicle_id`);

ALTER TABLE `disnew_loadings`
  ADD COLUMN IF NOT EXISTS `vehicle_id` BIGINT UNSIGNED NULL AFTER `business_location_id`,
  ADD KEY IF NOT EXISTS `disnew_loadings_vehicle_id_index` (`vehicle_id`);

ALTER TABLE `disnew_vehicle_stock`
  ADD COLUMN IF NOT EXISTS `vehicle_id` BIGINT UNSIGNED NULL AFTER `business_location_id`,
  ADD KEY IF NOT EXISTS `disnew_vehicle_stock_vehicle_id_index` (`vehicle_id`);


-- ===== DISNEW_005_ROUTES_SALESREP_CUSTOMER_DELIVERY.sql =====
-- DISNEW_005 - Routes, territories, sales reps, customer ordering and delivery proof
-- Run inside each tenant database. No database name is hard-coded.

CREATE TABLE IF NOT EXISTS `disnew_territories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `code` VARCHAR(50) NULL,
  `name` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_territories_business_idx` (`business_id`,`business_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_routes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `disnew_territory_id` BIGINT UNSIGNED NULL,
  `route_code` VARCHAR(50) NOT NULL,
  `route_name` VARCHAR(191) NOT NULL,
  `default_vehicle_id` BIGINT UNSIGNED NULL,
  `default_sales_rep_id` BIGINT UNSIGNED NULL,
  `visit_day` VARCHAR(50) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_routes_business_idx` (`business_id`,`business_location_id`),
  KEY `disnew_routes_territory_idx` (`disnew_territory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_route_customers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `disnew_route_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `visit_sequence` INT UNSIGNED NULL,
  `default_visit_time` VARCHAR(50) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_route_customer_unique` (`disnew_route_id`,`customer_id`),
  KEY `disnew_route_customers_customer_idx` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_sales_reps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `user_id` INT UNSIGNED NULL,
  `sales_rep_code` VARCHAR(50) NOT NULL,
  `sales_rep_name` VARCHAR(191) NOT NULL,
  `mobile` VARCHAR(50) NULL,
  `default_route_id` BIGINT UNSIGNED NULL,
  `default_vehicle_id` BIGINT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_sales_reps_business_idx` (`business_id`,`business_location_id`),
  KEY `disnew_sales_reps_user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_customer_access_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `mobile` VARCHAR(50) NULL,
  `token` VARCHAR(191) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `is_used` TINYINT(1) NOT NULL DEFAULT 0,
  `used_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_customer_access_token_unique` (`token`),
  KEY `disnew_customer_access_customer_idx` (`business_id`,`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_deliveries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `delivery_no` VARCHAR(80) NULL,
  `disnew_sales_order_id` BIGINT UNSIGNED NULL,
  `disnew_sales_invoice_id` BIGINT UNSIGNED NULL,
  `customer_id` INT UNSIGNED NULL,
  `disnew_vehicle_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `delivery_date` DATE NULL,
  `delivered_at` DATETIME NULL,
  `received_by` VARCHAR(191) NULL,
  `receiver_mobile` VARCHAR(50) NULL,
  `proof_note` TEXT NULL,
  `gps_lat` DECIMAL(12,8) NULL,
  `gps_lng` DECIMAL(12,8) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_deliveries_business_idx` (`business_id`,`business_location_id`),
  KEY `disnew_deliveries_invoice_idx` (`disnew_sales_invoice_id`),
  KEY `disnew_deliveries_vehicle_idx` (`disnew_vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_delivery_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `disnew_delivery_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `variation_id` INT UNSIGNED NULL,
  `ordered_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `loaded_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `delivered_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `returned_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `short_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_delivery_lines_delivery_idx` (`disnew_delivery_id`),
  KEY `disnew_delivery_lines_product_idx` (`product_id`,`variation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== DISNEW_006_COLLECTIONS_SETTLEMENTS_PORTALS_REPORTS.sql =====

-- DISNEW 006 - Collections, Settlements, Portals and Operational Reports
-- Run this in every tenant database. No database name is hard-coded.

CREATE TABLE IF NOT EXISTS `disnew_collections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `sales_rep_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `sales_order_id` bigint unsigned DEFAULT NULL,
  `sales_invoice_id` bigint unsigned DEFAULT NULL,
  `collection_no` varchar(50) NOT NULL,
  `collection_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'cash',
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `note` text DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_collections_no_unique` (`business_id`,`collection_no`),
  KEY `disnew_collections_business_location_idx` (`business_id`,`business_location_id`),
  KEY `disnew_collections_sales_rep_idx` (`sales_rep_id`),
  KEY `disnew_collections_invoice_idx` (`sales_invoice_id`),
  KEY `disnew_collections_customer_idx` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_settlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `sales_rep_id` bigint unsigned DEFAULT NULL,
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `settlement_no` varchar(50) NOT NULL,
  `settlement_date` date NOT NULL,
  `opening_stock_value` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `loaded_value` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `sold_value` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `returned_value` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `collection_total` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `shortage_value` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `excess_value` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `finalized_at` timestamp NULL DEFAULT NULL,
  `finalized_by` bigint unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_settlements_no_unique` (`business_id`,`settlement_no`),
  KEY `disnew_settlements_business_location_idx` (`business_id`,`business_location_id`),
  KEY `disnew_settlements_sales_rep_vehicle_idx` (`sales_rep_id`,`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_settlement_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `settlement_id` bigint unsigned NOT NULL,
  `line_type` varchar(40) NOT NULL,
  `reference_type` varchar(80) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `qty` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_settlement_lines_settlement_idx` (`settlement_id`),
  KEY `disnew_settlement_lines_ref_idx` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_order_status_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `sales_order_id` bigint unsigned NOT NULL,
  `from_status` varchar(40) DEFAULT NULL,
  `to_status` varchar(40) NOT NULL,
  `changed_by_type` varchar(40) DEFAULT NULL,
  `changed_by` bigint unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_order_status_logs_order_idx` (`sales_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_portal_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `portal_type` varchar(40) NOT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `reference_type` varchar(80) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_portal_audit_logs_business_idx` (`business_id`),
  KEY `disnew_portal_audit_logs_ref_idx` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== DISNEW_007_RETURNS_CREDIT_NOTES_STATUS_SMS_OFFICERS.sql =====
-- DISNEW_007_RETURNS_CREDIT_NOTES_STATUS_SMS_OFFICERS.sql
-- Distribution New Stage 7 - standalone additions only

CREATE TABLE IF NOT EXISTS disnew_returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    business_location_id BIGINT UNSIGNED NULL,
    sales_order_id BIGINT UNSIGNED NULL,
    sales_invoice_id BIGINT UNSIGNED NULL,
    delivery_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NULL,
    sales_rep_id BIGINT UNSIGNED NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    return_no VARCHAR(50) NOT NULL,
    return_date DATE NOT NULL,
    return_type VARCHAR(30) NOT NULL DEFAULT 'customer_return',
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    reason TEXT NULL,
    subtotal DECIMAL(22,4) NOT NULL DEFAULT 0,
    tax_total DECIMAL(22,4) NOT NULL DEFAULT 0,
    total_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY disnew_returns_business_no_unique (business_id, return_no),
    INDEX disnew_returns_business_status_idx (business_id, status),
    INDEX disnew_returns_invoice_idx (sales_invoice_id),
    INDEX disnew_returns_vehicle_idx (vehicle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_return_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    return_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variation_id BIGINT UNSIGNED NULL,
    unit_id BIGINT UNSIGNED NULL,
    qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    unit_price DECIMAL(22,4) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    line_total DECIMAL(22,4) NOT NULL DEFAULT 0,
    condition_status VARCHAR(30) NOT NULL DEFAULT 'saleable',
    stock_destination VARCHAR(30) NOT NULL DEFAULT 'store',
    note TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX disnew_return_lines_return_idx (return_id),
    INDEX disnew_return_lines_product_idx (product_id, variation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_credit_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    business_location_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NULL,
    sales_invoice_id BIGINT UNSIGNED NULL,
    return_id BIGINT UNSIGNED NULL,
    credit_note_no VARCHAR(50) NOT NULL,
    credit_note_date DATE NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    reason TEXT NULL,
    subtotal DECIMAL(22,4) NOT NULL DEFAULT 0,
    tax_total DECIMAL(22,4) NOT NULL DEFAULT 0,
    total_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    applied_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    balance_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY disnew_credit_notes_business_no_unique (business_id, credit_note_no),
    INDEX disnew_credit_notes_invoice_idx (sales_invoice_id),
    INDEX disnew_credit_notes_return_idx (return_id),
    INDEX disnew_credit_notes_customer_idx (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_credit_note_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    credit_note_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    variation_id BIGINT UNSIGNED NULL,
    description VARCHAR(255) NULL,
    qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    unit_price DECIMAL(22,4) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    line_total DECIMAL(22,4) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX disnew_credit_note_lines_note_idx (credit_note_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_sms_officer_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    business_location_id BIGINT UNSIGNED NULL,
    group_name VARCHAR(100) NOT NULL,
    event_key VARCHAR(80) NOT NULL,
    officer_user_ids TEXT NULL,
    officer_mobile_numbers TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX disnew_sms_officer_groups_event_idx (business_id, event_key, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_status_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(40) NOT NULL,
    from_status VARCHAR(40) NULL,
    to_status VARCHAR(40) NOT NULL,
    permission_name VARCHAR(120) NULL,
    requires_reason TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX disnew_status_rules_doc_idx (business_id, document_type, from_status, to_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== DISNEW_008_stage8.sql =====
-- Distribution New Stage 8 SQL only
-- Prefix: disnew_

CREATE TABLE IF NOT EXISTS disnew_order_lifecycles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 sales_order_id BIGINT UNSIGNED NOT NULL,
 from_status VARCHAR(50) NULL,
 to_status VARCHAR(50) NOT NULL,
 changed_by BIGINT UNSIGNED NULL,
 changed_at DATETIME NULL,
 remarks TEXT NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_ol_business_order_idx (business_id, sales_order_id),
 INDEX disnew_ol_status_idx (to_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_invoice_allocations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 sales_order_id BIGINT UNSIGNED NOT NULL,
 sales_order_line_id BIGINT UNSIGNED NULL,
 sales_invoice_id BIGINT UNSIGNED NOT NULL,
 sales_invoice_line_id BIGINT UNSIGNED NULL,
 product_id BIGINT UNSIGNED NULL,
 ordered_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
 invoiced_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
 remaining_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_ia_order_idx (business_id, sales_order_id),
 INDEX disnew_ia_invoice_idx (business_id, sales_invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_trips (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 trip_no VARCHAR(50) NOT NULL,
 vehicle_id BIGINT UNSIGNED NULL,
 driver_id BIGINT UNSIGNED NULL,
 helper_id BIGINT UNSIGNED NULL,
 route_id BIGINT UNSIGNED NULL,
 warehouse_id BIGINT UNSIGNED NULL,
 trip_date DATE NULL,
 status VARCHAR(50) NOT NULL DEFAULT 'planned',
 capacity_weight DECIMAL(22,4) NOT NULL DEFAULT 0,
 capacity_volume DECIMAL(22,4) NOT NULL DEFAULT 0,
 loaded_weight DECIMAL(22,4) NOT NULL DEFAULT 0,
 loaded_volume DECIMAL(22,4) NOT NULL DEFAULT 0,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 UNIQUE KEY disnew_trips_unique (business_id, trip_no),
 INDEX disnew_trips_date_idx (business_id, trip_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_trip_lines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 trip_id BIGINT UNSIGNED NOT NULL,
 loading_id BIGINT UNSIGNED NULL,
 sales_order_id BIGINT UNSIGNED NULL,
 customer_id BIGINT UNSIGNED NULL,
 sequence_no INT NOT NULL DEFAULT 0,
 delivery_address TEXT NULL,
 status VARCHAR(50) NOT NULL DEFAULT 'pending',
 remarks TEXT NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_trip_lines_trip_idx (business_id, trip_id),
 INDEX disnew_trip_lines_order_idx (business_id, sales_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_warehouses (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 name VARCHAR(191) NOT NULL,
 code VARCHAR(50) NOT NULL,
 manager_id BIGINT UNSIGNED NULL,
 phone VARCHAR(50) NULL,
 address TEXT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 UNIQUE KEY disnew_warehouse_unique (business_id, code),
 INDEX disnew_warehouse_location_idx (business_id, location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_warehouse_stocks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 warehouse_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 variation_id BIGINT UNSIGNED NULL,
 qty_available DECIMAL(22,4) NOT NULL DEFAULT 0,
 qty_reserved DECIMAL(22,4) NOT NULL DEFAULT 0,
 qty_loaded DECIMAL(22,4) NOT NULL DEFAULT 0,
 qty_returned DECIMAL(22,4) NOT NULL DEFAULT 0,
 last_movement_at DATETIME NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 UNIQUE KEY disnew_wh_stock_unique (business_id, warehouse_id, product_id, variation_id),
 INDEX disnew_wh_stock_product_idx (business_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_warehouse_transfers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 transfer_no VARCHAR(50) NOT NULL,
 from_warehouse_id BIGINT UNSIGNED NULL,
 to_vehicle_id BIGINT UNSIGNED NULL,
 to_warehouse_id BIGINT UNSIGNED NULL,
 status VARCHAR(50) NOT NULL DEFAULT 'draft',
 transfer_date DATE NULL,
 approved_by BIGINT UNSIGNED NULL,
 created_by BIGINT UNSIGNED NULL,
 remarks TEXT NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 UNIQUE KEY disnew_wh_transfer_unique (business_id, transfer_no),
 INDEX disnew_wh_transfer_date_idx (business_id, transfer_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_warehouse_transfer_lines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 transfer_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 variation_id BIGINT UNSIGNED NULL,
 qty DECIMAL(22,4) NOT NULL DEFAULT 0,
 unit_cost DECIMAL(22,4) NOT NULL DEFAULT 0,
 line_total DECIMAL(22,4) NOT NULL DEFAULT 0,
 remarks TEXT NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_wh_transfer_line_idx (business_id, transfer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_loading_checklists (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 loading_id BIGINT UNSIGNED NULL,
 trip_id BIGINT UNSIGNED NULL,
 checked_by BIGINT UNSIGNED NULL,
 checked_at DATETIME NULL,
 checklist_json JSON NULL,
 status VARCHAR(50) NOT NULL DEFAULT 'pending',
 remarks TEXT NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_loading_check_idx (business_id, loading_id, trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_unloading_checklists (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 unloading_id BIGINT UNSIGNED NULL,
 trip_id BIGINT UNSIGNED NULL,
 checked_by BIGINT UNSIGNED NULL,
 checked_at DATETIME NULL,
 checklist_json JSON NULL,
 status VARCHAR(50) NOT NULL DEFAULT 'pending',
 remarks TEXT NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_unloading_check_idx (business_id, unloading_id, trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_delivery_proofs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 delivery_id BIGINT UNSIGNED NULL,
 sales_order_id BIGINT UNSIGNED NULL,
 customer_id BIGINT UNSIGNED NULL,
 receiver_name VARCHAR(191) NULL,
 receiver_mobile VARCHAR(50) NULL,
 proof_file VARCHAR(255) NULL,
 gps_lat DECIMAL(11,8) NULL,
 gps_lng DECIMAL(11,8) NULL,
 received_at DATETIME NULL,
 remarks TEXT NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_delivery_proofs_idx (business_id, delivery_id, sales_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_business_limits (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 max_vehicles INT NULL,
 max_sales_reps INT NULL,
 max_territories INT NULL,
 max_routes INT NULL,
 max_warehouses INT NULL,
 max_delivery_users INT NULL,
 max_customer_portal_users INT NULL,
 max_active_orders INT NULL,
 max_daily_deliveries INT NULL,
 updated_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 UNIQUE KEY disnew_business_limits_unique (business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_dashboard_snapshots (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 snapshot_date DATE NOT NULL,
 metric_key VARCHAR(100) NOT NULL,
 metric_value DECIMAL(22,4) NOT NULL DEFAULT 0,
 metric_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_dashboard_snapshot_idx (business_id, location_id, snapshot_date, metric_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE disnew_sales_orders ADD COLUMN IF NOT EXISTS lifecycle_status VARCHAR(50) NULL AFTER status;
ALTER TABLE disnew_sales_orders ADD COLUMN IF NOT EXISTS fully_invoiced_at DATETIME NULL AFTER lifecycle_status;
ALTER TABLE disnew_sales_invoices ADD COLUMN IF NOT EXISTS source_sales_order_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE disnew_vehicles ADD COLUMN IF NOT EXISTS capacity_weight DECIMAL(22,4) NOT NULL DEFAULT 0;
ALTER TABLE disnew_vehicles ADD COLUMN IF NOT EXISTS capacity_volume DECIMAL(22,4) NOT NULL DEFAULT 0;
ALTER TABLE disnew_vehicles ADD COLUMN IF NOT EXISTS maintenance_warning_at DATE NULL;


-- ===== DISNEW_009_stage9.sql =====
-- Distribution New Stage 9 SQL only
-- Mobile/offline API foundation, approvals, audit trails, report schedules.

CREATE TABLE IF NOT EXISTS disnew_mobile_devices (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 sales_rep_id BIGINT UNSIGNED NULL,
 device_uid VARCHAR(191) NOT NULL,
 device_name VARCHAR(191) NULL,
 platform VARCHAR(50) NULL,
 app_version VARCHAR(50) NULL,
 last_sync_at DATETIME NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 registered_at DATETIME NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 UNIQUE KEY disnew_device_uid_unique (business_id, device_uid),
 INDEX disnew_mobile_devices_idx (business_id, user_id, sales_rep_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_offline_queues (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 device_id BIGINT UNSIGNED NULL,
 entity_type VARCHAR(100) NOT NULL,
 entity_local_id VARCHAR(191) NULL,
 entity_server_id BIGINT UNSIGNED NULL,
 payload_json JSON NULL,
 sync_status VARCHAR(50) NOT NULL DEFAULT 'pending',
 error_message TEXT NULL,
 synced_at DATETIME NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_offline_queue_idx (business_id, device_id, entity_type, sync_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_approval_rules (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 rule_for VARCHAR(100) NOT NULL,
 min_amount DECIMAL(22,4) NULL,
 max_amount DECIMAL(22,4) NULL,
 requires_role VARCHAR(100) NULL,
 requires_user_id BIGINT UNSIGNED NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_approval_rules_idx (business_id, location_id, rule_for)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_approval_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 rule_id BIGINT UNSIGNED NULL,
 reference_type VARCHAR(100) NOT NULL,
 reference_id BIGINT UNSIGNED NOT NULL,
 requested_by BIGINT UNSIGNED NULL,
 approved_by BIGINT UNSIGNED NULL,
 status VARCHAR(50) NOT NULL DEFAULT 'pending',
 requested_at DATETIME NULL,
 approved_at DATETIME NULL,
 remarks TEXT NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_approval_requests_idx (business_id, reference_type, reference_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_audit_trails (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 action VARCHAR(100) NOT NULL,
 entity_type VARCHAR(100) NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 before_json JSON NULL,
 after_json JSON NULL,
 ip_address VARCHAR(50) NULL,
 user_agent VARCHAR(500) NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_audit_trails_idx (business_id, entity_type, entity_id, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_api_tokens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 device_id BIGINT UNSIGNED NULL,
 token_hash VARCHAR(191) NOT NULL,
 abilities_json JSON NULL,
 last_used_at DATETIME NULL,
 expires_at DATETIME NULL,
 revoked_at DATETIME NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 UNIQUE KEY disnew_api_token_hash_unique (token_hash),
 INDEX disnew_api_tokens_idx (business_id, user_id, device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_sync_conflicts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 device_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 entity_type VARCHAR(100) NOT NULL,
 entity_local_id VARCHAR(191) NULL,
 entity_server_id BIGINT UNSIGNED NULL,
 server_payload_json JSON NULL,
 client_payload_json JSON NULL,
 resolution_status VARCHAR(50) NOT NULL DEFAULT 'open',
 resolved_by BIGINT UNSIGNED NULL,
 resolved_at DATETIME NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_sync_conflicts_idx (business_id, entity_type, resolution_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_report_schedules (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 report_key VARCHAR(100) NOT NULL,
 frequency VARCHAR(50) NOT NULL DEFAULT 'daily',
 recipient_emails TEXT NULL,
 recipient_user_ids TEXT NULL,
 last_run_at DATETIME NULL,
 next_run_at DATETIME NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_report_schedules_idx (business_id, location_id, report_key, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE disnew_sales_orders ADD COLUMN IF NOT EXISTS approval_status VARCHAR(50) NULL AFTER status;
ALTER TABLE disnew_sales_orders ADD COLUMN IF NOT EXISTS offline_reference VARCHAR(191) NULL AFTER approval_status;
ALTER TABLE disnew_sales_invoices ADD COLUMN IF NOT EXISTS approval_status VARCHAR(50) NULL AFTER status;


-- ===== DISNEW_010_STAGE10_STANDALONE_AUDIT.sql =====
-- DISNEW_010_STAGE10_STANDALONE_AUDIT.sql
-- Distribution New Stage 10 only.
-- Run in each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `disnew_health_checks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NULL,
  `check_key` VARCHAR(120) NOT NULL,
  `check_group` VARCHAR(80) NOT NULL DEFAULT 'general',
  `status` ENUM('pass','warning','fail') NOT NULL DEFAULT 'warning',
  `message` TEXT NULL,
  `payload` LONGTEXT NULL,
  `checked_by` INT UNSIGNED NULL,
  `checked_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_health_business_idx` (`business_id`),
  KEY `disnew_health_group_idx` (`check_group`),
  KEY `disnew_health_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_installation_steps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `step_key` VARCHAR(120) NOT NULL,
  `step_title` VARCHAR(191) NOT NULL,
  `status` ENUM('pending','completed','skipped','failed') NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `completed_by` INT UNSIGNED NULL,
  `completed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_install_business_step_unique` (`business_id`,`step_key`),
  KEY `disnew_install_business_idx` (`business_id`),
  KEY `disnew_install_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_permission_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `user_id` INT UNSIGNED NULL,
  `permission_key` VARCHAR(160) NOT NULL,
  `expected_status` TINYINT(1) NOT NULL DEFAULT 0,
  `actual_status` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('pass','mismatch') NOT NULL DEFAULT 'pass',
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_perm_audit_business_idx` (`business_id`),
  KEY `disnew_perm_audit_user_idx` (`user_id`),
  KEY `disnew_perm_audit_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_export_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NULL,
  `report_key` VARCHAR(120) NOT NULL,
  `export_type` ENUM('csv','excel','pdf','print') NOT NULL,
  `filter_payload` LONGTEXT NULL,
  `record_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_export_business_idx` (`business_id`),
  KEY `disnew_export_report_idx` (`report_key`),
  KEY `disnew_export_type_idx` (`export_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== DISNEW_011_STAGE_SQL.sql =====
-- DISNEW 011 - Stage SQL only. Run this in each tenant database.

CREATE TABLE IF NOT EXISTS disnew_notification_preferences (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    business_location_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    event_key VARCHAR(100) NOT NULL,
    officer_group_key VARCHAR(100) NULL,
    sms_enabled TINYINT(1) NOT NULL DEFAULT 1,
    email_enabled TINYINT(1) NOT NULL DEFAULT 0,
    in_app_enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY disnew_notification_pref_unique (business_id, business_location_id, user_id, event_key),
    KEY disnew_notification_pref_event_idx (business_id, event_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_saved_filters (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    business_location_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    filter_key VARCHAR(100) NOT NULL,
    filter_name VARCHAR(150) NOT NULL,
    filters JSON NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    KEY disnew_saved_filters_lookup_idx (business_id, user_id, filter_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_permission_seeds (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(191) NOT NULL,
    permission_group VARCHAR(100) NOT NULL DEFAULT 'distribution_new',
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY disnew_permission_seed_name_unique (permission_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_deployment_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NULL,
    stage VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'installed',
    note TEXT NULL,
    payload JSON NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    KEY disnew_deployment_logs_stage_idx (stage, status),
    KEY disnew_deployment_logs_business_idx (business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO disnew_permission_seeds (permission_name, permission_group, description, created_at, updated_at) VALUES
('distribution_new.view_dashboard','distribution_new','View Distribution New dashboard',NOW(),NOW()),
('distribution_new.manage_orders','distribution_new','Create and edit Distribution New sales orders',NOW(),NOW()),
('distribution_new.create_invoice_from_order','distribution_new','Create invoice from sales order',NOW(),NOW()),
('distribution_new.manage_loading','distribution_new','Manage loading operations',NOW(),NOW()),
('distribution_new.manage_unloading','distribution_new','Manage unloading operations',NOW(),NOW()),
('distribution_new.manage_vehicles','distribution_new','Manage Distribution New vehicles',NOW(),NOW()),
('distribution_new.manage_routes','distribution_new','Manage territories and routes',NOW(),NOW()),
('distribution_new.manage_collections','distribution_new','Manage collections',NOW(),NOW()),
('distribution_new.manage_settlements','distribution_new','Manage settlements',NOW(),NOW()),
('distribution_new.manage_returns','distribution_new','Manage returns and credit notes',NOW(),NOW()),
('distribution_new.view_reports','distribution_new','View Distribution New reports',NOW(),NOW()),
('distribution_new.manage_notification_preferences','distribution_new','Manage SMS/officer notification preferences',NOW(),NOW()),
('distribution_new.superadmin_limits','distribution_new','Manage Super Admin limits for Distribution New',NOW(),NOW());

INSERT INTO disnew_deployment_logs (stage, status, note, created_at, updated_at)
VALUES ('DISNEW_011', 'installed', 'Role dashboards, notification preferences, filters, permission seeds and rollback SQL installed.', NOW(), NOW());


-- ===== DISNEW_013_SMART_LOGISTICS.sql =====
-- DISNEW_013 Smart Logistics Large Parcel
-- Run inside every tenant database. No database name is specified intentionally.

CREATE TABLE IF NOT EXISTS `disnew_drivers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `employee_id` BIGINT UNSIGNED NULL,
  `driver_code` VARCHAR(50) NULL,
  `name` VARCHAR(150) NOT NULL,
  `mobile` VARCHAR(30) NULL,
  `nic_no` VARCHAR(60) NULL,
  `license_no` VARCHAR(80) NULL,
  `license_expiry_date` DATE NULL,
  `commission_type` ENUM('none','fixed','percentage','per_trip') NOT NULL DEFAULT 'none',
  `commission_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_drivers_business_idx` (`business_id`,`business_location_id`,`status`),
  KEY `disnew_drivers_license_expiry_idx` (`license_expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_helpers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `employee_id` BIGINT UNSIGNED NULL,
  `helper_code` VARCHAR(50) NULL,
  `name` VARCHAR(150) NOT NULL,
  `mobile` VARCHAR(30) NULL,
  `commission_type` ENUM('none','fixed','percentage','per_trip') NOT NULL DEFAULT 'none',
  `commission_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_helpers_business_idx` (`business_id`,`business_location_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_vehicle_odometer_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `trip_id` BIGINT UNSIGNED NULL,
  `reading_date` DATE NOT NULL,
  `reading_time` TIME NULL,
  `opening_odometer` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `closing_odometer` DECIMAL(22,3) NULL,
  `distance` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `source_type` VARCHAR(50) NULL,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_odometer_business_vehicle_idx` (`business_id`,`vehicle_id`,`reading_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_vehicle_fuel_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `driver_id` BIGINT UNSIGNED NULL,
  `trip_id` BIGINT UNSIGNED NULL,
  `fuel_date` DATE NOT NULL,
  `fuel_time` TIME NULL,
  `fuel_type` VARCHAR(50) NULL,
  `litres` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `odometer_reading` DECIMAL(22,3) NULL,
  `supplier_name` VARCHAR(150) NULL,
  `receipt_no` VARCHAR(80) NULL,
  `payment_method` VARCHAR(50) NULL,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_fuel_business_vehicle_idx` (`business_id`,`vehicle_id`,`fuel_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_vehicle_maintenances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `maintenance_date` DATE NOT NULL,
  `maintenance_type` VARCHAR(80) NOT NULL,
  `odometer_reading` DECIMAL(22,3) NULL,
  `next_due_date` DATE NULL,
  `next_due_odometer` DECIMAL(22,3) NULL,
  `garage_name` VARCHAR(150) NULL,
  `invoice_no` VARCHAR(80) NULL,
  `cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'completed',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_maint_business_vehicle_idx` (`business_id`,`vehicle_id`,`maintenance_date`),
  KEY `disnew_maint_due_idx` (`next_due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_vehicle_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `document_type` VARCHAR(80) NOT NULL,
  `document_no` VARCHAR(100) NULL,
  `issue_date` DATE NULL,
  `expiry_date` DATE NULL,
  `renewal_reminder_days` INT NOT NULL DEFAULT 30,
  `file_path` VARCHAR(255) NULL,
  `status` ENUM('active','expired','renewed','cancelled') NOT NULL DEFAULT 'active',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_doc_business_vehicle_idx` (`business_id`,`vehicle_id`,`document_type`),
  KEY `disnew_doc_expiry_idx` (`expiry_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_trip_expenses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `trip_id` BIGINT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NULL,
  `driver_id` BIGINT UNSIGNED NULL,
  `expense_date` DATE NOT NULL,
  `expense_category` VARCHAR(80) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reference_no` VARCHAR(100) NULL,
  `payment_method` VARCHAR(50) NULL,
  `is_reimbursable` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_trip_exp_business_idx` (`business_id`,`expense_date`,`status`),
  KEY `disnew_trip_exp_vehicle_idx` (`vehicle_id`,`trip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_trip_commissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `trip_id` BIGINT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NULL,
  `driver_id` BIGINT UNSIGNED NULL,
  `helper_id` BIGINT UNSIGNED NULL,
  `commission_for` ENUM('driver','helper','sales_rep') NOT NULL DEFAULT 'driver',
  `base_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `commission_type` ENUM('fixed','percentage','per_trip') NOT NULL DEFAULT 'fixed',
  `commission_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `commission_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('calculated','approved','paid','cancelled') NOT NULL DEFAULT 'calculated',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_trip_comm_business_idx` (`business_id`,`status`),
  KEY `disnew_trip_comm_trip_idx` (`trip_id`,`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT p.name, 'web', NOW(), NOW()
FROM (
  SELECT 'distributionnew.drivers.view' name UNION ALL
  SELECT 'distributionnew.drivers.create' UNION ALL
  SELECT 'distributionnew.drivers.update' UNION ALL
  SELECT 'distributionnew.helpers.view' UNION ALL
  SELECT 'distributionnew.helpers.create' UNION ALL
  SELECT 'distributionnew.helpers.update' UNION ALL
  SELECT 'distributionnew.fuel.view' UNION ALL
  SELECT 'distributionnew.fuel.create' UNION ALL
  SELECT 'distributionnew.odometer.view' UNION ALL
  SELECT 'distributionnew.odometer.create' UNION ALL
  SELECT 'distributionnew.maintenance.view' UNION ALL
  SELECT 'distributionnew.maintenance.create' UNION ALL
  SELECT 'distributionnew.vehicle_documents.view' UNION ALL
  SELECT 'distributionnew.vehicle_documents.create' UNION ALL
  SELECT 'distributionnew.trip_expenses.view' UNION ALL
  SELECT 'distributionnew.trip_expenses.create' UNION ALL
  SELECT 'distributionnew.trip_commissions.view' UNION ALL
  SELECT 'distributionnew.trip_commissions.approve'
) p
WHERE NOT EXISTS (SELECT 1 FROM `permissions` x WHERE x.name = p.name);


-- ===== DISNEW_014_SMART_SALES.sql =====
-- DISNEW_014 Smart Sales Distribution Large Parcel
-- Run inside every tenant database. No database name is specified intentionally.

CREATE TABLE IF NOT EXISTS `disnew_credit_controls` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `credit_limit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `credit_days` INT NOT NULL DEFAULT 0,
  `block_on_over_limit` TINYINT(1) NOT NULL DEFAULT 1,
  `block_on_overdue` TINYINT(1) NOT NULL DEFAULT 1,
  `warning_percentage` DECIMAL(8,3) NOT NULL DEFAULT 80.000,
  `temporary_limit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `temporary_limit_expiry` DATE NULL,
  `status` ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_credit_customer_unique` (`business_id`,`customer_id`),
  KEY `disnew_credit_business_status_idx` (`business_id`,`business_location_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_price_lists` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `price_list_code` VARCHAR(60) NULL,
  `name` VARCHAR(150) NOT NULL,
  `scope_type` ENUM('general','customer','route','territory','sales_rep') NOT NULL DEFAULT 'general',
  `customer_id` BIGINT UNSIGNED NULL,
  `route_id` BIGINT UNSIGNED NULL,
  `territory_id` BIGINT UNSIGNED NULL,
  `sales_rep_id` BIGINT UNSIGNED NULL,
  `valid_from` DATE NULL,
  `valid_to` DATE NULL,
  `priority` INT NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_price_scope_idx` (`business_id`,`scope_type`,`status`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_price_list_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `price_list_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `variation_id` BIGINT UNSIGNED NULL,
  `unit_id` BIGINT UNSIGNED NULL,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `minimum_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `maximum_qty` DECIMAL(22,4) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_price_lines_product_idx` (`business_id`,`product_id`,`variation_id`),
  KEY `disnew_price_lines_list_idx` (`price_list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_discount_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `rule_code` VARCHAR(60) NULL,
  `name` VARCHAR(150) NOT NULL,
  `scope_type` ENUM('general','customer','route','territory','product','category','sales_rep') NOT NULL DEFAULT 'general',
  `discount_type` ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `minimum_order_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `minimum_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `valid_from` DATE NULL,
  `valid_to` DATE NULL,
  `priority` INT NOT NULL DEFAULT 0,
  `requires_approval` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `condition_json` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_discount_rules_idx` (`business_id`,`scope_type`,`status`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_promotions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `promotion_code` VARCHAR(60) NULL,
  `name` VARCHAR(150) NOT NULL,
  `promotion_type` ENUM('bundle','seasonal','free_issue','discount','campaign') NOT NULL DEFAULT 'discount',
  `valid_from` DATE NULL,
  `valid_to` DATE NULL,
  `budget_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `used_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('draft','active','paused','closed') NOT NULL DEFAULT 'draft',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_promotions_business_idx` (`business_id`,`business_location_id`,`status`,`valid_from`,`valid_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_promotion_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `promotion_id` BIGINT UNSIGNED NOT NULL,
  `buy_product_id` BIGINT UNSIGNED NULL,
  `buy_variation_id` BIGINT UNSIGNED NULL,
  `buy_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `free_product_id` BIGINT UNSIGNED NULL,
  `free_variation_id` BIGINT UNSIGNED NULL,
  `free_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount_type` ENUM('none','percentage','fixed') NOT NULL DEFAULT 'none',
  `discount_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_promotion_lines_idx` (`business_id`,`promotion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_free_issue_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `name` VARCHAR(150) NOT NULL,
  `buy_product_id` BIGINT UNSIGNED NOT NULL,
  `buy_variation_id` BIGINT UNSIGNED NULL,
  `buy_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `free_product_id` BIGINT UNSIGNED NOT NULL,
  `free_variation_id` BIGINT UNSIGNED NULL,
  `free_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `valid_from` DATE NULL,
  `valid_to` DATE NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_free_issue_idx` (`business_id`,`buy_product_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_sales_targets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `target_type` ENUM('sales_rep','territory','route','customer','product') NOT NULL DEFAULT 'sales_rep',
  `sales_rep_id` BIGINT UNSIGNED NULL,
  `territory_id` BIGINT UNSIGNED NULL,
  `route_id` BIGINT UNSIGNED NULL,
  `customer_id` BIGINT UNSIGNED NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `period_type` ENUM('daily','weekly','monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `target_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `target_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `achieved_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `achieved_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('active','closed','cancelled') NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_targets_idx` (`business_id`,`target_type`,`period_start`,`period_end`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_sales_commissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `sales_rep_id` BIGINT UNSIGNED NULL,
  `customer_id` BIGINT UNSIGNED NULL,
  `sales_order_id` BIGINT UNSIGNED NULL,
  `sales_invoice_id` BIGINT UNSIGNED NULL,
  `commission_date` DATE NOT NULL,
  `base_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `commission_type` ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `commission_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `commission_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('pending','approved','paid','cancelled') NOT NULL DEFAULT 'pending',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_commissions_idx` (`business_id`,`sales_rep_id`,`commission_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_sales_kpi_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `snapshot_date` DATE NOT NULL,
  `sales_rep_id` BIGINT UNSIGNED NULL,
  `territory_id` BIGINT UNSIGNED NULL,
  `route_id` BIGINT UNSIGNED NULL,
  `orders_count` INT NOT NULL DEFAULT 0,
  `invoices_count` INT NOT NULL DEFAULT 0,
  `gross_sales` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `net_sales` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `collections_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `returns_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `target_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `achievement_percentage` DECIMAL(8,3) NOT NULL DEFAULT 0.000,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_kpi_snapshot_idx` (`business_id`,`snapshot_date`,`sales_rep_id`,`territory_id`,`route_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ===== DISNEW_012_UI_STABILIZATION.sql =====
-- DISNEW_012_UI_STABILIZATION.sql
-- Purpose: make Distribution New visible/testable from UI after Stage 1-11.
-- Run in every tenant database. This SQL is designed to be safe to re-run where possible.

CREATE TABLE IF NOT EXISTS `disnew_module_ui_status` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `module_key` VARCHAR(80) NOT NULL DEFAULT 'distribution_new',
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `is_installed` TINYINT(1) NOT NULL DEFAULT 1,
  `last_checked_at` TIMESTAMP NULL DEFAULT NULL,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_ui_business_module_unique` (`business_id`, `module_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_menu_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `parent_key` VARCHAR(80) NULL,
  `menu_key` VARCHAR(120) NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `route_name` VARCHAR(190) NOT NULL,
  `permission_name` VARCHAR(190) NULL,
  `icon` VARCHAR(80) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_menu_key_unique` (`business_id`, `menu_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `disnew_module_ui_status` (`business_id`, `module_key`, `is_visible`, `is_installed`, `last_checked_at`, `remarks`, `created_at`, `updated_at`)
VALUES (NULL, 'distribution_new', 1, 1, NOW(), 'DISNEW_012 UI stabilization installed', NOW(), NOW())
ON DUPLICATE KEY UPDATE `is_visible` = VALUES(`is_visible`), `is_installed` = VALUES(`is_installed`), `last_checked_at` = NOW(), `updated_at` = NOW();

INSERT INTO `disnew_menu_items` (`business_id`, `parent_key`, `menu_key`, `title`, `route_name`, `permission_name`, `icon`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(NULL, NULL, 'distributionnew.dashboard', 'Dashboard', 'distributionnew.dashboard', 'distributionnew.dashboard', 'fa fa-dashboard', 10, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.sales_orders', 'Sales Orders', 'distributionnew.sales-orders.index', 'distributionnew.sales_orders.view', 'fa fa-file-text-o', 20, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.sales_invoices', 'Sales Invoices', 'distributionnew.sales-invoices.index', 'distributionnew.sales_invoices.view', 'fa fa-list-alt', 30, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.loading_plans', 'Loading Plans', 'distributionnew.loading-plans.index', 'distributionnew.loading.view', 'fa fa-upload', 40, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.loading', 'Loading', 'distributionnew.loading.index', 'distributionnew.loading.view', 'fa fa-truck', 50, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.unloading', 'Unloading', 'distributionnew.unloading.index', 'distributionnew.unloading.view', 'fa fa-download', 60, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.vehicles', 'Vehicles', 'distributionnew.vehicles.index', 'distributionnew.vehicles.view', 'fa fa-truck', 70, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.vehicle_stock', 'Vehicle Stock', 'distributionnew.vehicle-stock.index', 'distributionnew.stock.view', 'fa fa-cubes', 80, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.territories', 'Territories', 'distributionnew.territories.index', 'distributionnew.routes.view', 'fa fa-map', 90, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.routes', 'Routes', 'distributionnew.routes.index', 'distributionnew.routes.view', 'fa fa-road', 100, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.sales_reps', 'Sales Reps', 'distributionnew.sales-reps.index', 'distributionnew.sales_reps.view', 'fa fa-user', 110, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.deliveries', 'Deliveries', 'distributionnew.deliveries.index', 'distributionnew.deliveries.view', 'fa fa-check-square-o', 120, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.collections', 'Collections', 'distributionnew.collections.index', 'distributionnew.collections.view', 'fa fa-money', 130, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.settlements', 'Settlements', 'distributionnew.settlements.index', 'distributionnew.settlements.view', 'fa fa-balance-scale', 140, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.returns', 'Returns', 'distributionnew.returns.index', 'distributionnew.returns.view', 'fa fa-undo', 150, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.credit_notes', 'Credit Notes', 'distributionnew.credit-notes.index', 'distributionnew.credit_notes.view', 'fa fa-credit-card', 160, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.reports', 'Reports', 'distributionnew.reports.index', 'distributionnew.reports.view', 'fa fa-bar-chart', 170, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.settings', 'Settings', 'distributionnew.settings.index', 'distributionnew.settings.view', 'fa fa-cog', 180, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`), `route_name`=VALUES(`route_name`), `permission_name`=VALUES(`permission_name`), `icon`=VALUES(`icon`), `sort_order`=VALUES(`sort_order`), `is_active`=1, `updated_at`=NOW();

-- Permissions are optional here because different ERP builds use different permission schemas.
-- If your application has Spatie permission table, this is safe:
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('distributionnew.dashboard', 'web', NOW(), NOW()),
('distributionnew.sales_orders.view', 'web', NOW(), NOW()),
('distributionnew.sales_orders.create', 'web', NOW(), NOW()),
('distributionnew.sales_orders.update', 'web', NOW(), NOW()),
('distributionnew.sales_invoices.view', 'web', NOW(), NOW()),
('distributionnew.sales_invoices.create', 'web', NOW(), NOW()),
('distributionnew.vehicles.view', 'web', NOW(), NOW()),
('distributionnew.vehicles.create', 'web', NOW(), NOW()),
('distributionnew.vehicles.update', 'web', NOW(), NOW()),
('distributionnew.loading.view', 'web', NOW(), NOW()),
('distributionnew.loading.create', 'web', NOW(), NOW()),
('distributionnew.unloading.view', 'web', NOW(), NOW()),
('distributionnew.unloading.create', 'web', NOW(), NOW()),
('distributionnew.stock.view', 'web', NOW(), NOW()),
('distributionnew.routes.view', 'web', NOW(), NOW()),
('distributionnew.routes.create', 'web', NOW(), NOW()),
('distributionnew.sales_reps.view', 'web', NOW(), NOW()),
('distributionnew.sales_reps.create', 'web', NOW(), NOW()),
('distributionnew.deliveries.view', 'web', NOW(), NOW()),
('distributionnew.collections.view', 'web', NOW(), NOW()),
('distributionnew.collections.create', 'web', NOW(), NOW()),
('distributionnew.settlements.view', 'web', NOW(), NOW()),
('distributionnew.settlements.create', 'web', NOW(), NOW()),
('distributionnew.returns.view', 'web', NOW(), NOW()),
('distributionnew.credit_notes.view', 'web', NOW(), NOW()),
('distributionnew.reports.view', 'web', NOW(), NOW()),
('distributionnew.settings.view', 'web', NOW(), NOW());


-- ===== DISNEW_015_advanced_analytics.sql =====
-- DISNEW_015 Advanced Analytics and Reporting Large Parcel
-- Run inside every tenant database. No database name is specified intentionally.

CREATE TABLE IF NOT EXISTS `disnew_analytics_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `snapshot_date` DATE NOT NULL,
  `snapshot_type` ENUM('executive','sales','route','territory','vehicle','warehouse','customer','product','driver') NOT NULL DEFAULT 'executive',
  `reference_type` VARCHAR(60) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `orders_count` INT NOT NULL DEFAULT 0,
  `invoices_count` INT NOT NULL DEFAULT 0,
  `deliveries_count` INT NOT NULL DEFAULT 0,
  `returns_count` INT NOT NULL DEFAULT 0,
  `gross_sales` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `net_sales` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `collections` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `outstanding` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `return_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `profit_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `profit_percentage` DECIMAL(8,3) NOT NULL DEFAULT 0.000,
  `payload_json` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_analytics_snapshot_unique` (`business_id`,`business_location_id`,`snapshot_date`,`snapshot_type`,`reference_type`,`reference_id`),
  KEY `disnew_analytics_snapshot_idx` (`business_id`,`snapshot_date`,`snapshot_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_kpi_metrics` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `metric_code` VARCHAR(80) NOT NULL,
  `metric_name` VARCHAR(150) NOT NULL,
  `metric_group` VARCHAR(80) NOT NULL DEFAULT 'distribution',
  `metric_date` DATE NOT NULL,
  `metric_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `target_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `variance_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `variance_percentage` DECIMAL(8,3) NOT NULL DEFAULT 0.000,
  `status` ENUM('good','warning','critical','neutral') NOT NULL DEFAULT 'neutral',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_kpi_metric_unique` (`business_id`,`business_location_id`,`metric_code`,`metric_date`),
  KEY `disnew_kpi_metric_group_idx` (`business_id`,`metric_group`,`metric_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_scheduled_reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `report_code` VARCHAR(80) NOT NULL,
  `report_name` VARCHAR(150) NOT NULL,
  `frequency` ENUM('daily','weekly','monthly','manual') NOT NULL DEFAULT 'daily',
  `delivery_channel` ENUM('email','sms','both','download_only') NOT NULL DEFAULT 'email',
  `recipient_emails` TEXT NULL,
  `recipient_mobiles` TEXT NULL,
  `officer_group` VARCHAR(80) NULL,
  `last_run_at` TIMESTAMP NULL,
  `next_run_at` TIMESTAMP NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `filters_json` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_sched_reports_idx` (`business_id`,`business_location_id`,`status`,`next_run_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_report_delivery_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `scheduled_report_id` BIGINT UNSIGNED NULL,
  `report_code` VARCHAR(80) NOT NULL,
  `delivery_channel` VARCHAR(30) NOT NULL,
  `recipient` VARCHAR(191) NULL,
  `status` ENUM('queued','sent','failed','skipped') NOT NULL DEFAULT 'queued',
  `external_reference` VARCHAR(191) NULL,
  `message` TEXT NULL,
  `file_path` VARCHAR(255) NULL,
  `sent_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_report_delivery_idx` (`business_id`,`report_code`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_export_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `export_code` VARCHAR(80) NOT NULL,
  `export_name` VARCHAR(150) NOT NULL,
  `export_format` ENUM('csv','excel','pdf') NOT NULL DEFAULT 'excel',
  `requested_by` BIGINT UNSIGNED NULL,
  `status` ENUM('queued','processing','completed','failed') NOT NULL DEFAULT 'queued',
  `filters_json` JSON NULL,
  `file_path` VARCHAR(255) NULL,
  `error_message` TEXT NULL,
  `started_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_export_jobs_idx` (`business_id`,`business_location_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `disnew_module_permissions` (`business_id`,`permission_key`,`permission_name`,`permission_group`,`created_at`,`updated_at`) VALUES
(0,'distribution_new.analytics.view','View Advanced Analytics','Distribution New Analytics',NOW(),NOW()),
(0,'distribution_new.analytics.export','Export Analytics','Distribution New Analytics',NOW(),NOW()),
(0,'distribution_new.scheduled_reports.manage','Manage Scheduled Reports','Distribution New Analytics',NOW(),NOW()),
(0,'distribution_new.executive_dashboard.view','View Executive Dashboard','Distribution New Analytics',NOW(),NOW());


-- ===== DISNEW_016_production_audit.sql =====
CREATE TABLE IF NOT EXISTS disnew_audit_checks (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 business_id INT UNSIGNED NULL,
 check_key VARCHAR(191) NOT NULL,
 check_group VARCHAR(100) NOT NULL,
 expected_value TEXT NULL,
 is_required TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_audit_checks_business_idx (business_id),
 INDEX disnew_audit_checks_group_idx (check_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_audit_results (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 business_id INT UNSIGNED NULL,
 user_id INT UNSIGNED NULL,
 check_type VARCHAR(100) NOT NULL,
 status VARCHAR(50) NOT NULL DEFAULT 'pending',
 message TEXT NULL,
 payload JSON NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_audit_results_business_idx (business_id),
 INDEX disnew_audit_results_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO disnew_audit_checks (business_id, check_key, check_group, expected_value, is_required, created_at, updated_at) VALUES
(NULL,'disnew_module_visible','menu','Distribution New sidebar visible',1,NOW(),NOW()),
(NULL,'disnew_permissions_seeded','permission','Core Distribution New permissions seeded',1,NOW(),NOW()),
(NULL,'disnew_routes_loaded','route','Distribution New routes return non-404',1,NOW(),NOW()),
(NULL,'disnew_tables_exist','sql','All required disnew_ tables exist',1,NOW(),NOW()),
(NULL,'disnew_pos_ui','ui','POS dashboard design standard applied',1,NOW(),NOW());

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('disnew.audit.view','web',NOW(),NOW()),
('disnew.audit.run','web',NOW(),NOW());


-- ===== DISNEW_017_mobile_offline.sql =====
-- DISNEW_017 Mobile / Customer / Driver API + Offline Operations

CREATE TABLE IF NOT EXISTS disnew_mobile_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  device_uuid VARCHAR(100) NOT NULL,
  token VARCHAR(128) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  expires_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY disnew_mobile_tokens_token_unique (token),
  KEY disnew_mobile_tokens_business_device_idx (business_id, device_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_offline_devices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  device_uuid VARCHAR(100) NOT NULL,
  device_name VARCHAR(191) NULL,
  platform VARCHAR(50) NULL,
  app_version VARCHAR(50) NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  last_seen_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY disnew_offline_devices_uuid_unique (device_uuid),
  KEY disnew_offline_devices_business_idx (business_id, location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_sync_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  device_uuid VARCHAR(100) NULL,
  batch_uuid VARCHAR(100) NOT NULL,
  direction VARCHAR(20) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'received',
  payload_count INT NOT NULL DEFAULT 0,
  conflict_count INT NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  processed_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY disnew_sync_batches_uuid_unique (batch_uuid),
  KEY disnew_sync_batches_business_idx (business_id, location_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_sync_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sync_batch_id BIGINT UNSIGNED NOT NULL,
  entity_type VARCHAR(100) NOT NULL,
  entity_uuid VARCHAR(100) NULL,
  operation VARCHAR(30) NOT NULL DEFAULT 'upsert',
  payload_json LONGTEXT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'queued',
  conflict_reason TEXT NULL,
  processed_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  KEY disnew_sync_items_batch_idx (sync_batch_id),
  KEY disnew_sync_items_entity_idx (entity_type, entity_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_delivery_checkpoints (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  trip_id BIGINT UNSIGNED NULL,
  delivery_id BIGINT UNSIGNED NULL,
  checkpoint_type VARCHAR(50) NOT NULL,
  latitude DECIMAL(12,8) NULL,
  longitude DECIMAL(12,8) NULL,
  remarks TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  KEY disnew_delivery_checkpoints_trip_idx (business_id, trip_id, delivery_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_epods (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  trip_id BIGINT UNSIGNED NULL,
  delivery_id BIGINT UNSIGNED NULL,
  receiver_name VARCHAR(191) NULL,
  receiver_mobile VARCHAR(50) NULL,
  signature_path VARCHAR(255) NULL,
  photo_path VARCHAR(255) NULL,
  remarks TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  KEY disnew_epods_delivery_idx (business_id, delivery_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('distribution_new.mobile_sync.view','web',NOW(),NOW()),
('distribution_new.mobile_devices.view','web',NOW(),NOW()),
('distribution_new.mobile_api.access','web',NOW(),NOW()),
('distribution_new.driver_api.access','web',NOW(),NOW()),
('distribution_new.customer_api.access','web',NOW(),NOW());


-- ===== DISNEW_018_scanner_barcode_qr.sql =====
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


-- ===== DISNEW_023_final_readiness.sql =====
-- DISNEW_023 Final Readiness SQL
-- Prefix: disnew_

CREATE TABLE IF NOT EXISTS disnew_final_readiness_checks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NULL,
  check_code VARCHAR(100) NOT NULL,
  check_name VARCHAR(191) NOT NULL,
  check_group VARCHAR(100) NOT NULL DEFAULT 'general',
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  severity VARCHAR(30) NOT NULL DEFAULT 'info',
  message TEXT NULL,
  checked_by BIGINT UNSIGNED NULL,
  checked_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_disnew_frc_business (business_id),
  INDEX idx_disnew_frc_group (check_group),
  INDEX idx_disnew_frc_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_installation_verifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  verification_key VARCHAR(120) NOT NULL,
  verification_value TEXT NULL,
  result VARCHAR(30) NOT NULL DEFAULT 'pending',
  remarks TEXT NULL,
  verified_by BIGINT UNSIGNED NULL,
  verified_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_disnew_install_key_business (business_id, verification_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== DISNEW_024_production_stabilization.sql =====
CREATE TABLE IF NOT EXISTS `disnew_production_exceptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `exception_type` VARCHAR(80) NOT NULL,
  `reference_no` VARCHAR(191) NULL,
  `message` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `assigned_to` INT UNSIGNED NULL,
  `resolution_note` TEXT NULL,
  `resolved_at` TIMESTAMP NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_disnew_pe_business_status` (`business_id`,`status`),
  KEY `idx_disnew_pe_type` (`exception_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_production_audits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `entity_type` VARCHAR(80) NOT NULL,
  `entity_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(80) NOT NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_disnew_pa_business_entity` (`business_id`,`entity_type`,`entity_id`),
  KEY `idx_disnew_pa_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_super_admin_monitors` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `metric_key` VARCHAR(80) NOT NULL,
  `metric_value` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `snapshot_date` DATE NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_disnew_sam_business_metric_date` (`business_id`,`metric_key`,`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== DISNEW_019_live_operations.sql =====
CREATE TABLE IF NOT EXISTS `disnew_vehicle_locations` (`id` bigint unsigned NOT NULL AUTO_INCREMENT, `business_id` int unsigned NOT NULL, `business_location_id` int unsigned NULL, `vehicle_id` bigint unsigned NOT NULL, `trip_id` bigint unsigned NULL, `latitude` decimal(10,7) NULL, `longitude` decimal(10,7) NULL, `speed` decimal(8,2) NULL, `recorded_at` datetime NULL, `created_by` int unsigned NULL, `created_at` timestamp NULL, `updated_at` timestamp NULL, PRIMARY KEY (`id`), KEY `disnew_vloc_business_vehicle_idx` (`business_id`,`vehicle_id`));
CREATE TABLE IF NOT EXISTS `disnew_driver_statuses` (`id` bigint unsigned NOT NULL AUTO_INCREMENT, `business_id` int unsigned NOT NULL, `business_location_id` int unsigned NULL, `driver_id` bigint unsigned NOT NULL, `status` varchar(40) NOT NULL, `remarks` text NULL, `created_by` int unsigned NULL, `created_at` timestamp NULL, `updated_at` timestamp NULL, PRIMARY KEY (`id`), KEY `disnew_driver_status_idx` (`business_id`,`driver_id`,`status`));
CREATE TABLE IF NOT EXISTS `disnew_delivery_timelines` (`id` bigint unsigned NOT NULL AUTO_INCREMENT, `business_id` int unsigned NOT NULL, `business_location_id` int unsigned NULL, `delivery_id` bigint unsigned NULL, `trip_id` bigint unsigned NULL, `event_type` varchar(60) NOT NULL, `event_note` text NULL, `latitude` decimal(10,7) NULL, `longitude` decimal(10,7) NULL, `recorded_at` datetime NULL, `created_by` int unsigned NULL, `created_at` timestamp NULL, `updated_at` timestamp NULL, PRIMARY KEY (`id`), KEY `disnew_delivery_timeline_idx` (`business_id`,`delivery_id`,`event_type`));


-- ===== DISNEW_020_customer_self_service.sql =====
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


-- ===== DISNEW_021_production_completion.sql =====
-- DISNEW_021 Production Completion & Operational Enhancements
-- Run inside each tenant database. No database name is specified.

CREATE TABLE IF NOT EXISTS `disnew_workflow_validation_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `run_no` VARCHAR(191) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `checked_by` INT UNSIGNED NULL,
  `checked_at` TIMESTAMP NULL,
  `summary` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `disnew_workflow_validation_runs_run_no_unique` (`run_no`),
  KEY `disnew_wvr_business_id_index` (`business_id`), KEY `disnew_wvr_location_id_index` (`location_id`), KEY `disnew_wvr_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_workflow_validation_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `validation_run_id` BIGINT UNSIGNED NOT NULL,
  `area` VARCHAR(100) NOT NULL,
  `check_code` VARCHAR(150) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `message` TEXT NULL,
  `payload` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `disnew_wvi_run_id_index` (`validation_run_id`), KEY `disnew_wvi_area_index` (`area`), KEY `disnew_wvi_code_index` (`check_code`), KEY `disnew_wvi_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_stock_reconciliation_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `reconciliation_date` DATE NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  `warehouse_variance_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `vehicle_variance_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `disnew_srr_business_id_index` (`business_id`), KEY `disnew_srr_location_id_index` (`location_id`), KEY `disnew_srr_date_index` (`reconciliation_date`), KEY `disnew_srr_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_stock_reconciliation_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reconciliation_run_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `warehouse_id` BIGINT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NULL,
  `system_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `physical_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `variance_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `disnew_srl_run_id_index` (`reconciliation_run_id`), KEY `disnew_srl_product_id_index` (`product_id`), KEY `disnew_srl_warehouse_id_index` (`warehouse_id`), KEY `disnew_srl_vehicle_id_index` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_visit_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `sales_rep_id` INT UNSIGNED NOT NULL,
  `route_id` BIGINT UNSIGNED NULL,
  `visit_date` DATE NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'planned',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `disnew_vp_business_id_index` (`business_id`), KEY `disnew_vp_location_id_index` (`location_id`), KEY `disnew_vp_sales_rep_id_index` (`sales_rep_id`), KEY `disnew_vp_route_id_index` (`route_id`), KEY `disnew_vp_visit_date_index` (`visit_date`), KEY `disnew_vp_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_visit_plan_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `visit_plan_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `sequence_no` INT UNSIGNED NOT NULL DEFAULT 0,
  `visit_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `planned_time` TIME NULL,
  `visited_time` TIME NULL,
  `gps_lat` DECIMAL(12,8) NULL,
  `gps_lng` DECIMAL(12,8) NULL,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `disnew_vpl_plan_id_index` (`visit_plan_id`), KEY `disnew_vpl_customer_id_index` (`customer_id`), KEY `disnew_vpl_status_index` (`visit_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_management_dashboard_widgets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `dashboard_role` VARCHAR(80) NOT NULL,
  `widget_code` VARCHAR(120) NOT NULL,
  `title` VARCHAR(191) NOT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `settings` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `disnew_dash_widget_unique` (`business_id`,`dashboard_role`,`widget_code`), KEY `disnew_mdw_role_index` (`dashboard_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_performance_cache` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `cache_key` VARCHAR(191) NOT NULL,
  `cache_payload` LONGTEXT NULL,
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `disnew_perf_cache_unique` (`business_id`,`cache_key`), KEY `disnew_pc_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional permission keys for permission seeder/import screen
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('distribution_new.workflow_validation.view','web',NOW(),NOW()),
('distribution_new.stock_reconciliation.view','web',NOW(),NOW()),
('distribution_new.stock_reconciliation.create','web',NOW(),NOW()),
('distribution_new.visit_plan.view','web',NOW(),NOW()),
('distribution_new.visit_plan.create','web',NOW(),NOW()),
('distribution_new.management_dashboard.view','web',NOW(),NOW()),
('distribution_new.performance_cache.manage','web',NOW(),NOW());


-- ===== DISNEW_022_operational_polish.sql =====
-- DISNEW 022 - Operational Polish
-- Apply this to each tenant database where Distribution New is enabled.

CREATE TABLE IF NOT EXISTS `disnew_profitability_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `from_date` DATE NOT NULL,
  `to_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `gross_sales` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `returns_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `delivery_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `vehicle_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `commission_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `net_profit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `idx_disnew_profitability_runs_business` (`business_id`), KEY `idx_disnew_profitability_runs_dates` (`from_date`,`to_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_profitability_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `run_id` BIGINT UNSIGNED NOT NULL,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `scope_type` VARCHAR(40) NOT NULL,
  `scope_id` BIGINT UNSIGNED NULL,
  `scope_name` VARCHAR(255) NULL,
  `sales_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `return_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `collection_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `profit_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `profit_percent` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `idx_disnew_profitability_lines_run` (`run_id`), KEY `idx_disnew_profitability_lines_scope` (`scope_type`,`scope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_collection_controls` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `sales_rep_id` BIGINT UNSIGNED NULL,
  `collection_date` DATE NOT NULL,
  `expected_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `collected_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `short_excess_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `remarks` TEXT NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `idx_disnew_collection_controls_business_date` (`business_id`,`collection_date`), KEY `idx_disnew_collection_controls_rep` (`sales_rep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_reconciliation_exceptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `exception_type` VARCHAR(60) NOT NULL,
  `reference_type` VARCHAR(80) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `expected_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `actual_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `variance_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `variance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `severity` VARCHAR(20) NOT NULL DEFAULT 'normal',
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `resolution_note` TEXT NULL,
  `resolved_by` BIGINT UNSIGNED NULL,
  `resolved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `idx_disnew_recon_exception_business` (`business_id`,`status`), KEY `idx_disnew_recon_exception_ref` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_deployment_verifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `check_group` VARCHAR(80) NOT NULL,
  `check_key` VARCHAR(120) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `message` TEXT NULL,
  `payload` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `idx_disnew_deployment_checks` (`check_group`,`check_key`), KEY `idx_disnew_deployment_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('distribution_new.operational_polish.view','web',NOW(),NOW()),
('distribution_new.profitability.view','web',NOW(),NOW()),
('distribution_new.collection_controls.view','web',NOW(),NOW()),
('distribution_new.reconciliation_exceptions.view','web',NOW(),NOW()),
('distribution_new.deployment_verification.view','web',NOW(),NOW());
