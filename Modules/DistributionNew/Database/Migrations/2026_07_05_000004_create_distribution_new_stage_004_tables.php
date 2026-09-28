<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Source SQL: DISNEW_004_VEHICLES_AND_SUPERADMIN_LIMIT.sql
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
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
SQL);
    }

    /**
     * Reverse the migrations.
     * Drops only disnew_ tables created in this stage.
     */
    public function down(): void
    {
        Schema::dropIfExists('disnew_vehicle_limits');
        Schema::dropIfExists('disnew_vehicles');
    }
};
