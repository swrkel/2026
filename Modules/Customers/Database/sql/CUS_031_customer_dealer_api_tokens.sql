CREATE TABLE IF NOT EXISTS `customer_portal_api_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `contact_id` INT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(191) NOT NULL,
  `device_name` VARCHAR(191) NULL,
  `ip_address` VARCHAR(45) NULL,
  `last_used_at` DATETIME NULL,
  `expires_at` DATETIME NULL,
  `revoked_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_portal_api_tokens_token_hash_unique` (`token_hash`),
  KEY `customer_portal_api_tokens_business_contact_index` (`business_id`, `contact_id`),
  KEY `customer_portal_api_tokens_expires_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
