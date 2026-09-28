/* Products New Stage 005 - Barcode, Label Templates and Product Media Center */

CREATE TABLE IF NOT EXISTS `products_new_barcode_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `paper_size` VARCHAR(50) NULL DEFAULT 'A4',
  `label_width` DECIMAL(10,3) NULL DEFAULT 38.000,
  `label_height` DECIMAL(10,3) NULL DEFAULT 25.000,
  `labels_per_row` INT NULL DEFAULT 3,
  `barcode_type` VARCHAR(50) NULL DEFAULT 'CODE128',
  `settings` JSON NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_barcode_templates_business_id_index` (`business_id`),
  KEY `products_new_barcode_templates_default_index` (`business_id`, `is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_media` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `media_type` VARCHAR(50) NOT NULL DEFAULT 'image',
  `title` VARCHAR(191) NULL,
  `file_name` VARCHAR(191) NULL,
  `file_path` TEXT NULL,
  `external_url` TEXT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `metadata` JSON NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_media_business_product_index` (`business_id`, `product_id`),
  KEY `products_new_media_type_index` (`business_id`, `media_type`),
  KEY `products_new_media_primary_index` (`business_id`, `product_id`, `is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `products_new_barcode_queue`
  ADD COLUMN IF NOT EXISTS `template_id` BIGINT UNSIGNED NULL AFTER `variation_id`,
  ADD COLUMN IF NOT EXISTS `barcode_value` VARCHAR(191) NULL AFTER `template_id`;

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.media.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.media.view');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.media.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.media.create');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.barcode.templates', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.barcode.templates');
