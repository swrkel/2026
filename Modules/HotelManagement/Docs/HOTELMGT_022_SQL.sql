-- HOTELMGT_022_SQL.sql
-- Hotel Management Parcel 022 specific SQL only.
-- Scope: Guest Feedback / ratings / service recovery.

CREATE TABLE IF NOT EXISTS `hm_guest_feedback_questions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `question` VARCHAR(255) NOT NULL,
  `category` VARCHAR(60) NOT NULL DEFAULT 'general',
  `rating_scale` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_feedback_q_business_idx` (`business_id`),
  KEY `hm_feedback_q_location_idx` (`business_location_id`),
  KEY `hm_feedback_q_category_idx` (`category`),
  KEY `hm_feedback_q_active_idx` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_guest_feedback` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `guest_id` BIGINT UNSIGNED NULL,
  `reservation_id` BIGINT UNSIGNED NULL,
  `folio_id` BIGINT UNSIGNED NULL,
  `feedback_date` DATE NULL,
  `source` VARCHAR(60) NOT NULL DEFAULT 'front_desk',
  `overall_rating` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `room_rating` DECIMAL(6,2) NULL,
  `service_rating` DECIMAL(6,2) NULL,
  `food_rating` DECIMAL(6,2) NULL,
  `cleanliness_rating` DECIMAL(6,2) NULL,
  `comments` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `resolution_note` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `resolved_by` INT UNSIGNED NULL,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_feedback_business_idx` (`business_id`),
  KEY `hm_feedback_location_idx` (`business_location_id`),
  KEY `hm_feedback_guest_idx` (`guest_id`),
  KEY `hm_feedback_reservation_idx` (`reservation_id`),
  KEY `hm_feedback_folio_idx` (`folio_id`),
  KEY `hm_feedback_date_idx` (`feedback_date`),
  KEY `hm_feedback_source_idx` (`source`),
  KEY `hm_feedback_status_idx` (`status`),
  KEY `hm_feedback_scope_date_idx` (`business_id`, `business_location_id`, `feedback_date`),
  KEY `hm_feedback_scope_status_idx` (`business_id`, `business_location_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
