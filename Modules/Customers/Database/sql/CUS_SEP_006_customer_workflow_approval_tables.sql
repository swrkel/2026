-- CUS_SEP_006 Customer Workflow & Approval Separation
-- Run in each tenant database where the Customers module is enabled.

CREATE TABLE IF NOT EXISTS `customer_workflow_approvals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `contact_id` INT UNSIGNED NOT NULL,
  `workflow_type` VARCHAR(50) NOT NULL DEFAULT 'customer_approval',
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `current_value` VARCHAR(191) NULL,
  `requested_value` VARCHAR(191) NULL,
  `reason` TEXT NULL,
  `remarks` TEXT NULL,
  `requested_by` INT UNSIGNED NULL,
  `approved_by` INT UNSIGNED NULL,
  `approved_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_workflow_approvals_business_id_index` (`business_id`),
  KEY `customer_workflow_approvals_contact_id_index` (`contact_id`),
  KEY `customer_workflow_approvals_workflow_type_status_index` (`workflow_type`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_workflow_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `contact_id` INT UNSIGNED NOT NULL,
  `workflow_type` VARCHAR(50) NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `old_value` VARCHAR(191) NULL,
  `new_value` VARCHAR(191) NULL,
  `remarks` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_workflow_histories_business_id_index` (`business_id`),
  KEY `customer_workflow_histories_contact_id_index` (`contact_id`),
  KEY `customer_workflow_histories_workflow_type_index` (`workflow_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
