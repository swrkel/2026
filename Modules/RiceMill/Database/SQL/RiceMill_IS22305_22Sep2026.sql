-- Rice Mill IS22305 - 22 Sep 2026
-- Standalone operational payment capture for Receive Paddy,
-- Milling / Production and Sales / Dispatch.
-- Safe for repeat import.

CREATE TABLE IF NOT EXISTS `rcm_operational_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `source_type` VARCHAR(60) NOT NULL,
  `source_id` BIGINT UNSIGNED NOT NULL,
  `payment_context` VARCHAR(30) NOT NULL,
  `payment_method` VARCHAR(60) NOT NULL,
  `payment_method_label` VARCHAR(120) NULL,
  `payment_account_id` BIGINT UNSIGNED NOT NULL,
  `payment_account_name` VARCHAR(191) NULL,
  `amount` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `event_type` VARCHAR(80) NOT NULL,
  `meta` JSON NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `finance_outbox_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `posted_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rcm_operational_payment_source_unique` (`business_id`,`source_type`,`source_id`),
  KEY `rcm_operational_payment_business_idx` (`business_id`),
  KEY `rcm_operational_payment_status_idx` (`status`),
  KEY `rcm_operational_payment_outbox_idx` (`finance_outbox_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
