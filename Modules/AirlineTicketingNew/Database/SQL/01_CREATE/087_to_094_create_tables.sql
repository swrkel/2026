-- ATN-087 TO ATN-094 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_queue_executions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`job_code` VARCHAR(100) NOT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'pending',
`payload_json` JSON NULL,
`message` TEXT NULL,
`duration_ms` INT NULL,
`started_at` DATETIME NULL,
`completed_at` DATETIME NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_queue_execution_idx` (`business_id`,`job_code`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_health_incidents` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`incident_code` VARCHAR(100) NOT NULL,
`severity` VARCHAR(20) NOT NULL DEFAULT 'warning',
`description` TEXT NOT NULL,
`context_json` JSON NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'open',
`detected_at` DATETIME NOT NULL,
`resolved_at` DATETIME NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_health_incident_idx` (`business_id`,`incident_code`,`status`,`detected_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_module_upgrades` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`from_version` VARCHAR(40) NOT NULL,
`to_version` VARCHAR(40) NOT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'pending',
`steps_json` JSON NULL,
`error_message` TEXT NULL,
`started_at` DATETIME NULL,
`completed_at` DATETIME NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_module_upgrade_idx` (`to_version`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
