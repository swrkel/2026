-- DISNEW_015 Advanced Analytics and Reporting Large Parcel
-- Run inside every tenant database. No database name is specified intentionally.

CREATE TABLE IF NOT EXISTS `disnew_analytics_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `snapshot_date` DATE NOT NULL,
  `snapshot_type` ENUM('executive','sales','route','territory','vehicle','warehouse','customer','product','driver') NOT NULL DEFAULT 'executive',
  `reference_type` VARCHAR(60) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `orders_count` INT NOT NULL DEFAULT 0,
  `invoices_count` INT NOT NULL DEFAULT 0,
  `deliveries_count` INT NOT NULL DEFAULT 0,
  `returns_count` INT NOT NULL DEFAULT 0,
  `gross_sales` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `net_sales` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `collections` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `outstanding` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `return_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `profit_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `profit_percentage` DECIMAL(8,3) NOT NULL DEFAULT 0.000,
  `payload_json` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_analytics_snapshot_unique` (`business_id`,`business_location_id`,`snapshot_date`,`snapshot_type`,`reference_type`,`reference_id`),
  KEY `disnew_analytics_snapshot_idx` (`business_id`,`snapshot_date`,`snapshot_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_kpi_metrics` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `metric_code` VARCHAR(80) NOT NULL,
  `metric_name` VARCHAR(150) NOT NULL,
  `metric_group` VARCHAR(80) NOT NULL DEFAULT 'distribution',
  `metric_date` DATE NOT NULL,
  `metric_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `target_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `variance_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `variance_percentage` DECIMAL(8,3) NOT NULL DEFAULT 0.000,
  `status` ENUM('good','warning','critical','neutral') NOT NULL DEFAULT 'neutral',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_kpi_metric_unique` (`business_id`,`business_location_id`,`metric_code`,`metric_date`),
  KEY `disnew_kpi_metric_group_idx` (`business_id`,`metric_group`,`metric_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_scheduled_reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `report_code` VARCHAR(80) NOT NULL,
  `report_name` VARCHAR(150) NOT NULL,
  `frequency` ENUM('daily','weekly','monthly','manual') NOT NULL DEFAULT 'daily',
  `delivery_channel` ENUM('email','sms','both','download_only') NOT NULL DEFAULT 'email',
  `recipient_emails` TEXT NULL,
  `recipient_mobiles` TEXT NULL,
  `officer_group` VARCHAR(80) NULL,
  `last_run_at` TIMESTAMP NULL,
  `next_run_at` TIMESTAMP NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `filters_json` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_sched_reports_idx` (`business_id`,`business_location_id`,`status`,`next_run_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_report_delivery_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `scheduled_report_id` BIGINT UNSIGNED NULL,
  `report_code` VARCHAR(80) NOT NULL,
  `delivery_channel` VARCHAR(30) NOT NULL,
  `recipient` VARCHAR(191) NULL,
  `status` ENUM('queued','sent','failed','skipped') NOT NULL DEFAULT 'queued',
  `external_reference` VARCHAR(191) NULL,
  `message` TEXT NULL,
  `file_path` VARCHAR(255) NULL,
  `sent_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_report_delivery_idx` (`business_id`,`report_code`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_export_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `export_code` VARCHAR(80) NOT NULL,
  `export_name` VARCHAR(150) NOT NULL,
  `export_format` ENUM('csv','excel','pdf') NOT NULL DEFAULT 'excel',
  `requested_by` BIGINT UNSIGNED NULL,
  `status` ENUM('queued','processing','completed','failed') NOT NULL DEFAULT 'queued',
  `filters_json` JSON NULL,
  `file_path` VARCHAR(255) NULL,
  `error_message` TEXT NULL,
  `started_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_export_jobs_idx` (`business_id`,`business_location_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `disnew_module_permissions` (`business_id`,`permission_key`,`permission_name`,`permission_group`,`created_at`,`updated_at`) VALUES
(0,'distribution_new.analytics.view','View Advanced Analytics','Distribution New Analytics',NOW(),NOW()),
(0,'distribution_new.analytics.export','Export Analytics','Distribution New Analytics',NOW(),NOW()),
(0,'distribution_new.scheduled_reports.manage','Manage Scheduled Reports','Distribution New Analytics',NOW(),NOW()),
(0,'distribution_new.executive_dashboard.view','View Executive Dashboard','Distribution New Analytics',NOW(),NOW());
