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
