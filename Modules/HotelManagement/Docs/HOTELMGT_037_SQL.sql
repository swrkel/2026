-- HOTELMGT_037_SQL.sql
-- Hotel Management Parcel 037 only
-- Security & Key Control: room keys/cards, visitor passes, security incident register
-- Global SQL only: execute inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_room_key_cards` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `key_no` VARCHAR(60) NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `reservation_id` BIGINT UNSIGNED NULL,
  `guest_name` VARCHAR(160) NULL,
  `issued_at` DATETIME NULL,
  `expires_at` DATETIME NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'issued',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_room_key_cards_business_status_idx` (`business_id`, `status`),
  KEY `hm_room_key_cards_location_idx` (`business_location_id`),
  KEY `hm_room_key_cards_room_idx` (`room_id`),
  KEY `hm_room_key_cards_reservation_idx` (`reservation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_visitor_passes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `pass_no` VARCHAR(60) NULL,
  `visitor_name` VARCHAR(160) NOT NULL,
  `mobile` VARCHAR(40) NULL,
  `nic_no` VARCHAR(80) NULL,
  `guest_name` VARCHAR(160) NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `purpose` VARCHAR(160) NULL,
  `check_in_at` DATETIME NULL,
  `check_out_at` DATETIME NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'checked_in',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_visitor_passes_business_status_idx` (`business_id`, `status`),
  KEY `hm_visitor_passes_location_idx` (`business_location_id`),
  KEY `hm_visitor_passes_room_idx` (`room_id`),
  KEY `hm_visitor_passes_mobile_idx` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_security_incidents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `incident_no` VARCHAR(60) NULL,
  `incident_date` DATE NULL,
  `incident_time` VARCHAR(20) NULL,
  `incident_type` VARCHAR(80) NOT NULL,
  `severity` VARCHAR(40) NOT NULL DEFAULT 'medium',
  `location_reference` VARCHAR(160) NULL,
  `reported_by` VARCHAR(160) NULL,
  `guest_name` VARCHAR(160) NULL,
  `description` TEXT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'open',
  `action_taken` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_security_incidents_business_status_idx` (`business_id`, `status`),
  KEY `hm_security_incidents_location_idx` (`business_location_id`),
  KEY `hm_security_incidents_date_idx` (`incident_date`),
  KEY `hm_security_incidents_severity_idx` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
