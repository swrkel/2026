-- LA-1116 Products New – all database support
-- MySQL / MariaDB, safe to run repeatedly in every tenant database.
-- No stored procedures or CREATE ROUTINE privilege are required.

SET @db := DATABASE();

-- ---------------------------------------------------------------------------
-- PRODUCTS: only add fields that are missing.
-- ---------------------------------------------------------------------------
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='preparation_time_in_minutes')=0,
  'ALTER TABLE `products` ADD COLUMN `preparation_time_in_minutes` INT UNSIGNED NULL', 'SELECT ''products.preparation_time_in_minutes already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='sale_tax')=0,
  'ALTER TABLE `products` ADD COLUMN `sale_tax` INT NULL', 'SELECT ''products.sale_tax already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='stock_type')=0,
  'ALTER TABLE `products` ADD COLUMN `stock_type` VARCHAR(191) NULL', 'SELECT ''products.stock_type already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='date')=0,
  'ALTER TABLE `products` ADD COLUMN `date` DATE NULL', 'SELECT ''products.date already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='vat_claimed')=0,
  'ALTER TABLE `products` ADD COLUMN `vat_claimed` TINYINT(1) NOT NULL DEFAULT 0', 'SELECT ''products.vat_claimed already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- CATEGORIES: existing installations are preserved; only missing fields added.
-- ---------------------------------------------------------------------------
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='categories' AND COLUMN_NAME='vat_exempted')=0,
  'ALTER TABLE `categories` ADD COLUMN `vat_exempted` VARCHAR(10) NOT NULL DEFAULT ''No''', 'SELECT ''categories.vat_exempted already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='categories' AND COLUMN_NAME='add_related_account')=0,
  'ALTER TABLE `categories` ADD COLUMN `add_related_account` VARCHAR(30) NULL', 'SELECT ''categories.add_related_account already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='categories' AND COLUMN_NAME='cogs_account_id')=0,
  'ALTER TABLE `categories` ADD COLUMN `cogs_account_id` INT UNSIGNED NULL', 'SELECT ''categories.cogs_account_id already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='categories' AND COLUMN_NAME='sales_income_account_id')=0,
  'ALTER TABLE `categories` ADD COLUMN `sales_income_account_id` INT UNSIGNED NULL', 'SELECT ''categories.sales_income_account_id already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='categories' AND COLUMN_NAME='weight_excess_loss_applicable')=0,
  'ALTER TABLE `categories` ADD COLUMN `weight_excess_loss_applicable` TINYINT(1) NOT NULL DEFAULT 0', 'SELECT ''categories.weight_excess_loss_applicable already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='categories' AND COLUMN_NAME='vat_based_on')=0,
  'ALTER TABLE `categories` ADD COLUMN `vat_based_on` VARCHAR(20) NOT NULL DEFAULT ''sale_price''', 'SELECT ''categories.vat_based_on already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='categories' AND COLUMN_NAME='apply_vat_on')=0,
  'ALTER TABLE `categories` ADD COLUMN `apply_vat_on` VARCHAR(191) NOT NULL DEFAULT ''on_product_sub_category_settings''', 'SELECT ''categories.apply_vat_on already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='categories' AND COLUMN_NAME='vat_not_applicable')=0,
  'ALTER TABLE `categories` ADD COLUMN `vat_not_applicable` INT NOT NULL DEFAULT 0', 'SELECT ''categories.vat_not_applicable already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add useful category account indexes only when absent.
SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='categories' AND COLUMN_NAME='cogs_account_id' AND SEQ_IN_INDEX=1)=0,
  'ALTER TABLE `categories` ADD INDEX `pn_categories_cogs_account_idx` (`cogs_account_id`)', 'SELECT ''pn_categories_cogs_account_idx already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='categories' AND COLUMN_NAME='sales_income_account_id' AND SEQ_IN_INDEX=1)=0,
  'ALTER TABLE `categories` ADD INDEX `pn_categories_sales_income_idx` (`sales_income_account_id`)', 'SELECT ''pn_categories_sales_income_idx already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- PRODUCTS NEW-owned category profile: stores the HSN-code checkbox separately.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products_new_category_profiles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `category_code_is_hsn` TINYINT(1) NOT NULL DEFAULT 0,
  `settings` JSON NULL,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_new_category_profiles_category_unique` (`category_id`),
  KEY `products_new_category_profiles_business_idx` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Permissions: insert only missing records and only when permissions exists.
-- ---------------------------------------------------------------------------
SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='permissions')>0,
  'INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT ''products_new.settings.categories.view'',''web'',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`=''products_new.settings.categories.view'' AND `guard_name`=''web'')',
  'SELECT ''permissions table not found; permission insert skipped''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='permissions')>0,
  'INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT ''products_new.settings.categories.create'',''web'',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`=''products_new.settings.categories.create'' AND `guard_name`=''web'')',
  'SELECT ''permissions table not found; permission insert skipped''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='permissions')>0,
  'INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT ''products_new.settings.categories.update'',''web'',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`=''products_new.settings.categories.update'' AND `guard_name`=''web'')',
  'SELECT ''permissions table not found; permission insert skipped''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='permissions')>0,
  'INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`) SELECT ''products_new.settings.categories.delete'',''web'',NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`=''products_new.settings.categories.delete'' AND `guard_name`=''web'')',
  'SELECT ''permissions table not found; permission insert skipped''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'LA-1116 Products New database support completed safely.' AS result;
