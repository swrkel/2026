-- Membership New - Membership Settings additional tab masters
-- Safe to run on the currently selected central or tenant database.
-- No database name is hard-coded. Existing data is not dropped or overwritten.

CREATE TABLE IF NOT EXISTS `mn_setting_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `setting_group` varchar(60) NOT NULL,
  `setting_key` varchar(191) NOT NULL,
  `setting_value` varchar(191) DEFAULT NULL,
  `amount` decimal(22,4) DEFAULT NULL,
  `added_by` varchar(191) DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mn_setting_options_business_group_key_unique` (`business_id`,`setting_group`,`setting_key`),
  KEY `mn_setting_options_business_id_index` (`business_id`),
  KEY `mn_setting_options_setting_group_index` (`setting_group`),
  KEY `mn_setting_options_created_by_index` (`created_by`),
  KEY `mn_setting_options_lookup_idx` (`business_id`,`setting_group`,`deleted_at`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
