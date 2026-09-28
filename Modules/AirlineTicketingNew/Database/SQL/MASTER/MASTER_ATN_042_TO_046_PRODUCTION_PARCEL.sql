-- ATN-042 TO ATN-046 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_backup_records` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`backup_type` VARCHAR(50) NOT NULL,
`storage_disk` VARCHAR(50) NOT NULL,
`file_path` VARCHAR(500) NULL,
`file_size` BIGINT UNSIGNED NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'pending',
`error_message` TEXT NULL,
`metadata_json` JSON NULL,
`started_at` DATETIME NULL,
`completed_at` DATETIME NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_backup_records_idx` (`business_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_scheduled_task_logs` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`task_code` VARCHAR(100) NOT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'pending',
`context_json` JSON NULL,
`error_message` TEXT NULL,
`started_at` DATETIME NULL,
`completed_at` DATETIME NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_scheduled_task_logs_idx` (`business_id`,`task_code`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_performance_metrics` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
`business_id` BIGINT UNSIGNED NOT NULL,
`metric_code` VARCHAR(100) NOT NULL,
`metric_value` DECIMAL(22,4) NOT NULL DEFAULT 0,
`context_json` JSON NULL,
`recorded_at` DATETIME NOT NULL,
`created_at` TIMESTAMP NULL,
`updated_at` TIMESTAMP NULL,
KEY `atn_performance_metrics_idx` (`business_id`,`metric_code`,`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.backups.view' AS name
UNION ALL SELECT 'airline_ticketing_new.backups.create' AS name
UNION ALL SELECT 'airline_ticketing_new.monitoring.view' AS name
UNION ALL SELECT 'airline_ticketing_new.deployment.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.scheduler.run' AS name
UNION ALL SELECT 'airline_ticketing_new.certification.run' AS name) p
WHERE NOT EXISTS(
    SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);
