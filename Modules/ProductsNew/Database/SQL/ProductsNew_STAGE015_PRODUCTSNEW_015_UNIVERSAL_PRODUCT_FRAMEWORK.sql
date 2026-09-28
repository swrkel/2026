-- ProductsNew Stage 015 - Universal Product Framework
-- Global tenant-safe SQL. Execute inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `products_new_product_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `code` VARCHAR(60) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_new_product_types_business_code_unique` (`business_id`,`code`),
  KEY `products_new_product_types_business_active_index` (`business_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_custom_fields` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_type_id` BIGINT UNSIGNED NULL,
  `label` VARCHAR(120) NOT NULL,
  `field_key` VARCHAR(80) NOT NULL,
  `field_type` VARCHAR(40) NOT NULL DEFAULT 'text',
  `options` TEXT NULL,
  `is_required` TINYINT(1) NOT NULL DEFAULT 0,
  `is_searchable` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_new_custom_fields_business_key_unique` (`business_id`,`field_key`),
  KEY `products_new_custom_fields_type_index` (`product_type_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_custom_field_values` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `field_key` VARCHAR(80) NOT NULL,
  `field_value` TEXT NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_new_custom_field_values_product_key_unique` (`product_id`,`field_key`),
  KEY `products_new_custom_field_values_key_index` (`field_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `rule_group` VARCHAR(80) NOT NULL DEFAULT 'general',
  `condition_json` LONGTEXT NULL,
  `action_json` LONGTEXT NULL,
  `severity` VARCHAR(30) NOT NULL DEFAULT 'warning',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_rules_business_group_index` (`business_id`,`rule_group`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_type_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(160) NOT NULL,
  `template_json` LONGTEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_templates_business_type_index` (`business_id`,`product_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_saved_filters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `name` VARCHAR(160) NOT NULL,
  `filter_json` LONGTEXT NULL,
  `is_shared` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_saved_filters_business_user_index` (`business_id`,`user_id`,`is_shared`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_bulk_operation_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `operation` VARCHAR(80) NOT NULL,
  `criteria_json` LONGTEXT NULL,
  `changes_json` LONGTEXT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'draft',
  `affected_count` INT NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_bulk_operation_sessions_business_status_index` (`business_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_version_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `version_no` INT NOT NULL DEFAULT 1,
  `change_type` VARCHAR(80) NOT NULL DEFAULT 'update',
  `before_json` LONGTEXT NULL,
  `after_json` LONGTEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_version_history_product_index` (`product_id`,`version_no`),
  KEY `products_new_version_history_business_index` (`business_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products_new_product_types` (`business_id`,`name`,`code`,`description`,`is_active`,`created_at`,`updated_at`)
SELECT b.id, x.name, x.code, x.description, 1, NOW(), NOW()
FROM businesses b
JOIN (
  SELECT 'Physical Product' name, 'physical_product' code, 'Standard stock item' description UNION ALL
  SELECT 'Service', 'service', 'Non-stock service item' UNION ALL
  SELECT 'Raw Material', 'raw_material', 'Manufacturing input' UNION ALL
  SELECT 'Finished Goods', 'finished_goods', 'Manufactured sale item' UNION ALL
  SELECT 'Spare Parts', 'spare_parts', 'Service and vehicle spare part' UNION ALL
  SELECT 'Fuel Product', 'fuel_product', 'Fuel or petroleum product' UNION ALL
  SELECT 'Hotel Item', 'hotel_item', 'Hotel, kitchen, housekeeping or room item' UNION ALL
  SELECT 'Medical Item', 'medical_item', 'Medical/pharmacy product with batch and expiry controls' UNION ALL
  SELECT 'Rental Item', 'rental_item', 'Asset or item available for rental' UNION ALL
  SELECT 'Bundle / Package', 'bundle_package', 'Kit, combo, package or bundle'
) x
WHERE NOT EXISTS (SELECT 1 FROM products_new_product_types t WHERE t.business_id=b.id AND t.code=x.code);
