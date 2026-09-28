-- S 577 - Stock Adjustment New Settings upgrade
-- Run on every tenant database. The script is idempotent and does not hardcode a database name.

CREATE TABLE IF NOT EXISTS `san_stock_adjustment_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `number_prefix` VARCHAR(30) NOT NULL DEFAULT 'SAN-',
  `number_padding` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `default_adjustment_type` VARCHAR(30) NOT NULL DEFAULT 'quantity',
  `default_page_size` SMALLINT UNSIGNED NOT NULL DEFAULT 25,
  `quantity_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 4,
  `amount_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 4,
  `require_reason` TINYINT(1) NOT NULL DEFAULT 0,
  `require_location` TINYINT(1) NOT NULL DEFAULT 1,
  `require_store` TINYINT(1) NOT NULL DEFAULT 0,
  `require_approval` TINYINT(1) NOT NULL DEFAULT 1,
  `auto_submit` TINYINT(1) NOT NULL DEFAULT 0,
  `auto_post_after_approval` TINYINT(1) NOT NULL DEFAULT 0,
  `require_batch_when_available` TINYINT(1) NOT NULL DEFAULT 1,
  `hide_zero_stock_products` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_negative_stock` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_zero_unit_cost` TINYINT(1) NOT NULL DEFAULT 1,
  `allow_backdated_adjustments` TINYINT(1) NOT NULL DEFAULT 1,
  `max_backdate_days` INT UNSIGNED NULL,
  `allow_future_dated_adjustments` TINYINT(1) NOT NULL DEFAULT 1,
  `batch_selection_method` VARCHAR(20) NOT NULL DEFAULT 'fefo',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `san_settings_business_unique` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `san_stock_adjustment_account_mappings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `effective_from` TIMESTAMP NULL,
  `adjustment_type` VARCHAR(30) NOT NULL DEFAULT 'quantity',
  `category_id` BIGINT UNSIGNED NULL,
  `sub_category_id` BIGINT UNSIGNED NULL,
  `account_to_link_id` BIGINT UNSIGNED NULL,
  `increase_account_id` BIGINT UNSIGNED NULL,
  `decrease_account_id` BIGINT UNSIGNED NULL,
  `stock_account_group_id` BIGINT UNSIGNED NULL,
  `stock_account_id` BIGINT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `san_mapping_business_idx` (`business_id`),
  KEY `san_mapping_effective_idx` (`effective_from`),
  KEY `san_mapping_type_idx` (`adjustment_type`),
  KEY `san_mapping_category_idx` (`category_id`),
  KEY `san_mapping_sub_category_idx` (`sub_category_id`),
  KEY `san_mapping_account_link_idx` (`account_to_link_id`),
  KEY `san_mapping_increase_account_idx` (`increase_account_id`),
  KEY `san_mapping_decrease_account_idx` (`decrease_account_id`),
  KEY `san_mapping_stock_group_idx` (`stock_account_group_id`),
  KEY `san_mapping_stock_account_idx` (`stock_account_id`),
  KEY `san_mapping_active_idx` (`is_active`),
  KEY `san_mapping_lookup_idx` (`business_id`,`adjustment_type`,`category_id`,`sub_category_id`,`is_active`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `san_stock_adjustment_account_mappings`
  ADD COLUMN IF NOT EXISTS `increase_account_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `decrease_account_id` BIGINT UNSIGNED NULL;

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.settings','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (
    SELECT 1 FROM `permissions`
    WHERE `name`='stock_adjustment_new.settings' AND `guard_name`='web'
  );
