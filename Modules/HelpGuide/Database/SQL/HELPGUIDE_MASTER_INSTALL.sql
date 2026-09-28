-- Help Guide consolidated safe installer (8038 + 8039)
-- Safe to run more than once. All Help Guide owned tables use hg_ prefix.

CREATE TABLE IF NOT EXISTS `hg_business_module_visibility` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` VARCHAR(191) NOT NULL DEFAULT '',
  `tenant_database` VARCHAR(191) NOT NULL DEFAULT '',
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_uid` VARCHAR(191) NOT NULL DEFAULT '',
  `business_name` VARCHAR(191) NOT NULL DEFAULT '',
  `module_key` VARCHAR(191) NOT NULL,
  `business_assigned` TINYINT(1) NOT NULL DEFAULT 0,
  `help_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hg_visibility_unique` (`tenant_id`,`business_id`,`module_key`),
  KEY `hg_visibility_uid_idx` (`business_uid`,`module_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hg_articles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_key` VARCHAR(191) NOT NULL,
  `title` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `summary` TEXT NULL,
  `content` LONGTEXT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hg_article_module_slug_unique` (`module_key`,`slug`),
  KEY `hg_article_lookup_idx` (`module_key`,`status`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hg_languages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(12) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `native_name` VARCHAR(100) NULL,
  `is_system_default` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hg_languages_code_unique` (`code`),
  KEY `hg_languages_active_idx` (`is_active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hg_user_language_preferences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `language_code` VARCHAR(12) NOT NULL DEFAULT 'en',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hg_user_language_user_unique` (`user_id`),
  KEY `hg_user_language_code_idx` (`language_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hg_article_translations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id` BIGINT UNSIGNED NOT NULL,
  `language_code` VARCHAR(12) NOT NULL,
  `title` VARCHAR(191) NOT NULL,
  `summary` TEXT NULL,
  `content` LONGTEXT NULL,
  `source_hash` CHAR(64) NOT NULL DEFAULT '',
  `translation_status` VARCHAR(20) NOT NULL DEFAULT 'ready',
  `translation_error` TEXT NULL,
  `translated_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hg_article_translation_unique` (`article_id`,`language_code`),
  KEY `hg_article_translation_lang_idx` (`language_code`,`translation_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hg_languages` (`code`,`name`,`native_name`,`is_system_default`,`is_active`,`sort_order`,`created_at`,`updated_at`)
SELECT 'en','English','English',1,1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hg_languages` WHERE `code`='en');

INSERT INTO `hg_languages` (`code`,`name`,`native_name`,`is_system_default`,`is_active`,`sort_order`,`created_at`,`updated_at`) SELECT 'si','Sinhala','සිංහල',0,1,2,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `hg_languages` WHERE `code`='si');
INSERT INTO `hg_languages` (`code`,`name`,`native_name`,`is_system_default`,`is_active`,`sort_order`,`created_at`,`updated_at`) SELECT 'ta','Tamil','தமிழ்',0,1,3,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `hg_languages` WHERE `code`='ta');
INSERT INTO `hg_languages` (`code`,`name`,`native_name`,`is_system_default`,`is_active`,`sort_order`,`created_at`,`updated_at`) SELECT 'hi','Hindi','हिन्दी',0,1,4,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `hg_languages` WHERE `code`='hi');
INSERT INTO `hg_languages` (`code`,`name`,`native_name`,`is_system_default`,`is_active`,`sort_order`,`created_at`,`updated_at`) SELECT 'ar','Arabic','العربية',0,1,5,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `hg_languages` WHERE `code`='ar');
INSERT INTO `hg_languages` (`code`,`name`,`native_name`,`is_system_default`,`is_active`,`sort_order`,`created_at`,`updated_at`) SELECT 'fr','French','Français',0,1,6,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `hg_languages` WHERE `code`='fr');
INSERT INTO `hg_languages` (`code`,`name`,`native_name`,`is_system_default`,`is_active`,`sort_order`,`created_at`,`updated_at`) SELECT 'de','German','Deutsch',0,1,7,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `hg_languages` WHERE `code`='de');
INSERT INTO `hg_languages` (`code`,`name`,`native_name`,`is_system_default`,`is_active`,`sort_order`,`created_at`,`updated_at`) SELECT 'es','Spanish','Español',0,1,8,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `hg_languages` WHERE `code`='es');
INSERT INTO `hg_languages` (`code`,`name`,`native_name`,`is_system_default`,`is_active`,`sort_order`,`created_at`,`updated_at`) SELECT 'zh-CN','Chinese (Simplified)','简体中文',0,1,9,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `hg_languages` WHERE `code`='zh-CN');
INSERT INTO `hg_languages` (`code`,`name`,`native_name`,`is_system_default`,`is_active`,`sort_order`,`created_at`,`updated_at`) SELECT 'ja','Japanese','日本語',0,1,10,NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM `hg_languages` WHERE `code`='ja');

-- Background translation queue (2026-09-07)
-- One row represents one article + one target language. Article saves only
-- enqueue here; Laravel Scheduler processes a small batch in the background.
CREATE TABLE IF NOT EXISTS `hg_translation_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id` BIGINT UNSIGNED NOT NULL,
  `language_code` VARCHAR(12) NOT NULL,
  `source_hash` CHAR(64) NOT NULL DEFAULT '',
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `attempts` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `available_at` TIMESTAMP NULL DEFAULT NULL,
  `locked_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `last_error` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hg_translation_queue_unique` (`article_id`,`language_code`),
  KEY `hg_translation_queue_run_idx` (`status`,`available_at`),
  KEY `hg_translation_queue_article_idx` (`article_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
