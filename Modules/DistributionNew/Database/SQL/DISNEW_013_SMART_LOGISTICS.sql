-- DISNEW_013 Smart Logistics Large Parcel
-- Run inside every tenant database. No database name is specified intentionally.

CREATE TABLE IF NOT EXISTS `disnew_drivers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `employee_id` BIGINT UNSIGNED NULL,
  `driver_code` VARCHAR(50) NULL,
  `name` VARCHAR(150) NOT NULL,
  `mobile` VARCHAR(30) NULL,
  `nic_no` VARCHAR(60) NULL,
  `license_no` VARCHAR(80) NULL,
  `license_expiry_date` DATE NULL,
  `commission_type` ENUM('none','fixed','percentage','per_trip') NOT NULL DEFAULT 'none',
  `commission_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_drivers_business_idx` (`business_id`,`business_location_id`,`status`),
  KEY `disnew_drivers_license_expiry_idx` (`license_expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_helpers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `employee_id` BIGINT UNSIGNED NULL,
  `helper_code` VARCHAR(50) NULL,
  `name` VARCHAR(150) NOT NULL,
  `mobile` VARCHAR(30) NULL,
  `commission_type` ENUM('none','fixed','percentage','per_trip') NOT NULL DEFAULT 'none',
  `commission_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_helpers_business_idx` (`business_id`,`business_location_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_vehicle_odometer_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `trip_id` BIGINT UNSIGNED NULL,
  `reading_date` DATE NOT NULL,
  `reading_time` TIME NULL,
  `opening_odometer` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `closing_odometer` DECIMAL(22,3) NULL,
  `distance` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `source_type` VARCHAR(50) NULL,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_odometer_business_vehicle_idx` (`business_id`,`vehicle_id`,`reading_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_vehicle_fuel_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `driver_id` BIGINT UNSIGNED NULL,
  `trip_id` BIGINT UNSIGNED NULL,
  `fuel_date` DATE NOT NULL,
  `fuel_time` TIME NULL,
  `fuel_type` VARCHAR(50) NULL,
  `litres` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `odometer_reading` DECIMAL(22,3) NULL,
  `supplier_name` VARCHAR(150) NULL,
  `receipt_no` VARCHAR(80) NULL,
  `payment_method` VARCHAR(50) NULL,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_fuel_business_vehicle_idx` (`business_id`,`vehicle_id`,`fuel_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_vehicle_maintenances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `maintenance_date` DATE NOT NULL,
  `maintenance_type` VARCHAR(80) NOT NULL,
  `odometer_reading` DECIMAL(22,3) NULL,
  `next_due_date` DATE NULL,
  `next_due_odometer` DECIMAL(22,3) NULL,
  `garage_name` VARCHAR(150) NULL,
  `invoice_no` VARCHAR(80) NULL,
  `cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'completed',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_maint_business_vehicle_idx` (`business_id`,`vehicle_id`,`maintenance_date`),
  KEY `disnew_maint_due_idx` (`next_due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_vehicle_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `document_type` VARCHAR(80) NOT NULL,
  `document_no` VARCHAR(100) NULL,
  `issue_date` DATE NULL,
  `expiry_date` DATE NULL,
  `renewal_reminder_days` INT NOT NULL DEFAULT 30,
  `file_path` VARCHAR(255) NULL,
  `status` ENUM('active','expired','renewed','cancelled') NOT NULL DEFAULT 'active',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_doc_business_vehicle_idx` (`business_id`,`vehicle_id`,`document_type`),
  KEY `disnew_doc_expiry_idx` (`expiry_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_trip_expenses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `trip_id` BIGINT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NULL,
  `driver_id` BIGINT UNSIGNED NULL,
  `expense_date` DATE NOT NULL,
  `expense_category` VARCHAR(80) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reference_no` VARCHAR(100) NULL,
  `payment_method` VARCHAR(50) NULL,
  `is_reimbursable` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_trip_exp_business_idx` (`business_id`,`expense_date`,`status`),
  KEY `disnew_trip_exp_vehicle_idx` (`vehicle_id`,`trip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_trip_commissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `trip_id` BIGINT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NULL,
  `driver_id` BIGINT UNSIGNED NULL,
  `helper_id` BIGINT UNSIGNED NULL,
  `commission_for` ENUM('driver','helper','sales_rep') NOT NULL DEFAULT 'driver',
  `base_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `commission_type` ENUM('fixed','percentage','per_trip') NOT NULL DEFAULT 'fixed',
  `commission_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `commission_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('calculated','approved','paid','cancelled') NOT NULL DEFAULT 'calculated',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_trip_comm_business_idx` (`business_id`,`status`),
  KEY `disnew_trip_comm_trip_idx` (`trip_id`,`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT p.name, 'web', NOW(), NOW()
FROM (
  SELECT 'distributionnew.drivers.view' name UNION ALL
  SELECT 'distributionnew.drivers.create' UNION ALL
  SELECT 'distributionnew.drivers.update' UNION ALL
  SELECT 'distributionnew.helpers.view' UNION ALL
  SELECT 'distributionnew.helpers.create' UNION ALL
  SELECT 'distributionnew.helpers.update' UNION ALL
  SELECT 'distributionnew.fuel.view' UNION ALL
  SELECT 'distributionnew.fuel.create' UNION ALL
  SELECT 'distributionnew.odometer.view' UNION ALL
  SELECT 'distributionnew.odometer.create' UNION ALL
  SELECT 'distributionnew.maintenance.view' UNION ALL
  SELECT 'distributionnew.maintenance.create' UNION ALL
  SELECT 'distributionnew.vehicle_documents.view' UNION ALL
  SELECT 'distributionnew.vehicle_documents.create' UNION ALL
  SELECT 'distributionnew.trip_expenses.view' UNION ALL
  SELECT 'distributionnew.trip_expenses.create' UNION ALL
  SELECT 'distributionnew.trip_commissions.view' UNION ALL
  SELECT 'distributionnew.trip_commissions.approve'
) p
WHERE NOT EXISTS (SELECT 1 FROM `permissions` x WHERE x.name = p.name);
