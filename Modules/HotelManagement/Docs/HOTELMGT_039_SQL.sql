-- =========================================================
-- HOTELMGT_039: Staff Rostering & Attendance Control
-- Raw SQL for parcel 039 only. Execute in the selected tenant database.
-- No database name is hardcoded for multi-tenant execution.
-- =========================================================

CREATE TABLE IF NOT EXISTS `hm_staff_roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `role_name` VARCHAR(120) NOT NULL,
  `department` VARCHAR(100) NULL,
  `standard_hours` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_staff_roles_business_department_idx` (`business_id`, `department`),
  KEY `hm_staff_roles_location_idx` (`business_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_staff_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `employee_no` VARCHAR(60) NULL,
  `name` VARCHAR(160) NOT NULL,
  `mobile` VARCHAR(40) NULL,
  `email` VARCHAR(160) NULL,
  `department` VARCHAR(100) NULL,
  `role_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_staff_members_business_status_idx` (`business_id`, `status`),
  KEY `hm_staff_members_location_idx` (`business_location_id`),
  KEY `hm_staff_members_employee_no_idx` (`employee_no`),
  KEY `hm_staff_members_role_idx` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_staff_roster_shifts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `shift_no` VARCHAR(60) NULL,
  `staff_id` BIGINT UNSIGNED NULL,
  `shift_date` DATE NOT NULL,
  `start_time` TIME NULL,
  `end_time` TIME NULL,
  `department` VARCHAR(100) NULL,
  `station` VARCHAR(120) NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'planned',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_staff_roster_shifts_business_date_idx` (`business_id`, `shift_date`),
  KEY `hm_staff_roster_shifts_location_idx` (`business_location_id`),
  KEY `hm_staff_roster_shifts_staff_date_idx` (`staff_id`, `shift_date`),
  KEY `hm_staff_roster_shifts_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_staff_attendance_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `staff_id` BIGINT UNSIGNED NOT NULL,
  `attendance_date` DATE NOT NULL,
  `clock_in` TIME NULL,
  `clock_out` TIME NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'present',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_staff_attendance_logs_business_date_idx` (`business_id`, `attendance_date`),
  KEY `hm_staff_attendance_logs_location_idx` (`business_location_id`),
  KEY `hm_staff_attendance_logs_staff_date_idx` (`staff_id`, `attendance_date`),
  KEY `hm_staff_attendance_logs_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
