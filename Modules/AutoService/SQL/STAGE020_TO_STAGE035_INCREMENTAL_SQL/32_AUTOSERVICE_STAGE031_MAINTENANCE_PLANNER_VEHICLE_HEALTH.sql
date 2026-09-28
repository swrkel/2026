-- AutoService Stage 031 - Maintenance Planner and Vehicle Health
-- Apply to each tenant database. No database name is hard-coded.

CREATE TABLE IF NOT EXISTS `auto_service_maintenance_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `contact_id` BIGINT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `job_id` BIGINT UNSIGNED NULL,
  `plan_no` VARCHAR(60) NOT NULL,
  `plan_type` VARCHAR(60) NOT NULL DEFAULT 'periodic_service',
  `current_meter` DECIMAL(15,3) NULL,
  `next_service_date` DATE NOT NULL,
  `next_service_meter` DECIMAL(15,3) NULL,
  `interval_days` INT NULL,
  `interval_meter` DECIMAL(15,3) NULL,
  `service_note` TEXT NULL,
  `recommended_parts` TEXT NULL,
  `customer_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `completion_note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `completed_by` BIGINT UNSIGNED NULL,
  `completed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `as_mp_plan_no_unique` (`plan_no`),
  KEY `as_mp_business_status_date_idx` (`business_id`, `status`, `next_service_date`),
  KEY `as_mp_vehicle_idx` (`vehicle_id`),
  KEY `as_mp_contact_idx` (`contact_id`),
  KEY `as_mp_job_idx` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('autoservice.maintenance_planner.view', 'web', NOW(), NOW()),
('autoservice.maintenance_planner.manage', 'web', NOW(), NOW()),
('autoservice.vehicle_health.view', 'web', NOW(), NOW());
