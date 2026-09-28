ALTER TABLE `rn_dining_areas` ADD COLUMN `code` VARCHAR(191) NULL AFTER `name`;
ALTER TABLE `rn_dining_areas` ADD COLUMN `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `code`;
ALTER TABLE `rn_tables` ADD COLUMN `table_code` VARCHAR(191) NULL AFTER `name`;
ALTER TABLE `rn_tables` ADD COLUMN `qr_code` VARCHAR(191) NULL AFTER `table_code`;
ALTER TABLE `rn_tables` ADD COLUMN `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `status`;
ALTER TABLE `rn_tables` ADD COLUMN `notes` TEXT NULL AFTER `sort_order`;
