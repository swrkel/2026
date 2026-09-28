-- Membership New - Safe Master Schema Repair
-- 24 Sep 2026
-- Database-agnostic: select the correct central/tenant database first.
-- No USE statement and no database name is hard-coded.
-- Existing tables are preserved by CREATE TABLE IF NOT EXISTS.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `mn_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `location_id` int unsigned DEFAULT NULL,
  `member_code` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `full_name_second_language` varchar(255) DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `nic` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `joined_on` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mn_members_business_code_uq` (`business_id`,`member_code`),
  KEY `mn_members_business_id_index` (`business_id`),
  KEY `mn_members_location_id_index` (`location_id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure the two name fields are also added when mn_members already existed.
SET @mn_has_full_name := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'full_name');
SET @mn_sql := IF(@mn_has_full_name = 0, 'ALTER TABLE `mn_members` ADD COLUMN `full_name` varchar(255) NULL AFTER `last_name`', 'SELECT 1');
PREPARE mn_stmt FROM @mn_sql;
EXECUTE mn_stmt;
DEALLOCATE PREPARE mn_stmt;

SET @mn_has_full_name_second := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'full_name_second_language');
SET @mn_sql := IF(@mn_has_full_name_second = 0, 'ALTER TABLE `mn_members` ADD COLUMN `full_name_second_language` varchar(255) NULL AFTER `full_name`', 'SELECT 1');
PREPARE mn_stmt FROM @mn_sql;
EXECUTE mn_stmt;
DEALLOCATE PREPARE mn_stmt;

CREATE TABLE IF NOT EXISTS `mn_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `duration_days` int unsigned NOT NULL DEFAULT 30,
  `price` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mn_plans_business_name_uq` (`business_id`,`name`),
  KEY `mn_plans_business_id_index` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `location_id` int unsigned DEFAULT NULL,
  `member_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned DEFAULT NULL,
  `payment_date` date NOT NULL,
  `payment_ref_no` varchar(100) DEFAULT NULL,
  `amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `payment_method` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mn_payments_business_id_index` (`business_id`),
  KEY `mn_payments_location_id_index` (`location_id`),
  KEY `mn_payments_member_id_index` (`member_id`),
  KEY `mn_payments_plan_id_index` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_linked_businesses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `linked_business_id` int unsigned NOT NULL,
  `outlet_business_id` int unsigned DEFAULT NULL,
  `location_id` int unsigned DEFAULT NULL,
  `name` varchar(190) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mn_linked_businesses_uq` (`business_id`,`linked_business_id`),
  KEY `mn_linked_businesses_business_id_index` (`business_id`),
  KEY `mn_linked_businesses_linked_business_id_index` (`linked_business_id`),
  KEY `mn_linked_businesses_outlet_business_id_index` (`outlet_business_id`),
  KEY `mn_linked_businesses_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_point_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `outlet_business_id` int unsigned DEFAULT NULL,
  `location_id` int unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `amount_step` decimal(22,4) NOT NULL DEFAULT 1.0000,
  `points_per_amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `max_points_per_invoice` decimal(22,4) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mn_point_rules_business_id_index` (`business_id`),
  KEY `mn_point_rules_outlet_business_id_index` (`outlet_business_id`),
  KEY `mn_point_rules_location_id_index` (`location_id`),
  KEY `mn_point_rules_category_id_index` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_point_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `member_id` bigint unsigned DEFAULT NULL,
  `member_business_map_id` bigint unsigned DEFAULT NULL,
  `outlet_business_id` int unsigned DEFAULT NULL,
  `location_id` int unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `transaction_date` datetime DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `points` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `purchase_amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mn_point_transactions_business_id_index` (`business_id`),
  KEY `mn_point_transactions_member_id_index` (`member_id`),
  KEY `mn_point_transactions_member_business_map_id_index` (`member_business_map_id`),
  KEY `mn_point_transactions_transaction_date_index` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_share_holdings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `member_id` bigint unsigned NOT NULL,
  `shares` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `share_value` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `note` text DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mn_share_holdings_business_member_uq` (`business_id`,`member_id`),
  KEY `mn_share_holdings_business_id_index` (`business_id`),
  KEY `mn_share_holdings_member_id_index` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_dividend_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `dividend_date` date NOT NULL,
  `total_dividend_amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `dividend_per_share` decimal(22,6) NOT NULL DEFAULT 0.000000,
  `note` text DEFAULT NULL,
  `is_posted` tinyint(1) NOT NULL DEFAULT 0,
  `posted_at` datetime DEFAULT NULL,
  `posted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mn_dividend_batches_business_id_index` (`business_id`),
  KEY `mn_dividend_batches_dividend_date_index` (`dividend_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_dividend_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `batch_id` bigint unsigned NOT NULL,
  `member_id` bigint unsigned DEFAULT NULL,
  `central_member_id` bigint unsigned DEFAULT NULL,
  `member_business_map_id` bigint unsigned DEFAULT NULL,
  `shares` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mn_dividend_payments_business_id_index` (`business_id`),
  KEY `mn_dividend_payments_batch_id_index` (`batch_id`),
  KEY `mn_dividend_payments_member_id_index` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_identity_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `member_id` bigint unsigned NOT NULL,
  `card_no` varchar(100) NOT NULL,
  `qr_payload` text DEFAULT NULL,
  `issued_on` date DEFAULT NULL,
  `expires_on` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `blocked_at` datetime DEFAULT NULL,
  `blocked_by` bigint unsigned DEFAULT NULL,
  `blocked_reason` text DEFAULT NULL,
  `last_scanned_at` datetime DEFAULT NULL,
  `last_scanned_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mn_identity_cards_card_no_unique` (`card_no`),
  KEY `mn_identity_cards_business_id_index` (`business_id`),
  KEY `mn_identity_cards_member_id_index` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_customer_maps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `member_id` bigint unsigned NOT NULL,
  `linked_business_id` int unsigned NOT NULL,
  `linked_customer_id` bigint unsigned DEFAULT NULL,
  `member_snapshot` json DEFAULT NULL,
  `is_synced` tinyint(1) NOT NULL DEFAULT 0,
  `last_synced_at` datetime DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mn_customer_maps_uq` (`business_id`,`member_id`,`linked_business_id`),
  KEY `mn_customer_maps_business_id_index` (`business_id`),
  KEY `mn_customer_maps_member_id_index` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_central_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `central_member_code` varchar(80) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `nic` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mn_central_members_code_unique` (`central_member_code`),
  KEY `mn_central_members_mobile_index` (`mobile`),
  KEY `mn_central_members_email_index` (`email`),
  KEY `mn_central_members_nic_index` (`nic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_member_business_maps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `central_member_id` bigint unsigned NOT NULL,
  `business_id` int unsigned NOT NULL,
  `local_customer_id` bigint unsigned DEFAULT NULL,
  `local_member_id` bigint unsigned DEFAULT NULL,
  `points_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `dividend_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_activity_at` datetime DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mn_member_business_maps_uq` (`central_member_id`,`business_id`),
  KEY `mn_member_business_maps_business_id_index` (`business_id`),
  KEY `mn_member_business_maps_local_customer_id_index` (`local_customer_id`),
  KEY `mn_member_business_maps_local_member_id_index` (`local_member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_business_customer_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `member_business_map_id` bigint unsigned NOT NULL,
  `transaction_date` datetime DEFAULT NULL,
  `transaction_type` varchar(80) DEFAULT NULL,
  `debit` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `credit` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `balance` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mn_business_customer_histories_business_id_index` (`business_id`),
  KEY `mn_business_customer_histories_map_id_index` (`member_business_map_id`),
  KEY `mn_business_customer_histories_transaction_date_index` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_duplicate_candidates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `primary_central_member_id` bigint unsigned NOT NULL,
  `duplicate_central_member_id` bigint unsigned NOT NULL,
  `match_fields` json DEFAULT NULL,
  `confidence_score` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` bigint unsigned DEFAULT NULL,
  `resolution_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mn_duplicate_candidates_uq` (`primary_central_member_id`,`duplicate_central_member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_outlet_transaction_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `outlet_business_id` int unsigned DEFAULT NULL,
  `member_business_map_id` bigint unsigned DEFAULT NULL,
  `central_member_id` bigint unsigned DEFAULT NULL,
  `member_id` bigint unsigned DEFAULT NULL,
  `transaction_date` datetime DEFAULT NULL,
  `purchase_amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `earn_points` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `redeem_points` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `is_processed` tinyint(1) NOT NULL DEFAULT 0,
  `processed_at` datetime DEFAULT NULL,
  `processed_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mn_outlet_transaction_queue_business_id_index` (`business_id`),
  KEY `mn_outlet_transaction_queue_outlet_business_id_index` (`outlet_business_id`),
  KEY `mn_outlet_transaction_queue_transaction_date_index` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_merge_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `primary_central_member_id` bigint unsigned NOT NULL,
  `duplicate_central_member_id` bigint unsigned NOT NULL,
  `reason` text DEFAULT NULL,
  `merge_payload` json DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `processed_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mn_merge_requests_primary_index` (`primary_central_member_id`),
  KEY `mn_merge_requests_duplicate_index` (`duplicate_central_member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_dividend_payouts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `dividend_payment_id` bigint unsigned NOT NULL,
  `member_id` bigint unsigned DEFAULT NULL,
  `central_member_id` bigint unsigned DEFAULT NULL,
  `member_business_map_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `payment_method` varchar(80) DEFAULT NULL,
  `payment_ref_no` varchar(150) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `is_reversed` tinyint(1) NOT NULL DEFAULT 0,
  `reversed_at` datetime DEFAULT NULL,
  `reversed_by` bigint unsigned DEFAULT NULL,
  `reversal_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mn_dividend_payouts_business_id_index` (`business_id`),
  KEY `mn_dividend_payouts_payment_id_index` (`dividend_payment_id`),
  KEY `mn_dividend_payouts_member_id_index` (`member_id`),
  KEY `mn_dividend_payouts_paid_at_index` (`paid_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(120) NOT NULL,
  `entity_type` varchar(150) DEFAULT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mn_audit_logs_business_id_index` (`business_id`),
  KEY `mn_audit_logs_user_id_index` (`user_id`),
  KEY `mn_audit_logs_action_index` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_approval_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `request_type` varchar(100) NOT NULL,
  `entity_type` varchar(150) DEFAULT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `requested_by` bigint unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approval_note` text DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejected_by` bigint unsigned DEFAULT NULL,
  `rejection_note` text DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mn_approval_requests_business_id_index` (`business_id`),
  KEY `mn_approval_requests_request_type_index` (`request_type`),
  KEY `mn_approval_requests_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_business_access_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `can_view_central_profile` tinyint(1) NOT NULL DEFAULT 1,
  `can_view_other_business_history` tinyint(1) NOT NULL DEFAULT 0,
  `can_redeem_cross_business_points` tinyint(1) NOT NULL DEFAULT 1,
  `can_issue_card` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `note` text DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mn_business_access_rules_business_unique` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_error_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `error_class` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `file` text DEFAULT NULL,
  `line` int unsigned DEFAULT NULL,
  `context` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mn_error_logs_business_id_index` (`business_id`),
  KEY `mn_error_logs_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mn_import_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `import_type` varchar(100) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `total_rows` int unsigned NOT NULL DEFAULT 0,
  `success_rows` int unsigned NOT NULL DEFAULT 0,
  `failed_rows` int unsigned NOT NULL DEFAULT 0,
  `summary` json DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mn_import_batches_business_id_index` (`business_id`),
  KEY `mn_import_batches_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;


-- Membership New - Membership Settings / Regions
-- Safe patch: no hard-coded database name; existing tables/data are preserved.

CREATE TABLE IF NOT EXISTS `mn_regions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `region_no` varchar(50) NOT NULL,
  `region` varchar(150) NOT NULL,
  `added_by` varchar(191) DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mn_regions_business_region_no_unique` (`business_id`,`region_no`),
  KEY `mn_regions_business_id_index` (`business_id`),
  KEY `mn_regions_date_index` (`date`),
  KEY `mn_regions_created_by_index` (`created_by`),
  KEY `mn_regions_business_date_index` (`business_id`,`date`),
  KEY `mn_regions_business_region_index` (`business_id`,`region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membership New - Additional Membership Settings tabs
CREATE TABLE IF NOT EXISTS `mn_setting_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `setting_group` varchar(60) NOT NULL,
  `setting_key` varchar(191) NOT NULL,
  `setting_value` varchar(191) DEFAULT NULL,
  `amount` decimal(22,4) DEFAULT NULL,
  `added_by` varchar(191) DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mn_setting_options_business_group_key_unique` (`business_id`,`setting_group`,`setting_key`),
  KEY `mn_setting_options_business_id_index` (`business_id`),
  KEY `mn_setting_options_setting_group_index` (`setting_group`),
  KEY `mn_setting_options_created_by_index` (`created_by`),
  KEY `mn_setting_options_lookup_idx` (`business_id`,`setting_group`,`deleted_at`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Membership New - Member Profile Fields
-- 24 Sep 2026
-- Safe to run on the currently selected central/tenant business database.
-- No database name is hard-coded. Existing values are preserved.

SET NAMES utf8mb4;

SET @mn_has_title := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'title');
SET @mn_sql := IF(@mn_has_title = 0, 'ALTER TABLE `mn_members` ADD COLUMN `title` varchar(30) NULL AFTER `member_code`', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;

SET @mn_has_region_id := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'region_id');
SET @mn_sql := IF(@mn_has_region_id = 0, 'ALTER TABLE `mn_members` ADD COLUMN `region_id` bigint unsigned NULL AFTER `full_name_second_language`, ADD INDEX `mn_members_region_id_index` (`region_id`)', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;

SET @mn_has_other_mobiles := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'other_mobile_nos');
SET @mn_sql := IF(@mn_has_other_mobiles = 0, 'ALTER TABLE `mn_members` ADD COLUMN `other_mobile_nos` text NULL AFTER `mobile`', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;

SET @mn_has_business_name := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'business_name');
SET @mn_sql := IF(@mn_has_business_name = 0, 'ALTER TABLE `mn_members` ADD COLUMN `business_name` varchar(191) NULL AFTER `other_mobile_nos`', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;

SET @mn_has_membership_type := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'membership_type_id');
SET @mn_sql := IF(@mn_has_membership_type = 0, 'ALTER TABLE `mn_members` ADD COLUMN `membership_type_id` bigint unsigned NULL AFTER `business_name`, ADD INDEX `mn_members_membership_type_id_index` (`membership_type_id`)', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;

SET @mn_has_shares := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'no_of_shares');
SET @mn_sql := IF(@mn_has_shares = 0, 'ALTER TABLE `mn_members` ADD COLUMN `no_of_shares` decimal(22,4) NULL AFTER `membership_type_id`', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;

SET @mn_has_share_value := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'total_share_value');
SET @mn_sql := IF(@mn_has_share_value = 0, 'ALTER TABLE `mn_members` ADD COLUMN `total_share_value` decimal(22,4) NULL AFTER `no_of_shares`', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;

SET @mn_has_gender := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'gender');
SET @mn_sql := IF(@mn_has_gender = 0, 'ALTER TABLE `mn_members` ADD COLUMN `gender` varchar(30) NULL AFTER `total_share_value`', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;
