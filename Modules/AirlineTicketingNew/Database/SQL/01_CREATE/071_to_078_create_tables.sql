-- ATN-071 TO ATN-078 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_saved_reports` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`user_id` BIGINT UNSIGNED NULL,
`report_code` VARCHAR(100) NOT NULL,
`name` VARCHAR(180) NOT NULL,
`filters_json` JSON NULL,
`columns_json` JSON NULL,
`is_shared` TINYINT(1) NOT NULL DEFAULT 0,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_saved_report_idx` (`business_id`,`user_id`,`report_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_forecast_models` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`model_code` VARCHAR(100) NOT NULL,
`name` VARCHAR(180) NOT NULL,
`metric_code` VARCHAR(100) NOT NULL,
`algorithm` VARCHAR(80) NOT NULL,
`parameters_json` JSON NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_forecast_model_uq` (`business_id`,`model_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_scheduled_reports` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`user_id` BIGINT UNSIGNED NULL,
`report_code` VARCHAR(100) NOT NULL,
`name` VARCHAR(180) NOT NULL,
`format` VARCHAR(20) NOT NULL DEFAULT 'pdf',
`frequency` VARCHAR(30) NOT NULL,
`filters_json` JSON NULL,
`recipients_json` JSON NULL,
`last_run_at` DATETIME NULL,
`next_run_at` DATETIME NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_scheduled_report_idx` (`business_id`,`next_run_at`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_report_templates` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`user_id` BIGINT UNSIGNED NULL,
`template_code` VARCHAR(100) NOT NULL,
`name` VARCHAR(180) NOT NULL,
`data_source_json` JSON NOT NULL,
`filters_json` JSON NULL,
`columns_json` JSON NULL,
`sorting_json` JSON NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_report_template_uq` (`business_id`,`template_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_kpi_alert_rules` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`name` VARCHAR(180) NOT NULL,
`metric_code` VARCHAR(100) NOT NULL,
`operator` VARCHAR(10) NOT NULL,
`threshold_value` DECIMAL(22,4) NOT NULL DEFAULT 0,
`severity` VARCHAR(20) NOT NULL DEFAULT 'normal',
`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_kpi_alert_rule_idx` (`business_id`,`metric_code`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
