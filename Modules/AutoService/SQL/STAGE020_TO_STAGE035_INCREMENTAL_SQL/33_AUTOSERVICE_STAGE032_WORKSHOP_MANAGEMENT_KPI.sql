-- AutoService Stage 032 - Workshop Management KPI / Technician Performance / Bay Utilisation / Repeat Repair Tracking
-- Run this on every tenant database. Do not hard-code tenant database names.

CREATE TABLE IF NOT EXISTS `auto_service_repeat_repairs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NULL,
  `location_id` int unsigned NULL,
  `job_id` bigint unsigned NOT NULL,
  `vehicle_id` bigint unsigned NULL,
  `contact_id` bigint unsigned NULL,
  `reason` varchar(255) NOT NULL,
  `corrective_action` text NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('open','reviewed','resolved','closed') NOT NULL DEFAULT 'open',
  `created_by` bigint unsigned NULL,
  `reviewed_by` bigint unsigned NULL,
  `reviewed_at` timestamp NULL,
  `closed_by` bigint unsigned NULL,
  `closed_at` timestamp NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `as_repeat_business_idx` (`business_id`),
  KEY `as_repeat_location_idx` (`location_id`),
  KEY `as_repeat_job_idx` (`job_id`),
  KEY `as_repeat_vehicle_idx` (`vehicle_id`),
  KEY `as_repeat_contact_idx` (`contact_id`),
  KEY `as_repeat_status_idx` (`status`),
  KEY `as_repeat_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.management_kpi.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.management_kpi.view');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.management_kpi.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.management_kpi.manage');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.repeat_repairs.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.repeat_repairs.view');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.repeat_repairs.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.repeat_repairs.manage');
