-- Membership New - Membership Settings / Regions
-- Safe patch: no hard-coded database name; existing tables/data are preserved.

CREATE TABLE IF NOT EXISTS `mn_regions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `region_no` varchar(50) NOT NULL,
  `region` varchar(150) NOT NULL,
  `added_by` varchar(191) DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mn_regions_business_region_no_unique` (`business_id`,`region_no`),
  KEY `mn_regions_business_id_index` (`business_id`),
  KEY `mn_regions_date_index` (`date`),
  KEY `mn_regions_created_by_index` (`created_by`),
  KEY `mn_regions_business_date_index` (`business_id`,`date`),
  KEY `mn_regions_business_region_index` (`business_id`,`region`),
  KEY `mn_regions_business_deleted_date_id_index` (`business_id`,`deleted_at`,`date`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
