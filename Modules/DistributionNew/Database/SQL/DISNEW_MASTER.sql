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


-- Included from DISNEW_006

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

-- ============================================================
-- DISNEW_010 - STANDALONE AUDIT + PRODUCTION HARDENING
-- ============================================================

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


-- DISNEW_027 included: see DISNEW_027_SERVER_TESTING_FIX_PACK_2.sql
