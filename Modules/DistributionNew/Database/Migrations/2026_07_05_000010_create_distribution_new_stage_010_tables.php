<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Source SQL: DISNEW_010_STAGE10_STANDALONE_AUDIT.sql
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
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
SQL);
    }

    /**
     * Reverse the migrations.
     * Drops only disnew_ tables created in this stage.
     */
    public function down(): void
    {
        Schema::dropIfExists('disnew_export_logs');
        Schema::dropIfExists('disnew_permission_audit_logs');
        Schema::dropIfExists('disnew_installation_steps');
        Schema::dropIfExists('disnew_health_checks');
    }
};
