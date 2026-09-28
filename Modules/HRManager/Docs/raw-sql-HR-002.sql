-- HR-002 Employee Management raw SQL
-- Global tenant SQL: run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hr_employees` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_code` VARCHAR(50) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NULL,
  `display_name` VARCHAR(150) NULL,
  `nic_no` VARCHAR(50) NULL,
  `passport_no` VARCHAR(50) NULL,
  `gender` VARCHAR(20) NULL,
  `date_of_birth` DATE NULL,
  `mobile` VARCHAR(30) NULL,
  `phone` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `address_line_1` VARCHAR(255) NULL,
  `address_line_2` VARCHAR(255) NULL,
  `city` VARCHAR(100) NULL,
  `state` VARCHAR(100) NULL,
  `country` VARCHAR(100) NULL,
  `postal_code` VARCHAR(30) NULL,
  `department_id` BIGINT UNSIGNED NULL,
  `designation_id` BIGINT UNSIGNED NULL,
  `branch_id` BIGINT UNSIGNED NULL,
  `shift_id` BIGINT UNSIGNED NULL,
  `joining_date` DATE NULL,
  `employment_type` VARCHAR(50) NOT NULL DEFAULT 'full_time',
  `employee_status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `basic_salary` DECIMAL(20,4) NULL,
  `bank_name` VARCHAR(150) NULL,
  `bank_branch` VARCHAR(150) NULL,
  `bank_account_no` VARCHAR(100) NULL,
  `epf_no` VARCHAR(100) NULL,
  `etf_no` VARCHAR(100) NULL,
  `photo_path` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hr_employees_employee_code_unique` (`employee_code`),
  KEY `hr_employees_nic_no_index` (`nic_no`),
  KEY `hr_employees_mobile_index` (`mobile`),
  KEY `hr_employees_email_index` (`email`),
  KEY `hr_employees_department_id_index` (`department_id`),
  KEY `hr_employees_designation_id_index` (`designation_id`),
  KEY `hr_employees_branch_id_index` (`branch_id`),
  KEY `hr_employees_shift_id_index` (`shift_id`),
  KEY `hr_employees_employee_status_index` (`employee_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr_employee_emergency_contacts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` BIGINT UNSIGNED NOT NULL,
  `contact_name` VARCHAR(150) NULL,
  `relationship` VARCHAR(100) NULL,
  `mobile` VARCHAR(30) NULL,
  `phone` VARCHAR(30) NULL,
  `address` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hr_employee_emergency_contacts_employee_id_index` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr_employee_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` BIGINT UNSIGNED NOT NULL,
  `document_type` VARCHAR(100) NULL,
  `document_title` VARCHAR(150) NULL,
  `document_no` VARCHAR(100) NULL,
  `issue_date` DATE NULL,
  `expiry_date` DATE NULL,
  `file_path` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hr_employee_documents_employee_id_index` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional permission seed pattern. Adapt to your permission table if the column names differ.
-- INSERT IGNORE INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
-- ('hr.employee.view','web',NOW(),NOW()),
-- ('hr.employee.create','web',NOW(),NOW()),
-- ('hr.employee.update','web',NOW(),NOW()),
-- ('hr.employee.delete','web',NOW(),NOW()),
-- ('hr.employee.export','web',NOW(),NOW());
