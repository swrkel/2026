<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Source SQL: DISNEW_011_STAGE_SQL.sql
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
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
SQL);
    }

    /**
     * Reverse the migrations.
     * Drops only disnew_ tables created in this stage.
     */
    public function down(): void
    {
        Schema::dropIfExists('disnew_deployment_logs');
        Schema::dropIfExists('disnew_permission_seeds');
        Schema::dropIfExists('disnew_saved_filters');
        Schema::dropIfExists('disnew_notification_preferences');
    }
};
