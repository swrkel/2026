-- Task 8051 - central idle-banner management settings.
-- Run against the CENTRAL database only if Laravel migrations are not being used.

CREATE TABLE IF NOT EXISTS `banner_idle_settings` (
  `id` BIGINT UNSIGNED NOT NULL,
  `idle_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
  `all_tenants` TINYINT(1) NOT NULL DEFAULT 1,
  `tenant_ids` TEXT NULL,
  `all_businesses` TINYINT(1) NOT NULL DEFAULT 1,
  `business_targets` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `banner_idle_settings`
(`id`, `idle_minutes`, `all_tenants`, `tenant_ids`, `all_businesses`, `business_targets`, `created_at`, `updated_at`)
VALUES
(1, 0, 1, NULL, 1, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);
