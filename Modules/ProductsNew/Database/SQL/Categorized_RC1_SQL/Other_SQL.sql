-- Statement 24
/* =========================================================
   Products New Stage 005 - Barcode, Label Templates and Media
   ========================================================= */
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
