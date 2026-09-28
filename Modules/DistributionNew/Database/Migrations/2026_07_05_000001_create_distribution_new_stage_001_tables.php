<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Source SQL: DISNEW_001_STAGE1_FOUNDATION.sql
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
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
SQL);
    }

    /**
     * Reverse the migrations.
     * Drops only disnew_ tables created in this stage.
     */
    public function down(): void
    {
        Schema::dropIfExists('disnew_sms_logs');
        Schema::dropIfExists('disnew_sms_officers');
        Schema::dropIfExists('disnew_stock_movements');
        Schema::dropIfExists('disnew_vehicle_stocks');
        Schema::dropIfExists('disnew_unloadings');
        Schema::dropIfExists('disnew_loading_lines');
        Schema::dropIfExists('disnew_loadings');
        Schema::dropIfExists('disnew_sales_invoice_lines');
        Schema::dropIfExists('disnew_sales_invoices');
        Schema::dropIfExists('disnew_sales_order_lines');
        Schema::dropIfExists('disnew_sales_orders');
        Schema::dropIfExists('disnew_number_sequences');
    }
};
