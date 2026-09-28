-- HOTELMGT_023_SQL.sql
-- Hotel Management Parcel 023 specific SQL only.
-- Scope: Loyalty / Membership / Points ledger.

CREATE TABLE IF NOT EXISTS `hm_loyalty_tiers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `min_points` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `discount_percent` DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
  `benefits` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_loyalty_tiers_business_code_unique` (`business_id`, `code`),
  KEY `hm_loyalty_tiers_scope_idx` (`business_id`, `business_location_id`),
  KEY `hm_loyalty_tiers_points_idx` (`min_points`),
  KEY `hm_loyalty_tiers_active_idx` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_loyalty_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `guest_id` BIGINT UNSIGNED NULL,
  `tier_id` BIGINT UNSIGNED NULL,
  `member_no` VARCHAR(60) NOT NULL,
  `guest_name` VARCHAR(150) NOT NULL,
  `mobile` VARCHAR(30) NULL,
  `email` VARCHAR(120) NULL,
  `join_date` DATE NULL,
  `points_balance` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `lifetime_points` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `last_activity_at` TIMESTAMP NULL DEFAULT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_loyalty_members_business_no_unique` (`business_id`, `member_no`),
  KEY `hm_loyalty_members_scope_idx` (`business_id`, `business_location_id`),
  KEY `hm_loyalty_members_guest_idx` (`guest_id`),
  KEY `hm_loyalty_members_tier_idx` (`tier_id`),
  KEY `hm_loyalty_members_mobile_idx` (`mobile`),
  KEY `hm_loyalty_members_email_idx` (`email`),
  KEY `hm_loyalty_members_status_idx` (`status`),
  KEY `hm_loyalty_members_points_idx` (`points_balance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_loyalty_point_ledger` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `member_id` BIGINT UNSIGNED NOT NULL,
  `transaction_date` DATE NULL,
  `type` VARCHAR(20) NOT NULL DEFAULT 'earn',
  `points` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `signed_points` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `amount` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `reference_type` VARCHAR(60) NULL,
  `reference_no` VARCHAR(80) NULL,
  `remarks` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_loyalty_ledger_scope_idx` (`business_id`, `business_location_id`),
  KEY `hm_loyalty_ledger_member_idx` (`member_id`),
  KEY `hm_loyalty_ledger_date_idx` (`transaction_date`),
  KEY `hm_loyalty_ledger_type_idx` (`type`),
  KEY `hm_loyalty_ledger_reference_idx` (`reference_type`, `reference_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hm_loyalty_tiers`
(`business_id`, `business_location_id`, `code`, `name`, `min_points`, `discount_percent`, `benefits`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'SILVER', 'Silver', 0.0000, 0.0000, 'Standard member benefits', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hm_loyalty_tiers` WHERE `code` = 'SILVER');

INSERT INTO `hm_loyalty_tiers`
(`business_id`, `business_location_id`, `code`, `name`, `min_points`, `discount_percent`, `benefits`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'GOLD', 'Gold', 5000.0000, 5.0000, 'Priority service and selected discounts', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hm_loyalty_tiers` WHERE `code` = 'GOLD');

INSERT INTO `hm_loyalty_tiers`
(`business_id`, `business_location_id`, `code`, `name`, `min_points`, `discount_percent`, `benefits`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'PLATINUM', 'Platinum', 15000.0000, 10.0000, 'Premium guest benefits, priority service and selected discounts', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hm_loyalty_tiers` WHERE `code` = 'PLATINUM');
