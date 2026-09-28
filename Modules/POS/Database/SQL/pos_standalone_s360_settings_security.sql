-- POS Standalone S360 - Settings & Security
-- Global tenant SQL. Run on each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `location_id` BIGINT UNSIGNED NULL,
    `setting_group` VARCHAR(80) NOT NULL,
    `setting_key` VARCHAR(120) NOT NULL,
    `setting_value` TEXT NULL,
    `value_type` VARCHAR(30) NOT NULL DEFAULT 'string',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED NULL,
    `updated_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `pos_settings_unique_key` (`business_id`,`location_id`,`setting_group`,`setting_key`),
    KEY `pos_settings_group_idx` (`business_id`,`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `location_id` BIGINT UNSIGNED NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(120) NOT NULL,
    `auditable_type` VARCHAR(160) NULL,
    `auditable_id` BIGINT UNSIGNED NULL,
    `old_values` LONGTEXT NULL,
    `new_values` LONGTEXT NULL,
    `ip_address` VARCHAR(64) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `pos_audit_logs_business_action_idx` (`business_id`,`action`),
    KEY `pos_audit_logs_auditable_idx` (`auditable_type`,`auditable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
