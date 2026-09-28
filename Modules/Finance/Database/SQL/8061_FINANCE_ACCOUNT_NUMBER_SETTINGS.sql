-- Task 8061 - Finance / List Accounts / Account Settings / Account Numbers
-- Run once on each tenant/business database if migrations are not being used.

CREATE TABLE IF NOT EXISTS `finance_account_number_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `account_type_key` VARCHAR(32) NOT NULL,
  `start_number` BIGINT UNSIGNED NOT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fin_acc_num_business_type_unique` (`business_id`, `account_type_key`),
  KEY `fin_acc_num_business_idx` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
