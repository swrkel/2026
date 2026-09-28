-- ============================================================================
-- Church Management — install
--
-- MariaDB 10.2+. Run in each tenant database. Safe to re-run.
--
-- BEFORE RUNNING: select the tenant database in phpMyAdmin's left-hand panel,
-- or edit the USE line below. Running with information_schema selected is
-- refused with "#1044 Access denied".
--
-- This is the same schema the module migration creates
-- (Database/Migrations/2026_09_01_000001_create_church_management_tables.php).
-- Use EITHER the migration OR this file. Both are guarded, so running both is
-- harmless, but there is no reason to.
--
-- Every table is prefixed chc_, so the module's data is identifiable in a
-- database shared with a hundred other modules and can be dropped as a set.
--
-- NO FOREIGN KEYS, matching the other vertical modules here. Relationships are
-- enforced in application code, so installing onto a tenant carrying legacy or
-- partial data cannot fail on a constraint.
-- ============================================================================

-- >>> EDIT THIS LINE for the tenant you are installing into.
-- USE `your_tenant_database`;


-- ----------------------------------------------------------------------------
-- Families. Created first: a member points at one.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chc_families` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id`          INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `family_code`          VARCHAR(50) NULL,
  `family_name`          VARCHAR(191) NOT NULL,
  -- The member who represents the household. Nullable: a family is usually
  -- created first and its head chosen from its members afterwards.
  `head_member_id`       BIGINT UNSIGNED NULL,
  `phone`                VARCHAR(50) NULL,
  `email`                VARCHAR(191) NULL,
  `address`              TEXT NULL,
  `city`                 VARCHAR(100) NULL,
  `notes`                TEXT NULL,
  `is_active`            TINYINT(1) NOT NULL DEFAULT 1,
  `created_by`           INT UNSIGNED NULL,
  `updated_by`           INT UNSIGNED NULL,
  `created_at`           TIMESTAMP NULL,
  `updated_at`           TIMESTAMP NULL,
  `deleted_at`           TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `chc_families_business_id_index` (`business_id`),
  KEY `chc_families_business_location_id_index` (`business_location_id`),
  KEY `chc_families_head_member_id_index` (`head_member_id`),
  KEY `chc_fam_biz_code_idx` (`business_id`,`family_code`),
  KEY `chc_fam_biz_name_idx` (`business_id`,`family_name`),
  KEY `chc_fam_biz_active_idx` (`business_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- Members.
--
-- membership_status is a status rather than a deletion, because a
-- congregation's history matters and a departed member may return.
--
-- full_name is stored rather than assembled on every read, so the list can sort
-- and search on one indexed value instead of a CONCAT no index can serve.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chc_members` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id`          INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `member_code`          VARCHAR(50) NULL,
  `first_name`           VARCHAR(100) NOT NULL,
  `last_name`            VARCHAR(100) NULL,
  `full_name`            VARCHAR(191) NULL,
  `family_id`            BIGINT UNSIGNED NULL,
  `family_role`          VARCHAR(50) NULL,
  `gender`               ENUM('male','female','other') NULL,
  `date_of_birth`        DATE NULL,
  `marital_status`       ENUM('single','married','widowed','divorced','other') NULL,
  `phone`                VARCHAR(50) NULL,
  `whatsapp`             VARCHAR(50) NULL,
  `email`                VARCHAR(191) NULL,
  `address`              TEXT NULL,
  `city`                 VARCHAR(100) NULL,
  `joined_date`          DATE NULL,
  `baptism_date`         DATE NULL,
  `confirmation_date`    DATE NULL,
  `membership_status`    ENUM('member','visitor','inactive','departed') NOT NULL DEFAULT 'member',
  `occupation`           VARCHAR(191) NULL,
  `notes`                TEXT NULL,
  `photo`                VARCHAR(255) NULL,
  `is_active`            TINYINT(1) NOT NULL DEFAULT 1,
  `created_by`           INT UNSIGNED NULL,
  `updated_by`           INT UNSIGNED NULL,
  `created_at`           TIMESTAMP NULL,
  `updated_at`           TIMESTAMP NULL,
  `deleted_at`           TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `chc_members_business_id_index` (`business_id`),
  KEY `chc_members_business_location_id_index` (`business_location_id`),
  KEY `chc_members_family_id_index` (`family_id`),
  KEY `chc_mem_biz_code_idx` (`business_id`,`member_code`),
  KEY `chc_mem_biz_name_idx` (`business_id`,`full_name`),
  KEY `chc_mem_biz_status_idx` (`business_id`,`membership_status`),
  KEY `chc_mem_biz_family_idx` (`business_id`,`family_id`),
  KEY `chc_mem_biz_active_idx` (`business_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- Settings. Key/value rather than a wide table, so a later phase can add a
-- setting without a migration on every tenant.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chc_settings` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id`   INT UNSIGNED NOT NULL,
  `setting_key`   VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL,
  `created_at`    TIMESTAMP NULL,
  `updated_at`    TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chc_set_biz_key_unique` (`business_id`,`setting_key`),
  KEY `chc_settings_business_id_index` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- VERIFY — all three should be listed.
-- ============================================================================
SHOW TABLES LIKE 'cm\_%';


-- ============================================================================
-- UNINSTALL (destructive — removes every Church Management record).
-- Deliberately left commented out.
--
--   DROP TABLE IF EXISTS `chc_settings`;
--   DROP TABLE IF EXISTS `chc_members`;
--   DROP TABLE IF EXISTS `chc_families`;
-- ============================================================================
