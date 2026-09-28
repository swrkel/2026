-- HOTELMGT_041_SQL.sql
-- Parcel 041 only: Staff Training & Compliance
-- Global tenant SQL: execute inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_staff_training_courses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `course_code` VARCHAR(60) NOT NULL,
  `course_name` VARCHAR(191) NOT NULL,
  `department` VARCHAR(100) NULL,
  `training_type` VARCHAR(60) NOT NULL DEFAULT 'service',
  `validity_days` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_mandatory` TINYINT(1) NOT NULL DEFAULT 0,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `description` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_stc_business_course_code_unique` (`business_id`, `course_code`),
  KEY `hm_stc_scope_idx` (`business_id`, `business_location_id`, `department`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_staff_training_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `session_no` VARCHAR(60) NOT NULL,
  `trainer_name` VARCHAR(191) NULL,
  `training_date` DATE NOT NULL,
  `start_time` TIME NULL,
  `end_time` TIME NULL,
  `venue` VARCHAR(191) NULL,
  `capacity` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(40) NOT NULL DEFAULT 'planned',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_sts_business_session_no_unique` (`business_id`, `session_no`),
  KEY `hm_sts_scope_idx` (`business_id`, `business_location_id`, `course_id`, `training_date`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_staff_training_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `course_id` BIGINT UNSIGNED NULL,
  `session_id` BIGINT UNSIGNED NOT NULL,
  `staff_id` BIGINT UNSIGNED NOT NULL,
  `attendance_status` VARCHAR(40) NOT NULL DEFAULT 'assigned',
  `score` DECIMAL(8,2) NULL,
  `result_status` VARCHAR(40) NOT NULL DEFAULT 'assigned',
  `completed_at` DATE NULL,
  `valid_until` DATE NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_str_session_staff_unique` (`business_id`, `session_id`, `staff_id`),
  KEY `hm_str_scope_idx` (`business_id`, `business_location_id`, `course_id`, `staff_id`, `result_status`),
  KEY `hm_str_valid_until_idx` (`business_id`, `valid_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
