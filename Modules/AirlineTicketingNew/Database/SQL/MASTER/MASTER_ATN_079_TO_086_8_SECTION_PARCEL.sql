-- ATN-079 TO ATN-086 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_permission_profiles` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`profile_code` VARCHAR(80) NOT NULL,
`name` VARCHAR(150) NOT NULL,
`permissions_json` JSON NOT NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_by` BIGINT UNSIGNED NULL,
`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_permission_profile_uq` (`business_id`,`profile_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_security_audit_logs` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NULL,
`user_id` BIGINT UNSIGNED NULL,
`event_code` VARCHAR(100) NOT NULL,
`ip_address` VARCHAR(64) NULL,
`user_agent` TEXT NULL,
`route_name` VARCHAR(190) NULL,
`context_json` JSON NULL,
`recorded_at` DATETIME NOT NULL,
KEY `atn_security_audit_idx` (`business_id`,`event_code`,`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_encrypted_settings` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`setting_key` VARCHAR(120) NOT NULL,
`encrypted_value` LONGTEXT NOT NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_encrypted_setting_uq` (`business_id`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_api_rate_limits` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`api_key_id` BIGINT UNSIGNED NULL,
`endpoint` VARCHAR(190) NOT NULL,
`request_count` INT NOT NULL DEFAULT 0,
`window_started_at` DATETIME NOT NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_api_rate_limit_idx` (`business_id`,`endpoint`,`window_started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_enterprise_settings` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`setting_group` VARCHAR(80) NOT NULL,
`setting_key` VARCHAR(120) NOT NULL,
`setting_value_json` JSON NULL,
`is_locked` TINYINT(1) NOT NULL DEFAULT 0,
`created_by` BIGINT UNSIGNED NULL,
`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_enterprise_setting_uq` (`business_id`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.ui.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.permission_profiles.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.security_audit.view' AS name
UNION ALL SELECT 'airline_ticketing_new.encrypted_settings.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.api_security.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.mobile.view' AS name
UNION ALL SELECT 'airline_ticketing_new.enterprise_settings.view' AS name
UNION ALL SELECT 'airline_ticketing_new.enterprise_settings.manage' AS name) p
WHERE NOT EXISTS(
 SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);
