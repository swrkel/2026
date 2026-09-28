<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Source SQL: DISNEW_005_ROUTES_SALESREP_CUSTOMER_DELIVERY.sql
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
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
SQL);
    }

    /**
     * Reverse the migrations.
     * Drops only disnew_ tables created in this stage.
     */
    public function down(): void
    {
        Schema::dropIfExists('disnew_delivery_lines');
        Schema::dropIfExists('disnew_deliveries');
        Schema::dropIfExists('disnew_customer_access_tokens');
        Schema::dropIfExists('disnew_sales_reps');
        Schema::dropIfExists('disnew_route_customers');
        Schema::dropIfExists('disnew_routes');
        Schema::dropIfExists('disnew_territories');
    }
};
