-- ATN-027 TO ATN-031 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_gds_provider_settings` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`provider_code` VARCHAR(50) NOT NULL,`display_name` VARCHAR(150) NOT NULL,`credentials_json` LONGTEXT NULL,`options_json` JSON NULL,
`priority` INT NOT NULL DEFAULT 100,`is_active` TINYINT(1) NOT NULL DEFAULT 0,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_gds_provider_uq` (`business_id`,`provider_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_gds_request_logs` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`provider_code` VARCHAR(50) NOT NULL,
`operation` VARCHAR(80) NOT NULL,`request_payload` JSON NULL,`response_payload` JSON NULL,`status` VARCHAR(30) NOT NULL,
`error_message` TEXT NULL,`started_at` DATETIME NULL,`completed_at` DATETIME NULL,`duration_ms` INT NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,KEY `atn_gds_log_idx` (`business_id`,`provider_code`,`operation`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_flight_schedules` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`airline_id` BIGINT UNSIGNED NOT NULL,`flight_number` VARCHAR(20) NOT NULL,`origin_airport_id` BIGINT UNSIGNED NOT NULL,`destination_airport_id` BIGINT UNSIGNED NOT NULL,
`departure_at` DATETIME NOT NULL,`arrival_at` DATETIME NOT NULL,`aircraft_type_id` BIGINT UNSIGNED NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'scheduled',
`is_active` TINYINT(1) NOT NULL DEFAULT 1,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_flight_schedule_idx` (`business_id`,`departure_at`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_flight_disruptions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`flight_schedule_id` BIGINT UNSIGNED NULL,`flight_number` VARCHAR(20) NOT NULL,`disruption_type` VARCHAR(30) NOT NULL,`description` TEXT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'open',`reported_at` DATETIME NULL,`resolved_at` DATETIME NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_managed_documents` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`document_type` VARCHAR(80) NOT NULL,`owner_type` VARCHAR(190) NULL,`owner_id` BIGINT UNSIGNED NULL,`file_name` VARCHAR(255) NOT NULL,
`mime_type` VARCHAR(100) NULL,`file_size` BIGINT UNSIGNED NULL,`storage_disk` VARCHAR(50) NOT NULL,`storage_path` VARCHAR(500) NOT NULL,
`version_no` INT NOT NULL DEFAULT 1,`expiry_date` DATE NULL,`is_confidential` TINYINT(1) NOT NULL DEFAULT 0,`metadata_json` JSON NULL,`notes` TEXT NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_managed_document_idx` (`business_id`,`owner_type`,`owner_id`,`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_managed_document_versions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`document_id` BIGINT UNSIGNED NOT NULL,
`version_no` INT NOT NULL,`file_name` VARCHAR(255) NOT NULL,`storage_disk` VARCHAR(50) NOT NULL,`storage_path` VARCHAR(500) NOT NULL,
`uploaded_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_document_version_uq` (`document_id`,`version_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_workflow_definitions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`event_code` VARCHAR(80) NOT NULL,
`name` VARCHAR(150) NOT NULL,`priority` INT NOT NULL DEFAULT 100,`conditions_json` JSON NULL,`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_workflow_definition_idx` (`business_id`,`event_code`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_workflow_instances` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`workflow_definition_id` BIGINT UNSIGNED NOT NULL,
`reference_type` VARCHAR(190) NOT NULL,`reference_id` BIGINT UNSIGNED NOT NULL,`current_step` INT NOT NULL DEFAULT 1,
`status` VARCHAR(30) NOT NULL DEFAULT 'pending',`started_at` DATETIME NULL,`completed_at` DATETIME NULL,
`completion_comment` TEXT NULL,`completed_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_workflow_instance_idx` (`business_id`,`status`,`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_feature_switches` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`feature_code` VARCHAR(100) NOT NULL,
`is_enabled` TINYINT(1) NOT NULL DEFAULT 0,`config_json` JSON NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_feature_switch_uq` (`business_id`,`feature_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_api_credentials` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`credential_code` VARCHAR(100) NOT NULL,
`provider_name` VARCHAR(100) NOT NULL,`credentials_json` LONGTEXT NULL,`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`expires_at` DATETIME NULL,`last_used_at` DATETIME NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_api_credential_uq` (`business_id`,`credential_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
