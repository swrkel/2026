-- StockTransfer-New STN_008: Tenant DB approval workflow SQL
CREATE TABLE IF NOT EXISTS `stnew_approval_matrices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `from_location_id` bigint unsigned NULL,
  `to_location_id` bigint unsigned NULL,
  `from_store_id` bigint unsigned NULL,
  `to_store_id` bigint unsigned NULL,
  `min_amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `max_amount` decimal(22,4) NULL,
  `approval_mode` enum('sequential','parallel') NOT NULL DEFAULT 'sequential',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint unsigned NULL,
  `updated_by` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  `deleted_at` timestamp NULL,
  PRIMARY KEY (`id`), KEY `stnew_am_business` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stnew_approval_matrix_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `matrix_id` bigint unsigned NOT NULL,
  `step_order` int unsigned NOT NULL DEFAULT 1,
  `role_name` varchar(191) NULL,
  `approver_user_id` bigint unsigned NULL,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT 1,
  `sla_hours` int unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  `deleted_at` timestamp NULL,
  PRIMARY KEY (`id`), KEY `stnew_ams_matrix` (`matrix_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stnew_transfer_approval_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transfer_id` bigint unsigned NOT NULL,
  `matrix_step_id` bigint unsigned NULL,
  `step_order` int unsigned NOT NULL DEFAULT 1,
  `role_name` varchar(191) NULL,
  `approver_user_id` bigint unsigned NULL,
  `status` enum('pending','approved','rejected','returned','skipped') NOT NULL DEFAULT 'pending',
  `remarks` text NULL,
  `acted_at` timestamp NULL,
  `acted_by` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  `deleted_at` timestamp NULL,
  PRIMARY KEY (`id`), KEY `stnew_tas_transfer` (`transfer_id`), KEY `stnew_tas_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stnew_approval_delegations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `from_user_id` bigint unsigned NOT NULL,
  `to_user_id` bigint unsigned NOT NULL,
  `valid_from` date NOT NULL,
  `valid_to` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remarks` text NULL,
  `created_by` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  `deleted_at` timestamp NULL,
  PRIMARY KEY (`id`), KEY `stnew_ad_business` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `stnew_stock_transfers`
  ADD COLUMN IF NOT EXISTS `approval_mode` varchar(30) NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `approval_matrix_id` bigint unsigned NULL AFTER `approval_mode`,
  ADD COLUMN IF NOT EXISTS `current_approval_step` int unsigned NOT NULL DEFAULT 0 AFTER `approval_matrix_id`,
  ADD COLUMN IF NOT EXISTS `returned_at` timestamp NULL AFTER `approved_at`,
  ADD COLUMN IF NOT EXISTS `returned_by` bigint unsigned NULL AFTER `returned_at`,
  ADD COLUMN IF NOT EXISTS `rejected_at` timestamp NULL AFTER `returned_by`,
  ADD COLUMN IF NOT EXISTS `rejected_by` bigint unsigned NULL AFTER `rejected_at`;

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'stocktransfernew.approval_matrix', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='stocktransfernew.approval_matrix');
