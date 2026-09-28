-- HR-003 Department / Designation / Shift Setup
-- Global tenant SQL. No database name is hardcoded. Execute inside the selected tenant database.

CREATE TABLE IF NOT EXISTS `hr_departments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `code` VARCHAR(30) NULL,
  `name` VARCHAR(120) NOT NULL,
  `description` TEXT NULL,
  `manager_employee_id` BIGINT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hr_departments_business_code_unique` (`business_id`,`code`),
  KEY `hr_departments_business_id_index` (`business_id`),
  KEY `hr_departments_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr_designations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `department_id` BIGINT UNSIGNED NULL,
  `code` VARCHAR(30) NULL,
  `name` VARCHAR(120) NOT NULL,
  `grade_level` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hr_designations_business_code_unique` (`business_id`,`code`),
  KEY `hr_designations_business_id_index` (`business_id`),
  KEY `hr_designations_department_id_index` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr_shifts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `code` VARCHAR(30) NULL,
  `name` VARCHAR(120) NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `break_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `grace_in_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `grace_out_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `overtime_after_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_night_shift` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hr_shifts_business_code_unique` (`business_id`,`code`),
  KEY `hr_shifts_business_id_index` (`business_id`),
  KEY `hr_shifts_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr_holidays` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `holiday_date` DATE NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `type` VARCHAR(50) NULL,
  `is_paid` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hr_holidays_business_id_index` (`business_id`),
  KEY `hr_holidays_location_id_index` (`location_id`),
  KEY `hr_holidays_holiday_date_index` (`holiday_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr_weekly_offs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(120) NOT NULL,
  `day_of_week` TINYINT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hr_weekly_offs_business_id_index` (`business_id`),
  KEY `hr_weekly_offs_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
