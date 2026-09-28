-- ============================================================================
-- Church Management — phase 2: donations, attendance and events
--
-- MariaDB 10.2+. Run in each tenant database AFTER the phase 1 install.
-- Safe to re-run.
--
-- BEFORE RUNNING: select the tenant database in phpMyAdmin's left-hand panel,
-- or edit the USE line below.
--
-- Same schema as
-- Database/Migrations/2026_09_01_000002_create_church_phase_two_tables.php.
-- Use EITHER the migration OR this file.
-- ============================================================================

-- >>> EDIT THIS LINE for the tenant you are installing into.
-- USE `your_tenant_database`;


-- ----------------------------------------------------------------------------
-- Donation types — tithe, offering, building fund and so on.
--
-- A table rather than a fixed list: every congregation names these differently
-- and adds new ones without wanting a schema change.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chc_donation_types` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `name`        VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NULL,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_by`  INT UNSIGNED NULL,
  `created_at`  TIMESTAMP NULL,
  `updated_at`  TIMESTAMP NULL,
  `deleted_at`  TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `chc_donation_types_business_id_index` (`business_id`),
  KEY `chc_dtype_biz_active_idx` (`business_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- Donations.
--
-- member_id is nullable on purpose: a collection plate is anonymous. Where the
-- giver is unknown, donor_name can carry a written name without creating a
-- member record for a one-off visitor.
--
-- amount is DECIMAL(22,4), matching the money columns elsewhere in this
-- application. A float would lose cents on a large annual total - exactly the
-- number a treasurer checks.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chc_donations` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id`          INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `receipt_no`           VARCHAR(50) NULL,
  `member_id`            BIGINT UNSIGNED NULL,
  `donor_name`           VARCHAR(191) NULL,
  `donation_type_id`     BIGINT UNSIGNED NULL,
  `amount`               DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `donation_date`        DATE NOT NULL,
  `payment_method`       VARCHAR(50) NULL,
  `reference_no`         VARCHAR(100) NULL,
  `notes`                TEXT NULL,
  `created_by`           INT UNSIGNED NULL,
  `updated_by`           INT UNSIGNED NULL,
  `created_at`           TIMESTAMP NULL,
  `updated_at`           TIMESTAMP NULL,
  `deleted_at`           TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `chc_donations_business_id_index` (`business_id`),
  KEY `chc_donations_business_location_id_index` (`business_location_id`),
  KEY `chc_donations_member_id_index` (`member_id`),
  KEY `chc_donations_donation_type_id_index` (`donation_type_id`),
  KEY `chc_donations_donation_date_index` (`donation_date`),
  KEY `chc_don_biz_date_idx` (`business_id`,`donation_date`),
  KEY `chc_don_biz_member_idx` (`business_id`,`member_id`),
  KEY `chc_don_biz_type_idx` (`business_id`,`donation_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- Services — what attendance is taken against.
--
-- Separate from attendance so a service exists once, with one date, time and
-- title, rather than being repeated on every member's row.
--
-- headcount supports congregations that count the room rather than mark a
-- register. Forcing per-member rows would make the feature unusable for them.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chc_services` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id`          INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `title`                VARCHAR(191) NOT NULL,
  `service_date`         DATE NOT NULL,
  `service_time`         TIME NULL,
  `service_type`         VARCHAR(50) NULL,
  `headcount`            INT UNSIGNED NULL,
  `notes`                TEXT NULL,
  `created_by`           INT UNSIGNED NULL,
  `created_at`           TIMESTAMP NULL,
  `updated_at`           TIMESTAMP NULL,
  `deleted_at`           TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `chc_services_business_id_index` (`business_id`),
  KEY `chc_services_business_location_id_index` (`business_location_id`),
  KEY `chc_services_service_date_index` (`service_date`),
  KEY `chc_svc_biz_date_idx` (`business_id`,`service_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- Attendance — one row per member per service.
--
-- The unique key matters: without it a double-submitted register would count
-- someone twice, and every attendance figure downstream would be wrong.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chc_attendance` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `service_id`  BIGINT UNSIGNED NOT NULL,
  `member_id`   BIGINT UNSIGNED NOT NULL,
  `status`      ENUM('present','absent','excused') NOT NULL DEFAULT 'present',
  `notes`       TEXT NULL,
  `created_by`  INT UNSIGNED NULL,
  `created_at`  TIMESTAMP NULL,
  `updated_at`  TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chc_att_service_member_unique` (`service_id`,`member_id`),
  KEY `chc_attendance_business_id_index` (`business_id`),
  KEY `chc_attendance_service_id_index` (`service_id`),
  KEY `chc_attendance_member_id_index` (`member_id`),
  KEY `chc_att_biz_service_idx` (`business_id`,`service_id`),
  KEY `chc_att_biz_member_idx` (`business_id`,`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- Events.
--
-- Times are nullable so a whole-day or multi-day event does not need invented
-- ones, and end_date is optional for a single-day event.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chc_events` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id`          INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `title`                VARCHAR(191) NOT NULL,
  `event_type`           VARCHAR(50) NULL,
  `event_date`           DATE NOT NULL,
  `end_date`             DATE NULL,
  `start_time`           TIME NULL,
  `end_time`             TIME NULL,
  `venue`                VARCHAR(191) NULL,
  `organiser_member_id`  BIGINT UNSIGNED NULL,
  `description`          TEXT NULL,
  `status`               ENUM('planned','confirmed','completed','cancelled') NOT NULL DEFAULT 'planned',
  `created_by`           INT UNSIGNED NULL,
  `updated_by`           INT UNSIGNED NULL,
  `created_at`           TIMESTAMP NULL,
  `updated_at`           TIMESTAMP NULL,
  `deleted_at`           TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `chc_events_business_id_index` (`business_id`),
  KEY `chc_events_business_location_id_index` (`business_location_id`),
  KEY `chc_events_organiser_member_id_index` (`organiser_member_id`),
  KEY `chc_events_event_date_index` (`event_date`),
  KEY `chc_evt_biz_date_idx` (`business_id`,`event_date`),
  KEY `chc_evt_biz_status_idx` (`business_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- OPTIONAL — a starting set of donation types.
--
-- Commented out because the names are a congregation's own. Set the business id
-- and uncomment if you want the common ones seeded.
-- ============================================================================
-- INSERT INTO `chc_donation_types` (`business_id`,`name`,`is_active`,`created_at`,`updated_at`)
-- VALUES (1,'Tithe',1,NOW(),NOW()),
--        (1,'Offering',1,NOW(),NOW()),
--        (1,'Building Fund',1,NOW(),NOW()),
--        (1,'Mission',1,NOW(),NOW()),
--        (1,'Special Gift',1,NOW(),NOW());


-- ============================================================================
-- VERIFY — eight chc_ tables in total after phase 1 and phase 2.
-- ============================================================================
SHOW TABLES LIKE 'chc\_%';
