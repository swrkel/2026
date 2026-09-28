CREATE TABLE IF NOT EXISTS `loan_officers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NULL,
  `mobile` VARCHAR(30) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `notes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `loan_officers_business_id_index` (`business_id`),
  KEY `loan_officers_user_id_index` (`user_id`),
  KEY `loan_officers_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `loan_customers`
  ADD COLUMN IF NOT EXISTS `branch_id` INT UNSIGNED NULL AFTER `loan_officer`;

CREATE INDEX IF NOT EXISTS `loan_customers_branch_id_index` ON `loan_customers` (`branch_id`);
