-- HOTELMGT_043_SQL.sql
-- Parcel 043 only: Preventive Maintenance Scheduler
-- Global SQL: execute inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_preventive_maintenance_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `plan_no` VARCHAR(60) NOT NULL,
  `plan_name` VARCHAR(191) NOT NULL,
  `asset_id` BIGINT UNSIGNED NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `department` VARCHAR(100) NULL,
  `frequency_type` VARCHAR(30) NOT NULL DEFAULT 'monthly',
  `frequency_value` INT UNSIGNED NOT NULL DEFAULT 1,
  `start_date` DATE NOT NULL,
  `last_completed_date` DATE NULL,
  `next_due_date` DATE NULL,
  `priority` VARCHAR(30) NOT NULL DEFAULT 'medium',
  `estimated_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `instructions` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hm_pm_plans_business_idx` (`business_id`, `business_location_id`),
  KEY `hm_pm_plans_due_idx` (`business_id`, `next_due_date`, `status`),
  KEY `hm_pm_plans_asset_idx` (`asset_id`),
  KEY `hm_pm_plans_room_idx` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_preventive_maintenance_checklists` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `plan_id` BIGINT UNSIGNED NOT NULL,
  `check_item` VARCHAR(191) NOT NULL,
  `required_result` VARCHAR(191) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hm_pm_checklists_business_idx` (`business_id`, `business_location_id`),
  KEY `hm_pm_checklists_plan_idx` (`plan_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_preventive_maintenance_tasks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `plan_id` BIGINT UNSIGNED NULL,
  `task_no` VARCHAR(60) NOT NULL,
  `asset_id` BIGINT UNSIGNED NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `task_title` VARCHAR(191) NOT NULL,
  `due_date` DATE NOT NULL,
  `assigned_to` VARCHAR(100) NULL,
  `priority` VARCHAR(30) NOT NULL DEFAULT 'medium',
  `estimated_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `actual_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(30) NOT NULL DEFAULT 'scheduled',
  `checklist_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `completed_date` DATE NULL,
  `completion_notes` TEXT NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hm_pm_tasks_business_idx` (`business_id`, `business_location_id`),
  KEY `hm_pm_tasks_due_idx` (`business_id`, `due_date`, `status`),
  KEY `hm_pm_tasks_plan_idx` (`plan_id`),
  KEY `hm_pm_tasks_asset_idx` (`asset_id`),
  KEY `hm_pm_tasks_room_idx` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'hotel.preventive_maintenance.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'hotel.preventive_maintenance.view');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'hotel.preventive_maintenance.create', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'hotel.preventive_maintenance.create');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'hotel.preventive_maintenance.update', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'hotel.preventive_maintenance.update');
