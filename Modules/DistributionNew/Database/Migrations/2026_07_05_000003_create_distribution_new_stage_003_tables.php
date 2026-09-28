<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Source SQL: DISNEW_003_LOADING_UNLOADING_VEHICLE_STOCK.sql
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
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
SQL);
    }

    /**
     * Reverse the migrations.
     * Drops only disnew_ tables created in this stage.
     */
    public function down(): void
    {
        Schema::dropIfExists('disnew_unloading_lines');
        Schema::dropIfExists('disnew_delivery_issue_logs');
        Schema::dropIfExists('disnew_vehicle_store_stocks');
        Schema::dropIfExists('disnew_loading_plan_lines');
        Schema::dropIfExists('disnew_loading_plans');
    }
};
