-- StockTransferNew STN_023 Tenant SQL: Maintenance Control / Admin Follow-up

CREATE TABLE IF NOT EXISTS `stn_maintenance_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `title` varchar(191) NOT NULL,
  `task_type` varchar(50) NOT NULL DEFAULT 'general',
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('open','in_progress','on_hold','closed') NOT NULL DEFAULT 'open',
  `owner_user_id` int unsigned NULL,
  `due_date` date NULL,
  `description` text NULL,
  `created_by` int unsigned NULL,
  `closed_by` int unsigned NULL,
  `closed_at` timestamp NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `stn_maintenance_business_status_idx` (`business_id`, `status`),
  KEY `stn_maintenance_due_idx` (`business_id`, `due_date`),
  KEY `stn_maintenance_priority_idx` (`business_id`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stn_maintenance_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `maintenance_task_id` bigint unsigned NOT NULL,
  `action` varchar(100) NOT NULL,
  `remarks` text NULL,
  `created_by` int unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `stn_maintenance_logs_task_idx` (`business_id`, `maintenance_task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('stock_transfer_new.maintenance.view', 'web', NOW(), NOW()),
('stock_transfer_new.maintenance.create', 'web', NOW(), NOW()),
('stock_transfer_new.maintenance.update_status', 'web', NOW(), NOW()),
('stock_transfer_new.maintenance.calendar', 'web', NOW(), NOW());
