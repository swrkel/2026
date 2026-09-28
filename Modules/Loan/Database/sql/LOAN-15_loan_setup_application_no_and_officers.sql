-- LOAN-15 - Loan Setup tables
-- Run on each tenant database if you prefer raw SQL instead of automatic table creation.

CREATE TABLE IF NOT EXISTS `loan_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NULL,
  `created_by` int unsigned NULL,
  `updated_by` int unsigned NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_settings_business_key_unique` (`business_id`, `setting_key`),
  KEY `loan_settings_business_id_index` (`business_id`),
  KEY `loan_settings_setting_key_index` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `loan_officers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `user_id` int unsigned NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NULL,
  `mobile` varchar(30) NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `notes` text NULL,
  `created_by` int unsigned NULL,
  `updated_by` int unsigned NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_officers_business_id_index` (`business_id`),
  KEY `loan_officers_user_id_index` (`user_id`),
  KEY `loan_officers_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `loan_settings` (`business_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`)
SELECT b.id, 'loan_application_prefix', 'LA', NOW(), NOW()
FROM businesses b
WHERE NOT EXISTS (
  SELECT 1 FROM loan_settings s WHERE s.business_id = b.id AND s.setting_key = 'loan_application_prefix'
);

INSERT INTO `loan_settings` (`business_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`)
SELECT b.id, 'loan_application_starting_no', '1', NOW(), NOW()
FROM businesses b
WHERE NOT EXISTS (
  SELECT 1 FROM loan_settings s WHERE s.business_id = b.id AND s.setting_key = 'loan_application_starting_no'
);
