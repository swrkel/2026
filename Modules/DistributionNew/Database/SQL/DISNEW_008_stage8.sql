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
