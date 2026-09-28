-- Products New Stage 017 - Product Command Center
-- Global tenant-safe SQL. Execute inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `products_new_command_center_preferences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `visible_widgets` JSON NULL,
  `quick_actions` JSON NULL,
  `saved_searches` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pcc_preferences_business_user_idx` (`business_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_command_center_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `summary_payload` JSON NULL,
  `inventory_payload` JSON NULL,
  `finance_payload` JSON NULL,
  `sales_payload` JSON NULL,
  `purchase_payload` JSON NULL,
  `alerts_payload` JSON NULL,
  `integration_payload` JSON NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pcc_snapshots_business_product_idx` (`business_id`, `product_id`),
  KEY `pcc_snapshots_created_by_idx` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('products_new.command_center.view', 'web', NOW(), NOW()),
('products_new.command_center.snapshot', 'web', NOW(), NOW()),
('products_new.command_center.actions', 'web', NOW(), NOW());
