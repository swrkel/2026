-- HOTELMGT_021_SQL.sql
-- Hotel Management Parcel 021 only
-- Guest Communication bridge tables for hotel-specific SMS/email/notification templates and logs.
-- Apply to each tenant database that uses Hotel Management.

CREATE TABLE IF NOT EXISTS `hm_guest_message_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `code` VARCHAR(60) NULL,
  `name` VARCHAR(120) NOT NULL,
  `channel` VARCHAR(30) NOT NULL DEFAULT 'sms',
  `event_key` VARCHAR(60) NULL,
  `message_body` TEXT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_gmt_business_location_idx` (`business_id`, `business_location_id`),
  KEY `hm_gmt_event_idx` (`event_key`, `channel`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_guest_message_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `guest_id` BIGINT UNSIGNED NULL,
  `reservation_id` BIGINT UNSIGNED NULL,
  `folio_id` BIGINT UNSIGNED NULL,
  `template_id` BIGINT UNSIGNED NULL,
  `channel` VARCHAR(30) NOT NULL DEFAULT 'sms',
  `recipient` VARCHAR(191) NOT NULL,
  `subject` VARCHAR(191) NULL,
  `message_body` TEXT NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `source` VARCHAR(60) NULL DEFAULT 'hotel_management',
  `external_message_id` VARCHAR(191) NULL,
  `error_message` TEXT NULL,
  `queued_by` BIGINT UNSIGNED NULL,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_gml_business_location_idx` (`business_id`, `business_location_id`),
  KEY `hm_gml_guest_idx` (`guest_id`),
  KEY `hm_gml_reservation_idx` (`reservation_id`),
  KEY `hm_gml_folio_idx` (`folio_id`),
  KEY `hm_gml_status_idx` (`status`, `channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hm_guest_message_templates`
(`business_id`, `business_location_id`, `code`, `name`, `channel`, `event_key`, `message_body`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'booking_confirm', 'Booking Confirmation', 'sms', 'reservation_created', 'Dear {guest_name}, your reservation {reservation_no} is confirmed. Thank you.', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hm_guest_message_templates` WHERE `code` = 'booking_confirm');

INSERT INTO `hm_guest_message_templates`
(`business_id`, `business_location_id`, `code`, `name`, `channel`, `event_key`, `message_body`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'checkin_welcome', 'Check-in Welcome', 'sms', 'check_in', 'Dear {guest_name}, welcome to {hotel_name}. We wish you a pleasant stay.', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hm_guest_message_templates` WHERE `code` = 'checkin_welcome');

INSERT INTO `hm_guest_message_templates`
(`business_id`, `business_location_id`, `code`, `name`, `channel`, `event_key`, `message_body`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'checkout_thanks', 'Check-out Thank You', 'sms', 'check_out', 'Dear {guest_name}, thank you for staying with us. We hope to welcome you again.', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hm_guest_message_templates` WHERE `code` = 'checkout_thanks');
