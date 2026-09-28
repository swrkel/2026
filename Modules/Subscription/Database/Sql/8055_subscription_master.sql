CREATE TABLE IF NOT EXISTS `subs_business_subscriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` VARCHAR(191) NOT NULL,
  `tenant_database` VARCHAR(191) NULL,
  `business_registry_id` BIGINT UNSIGNED NULL,
  `business_global_uid` VARCHAR(191) NULL,
  `business_name` VARCHAR(255) NOT NULL,
  `business_registered_on` DATE NOT NULL,
  `subscription_period_days` INT UNSIGNED NOT NULL,
  `subscription_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `business_mobile_numbers` TEXT NOT NULL,
  `expiry_date` DATE NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `subs_business_tenant_idx` (`tenant_id`), KEY `subs_business_uid_idx` (`business_global_uid`), KEY `subs_business_expiry_idx` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `subs_subscription_reminders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscription_id` BIGINT UNSIGNED NOT NULL,
  `reminder_no` TINYINT UNSIGNED NOT NULL,
  `days_before` INT UNSIGNED NULL,
  `message_body` TEXT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `subs_reminder_unique` (`subscription_id`,`reminder_no`), KEY `subs_reminder_subscription_idx` (`subscription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `subs_reminder_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscription_id` BIGINT UNSIGNED NOT NULL,
  `reminder_id` BIGINT UNSIGNED NULL,
  `due_date` DATE NOT NULL,
  `mobile_numbers` TEXT NULL,
  `message_body` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'sent',
  `error_message` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `subs_log_subscription_idx` (`subscription_id`), KEY `subs_log_reminder_idx` (`reminder_id`), KEY `subs_log_due_idx` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `subs_master_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(191) NOT NULL,
  `setting_value` TEXT NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `subs_master_setting_key_unique` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
