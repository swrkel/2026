-- Rice Mill v36 - Packaging Materials / Usage Mapping / Material Ledger / Packing Trace
-- Incremental SQL for an EXISTING tenant database. Safe to run again.
-- Do not prefix a database name. Run inside the correct tenant database only.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rcm_packaging_materials` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `code` VARCHAR(40) NULL,
  `name` VARCHAR(150) NOT NULL,
  `unit` VARCHAR(30) NOT NULL DEFAULT 'pcs',
  `current_qty` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rcm_pack_material_business_name_unique` (`business_id`,`name`),
  KEY `rcm_pack_material_business_idx` (`business_id`),
  KEY `rcm_pack_material_business_code_idx` (`business_id`,`code`),
  KEY `rcm_pack_material_active_idx` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rcm_packaging_material_mappings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `bag_size_kg` DECIMAL(12,3) NOT NULL,
  `material_id` BIGINT UNSIGNED NOT NULL,
  `usage_per_bag` DECIMAL(20,4) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rcm_pack_material_mapping_unique` (`business_id`,`product_id`,`bag_size_kg`,`material_id`),
  KEY `rcm_pack_material_mapping_business_idx` (`business_id`),
  KEY `rcm_pack_material_mapping_product_idx` (`product_id`),
  KEY `rcm_pack_material_mapping_material_idx` (`material_id`),
  KEY `rcm_pack_material_mapping_lookup_idx` (`business_id`,`product_id`,`bag_size_kg`,`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rcm_packaging_material_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `material_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `movement_date` DATE NOT NULL,
  `movement_type` VARCHAR(40) NOT NULL,
  `quantity` DECIMAL(20,4) NOT NULL,
  `signed_quantity` DECIMAL(20,4) NOT NULL,
  `packing_batch_id` BIGINT UNSIGNED NULL,
  `reference_type` VARCHAR(60) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rcm_pack_material_move_business_idx` (`business_id`),
  KEY `rcm_pack_material_move_material_idx` (`material_id`),
  KEY `rcm_pack_material_move_date_idx` (`movement_date`),
  KEY `rcm_pack_material_move_type_idx` (`movement_type`),
  KEY `rcm_pack_material_move_packing_idx` (`packing_batch_id`),
  KEY `rcm_pack_material_move_ledger_idx` (`business_id`,`material_id`,`movement_date`),
  KEY `rcm_pack_material_move_ref_idx` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rcm_packing_sources` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `packing_line_id` BIGINT UNSIGNED NOT NULL,
  `production_batch_id` BIGINT UNSIGNED NULL,
  `quantity` DECIMAL(20,3) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rcm_packing_source_business_idx` (`business_id`),
  KEY `rcm_packing_source_line_idx` (`packing_line_id`),
  KEY `rcm_packing_source_production_idx` (`production_batch_id`),
  KEY `rcm_packing_source_batch_idx` (`business_id`,`production_batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
