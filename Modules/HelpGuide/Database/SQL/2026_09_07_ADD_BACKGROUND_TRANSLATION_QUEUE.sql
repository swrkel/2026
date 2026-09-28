-- HelpGuide: background article translation queue
-- Safe to run more than once. Central database only.

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
