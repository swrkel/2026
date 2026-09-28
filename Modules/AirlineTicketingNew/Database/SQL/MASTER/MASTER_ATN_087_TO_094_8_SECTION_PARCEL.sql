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

-- ATN-087 TO ATN-094 PERFORMANCE INDEXES
ALTER TABLE `atn_tickets`
    ADD INDEX `atn_tickets_perf_idx` (`business_id`,`business_location_id`,`store_id`,`issue_date`,`status`);

ALTER TABLE `atn_reservations`
    ADD INDEX `atn_reservations_perf_idx` (`business_id`,`business_location_id`,`store_id`,`reservation_date`,`status`);

ALTER TABLE `atn_invoices`
    ADD INDEX `atn_invoices_perf_idx` (`business_id`,`invoice_date`,`status`,`due_total`);

ALTER TABLE `atn_operational_tasks`
    ADD INDEX `atn_operational_tasks_perf_idx` (`business_id`,`assigned_to`,`status`,`due_at`);

INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.performance.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.cache.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.queue.monitor' AS name
UNION ALL SELECT 'airline_ticketing_new.diagnostics.view' AS name
UNION ALL SELECT 'airline_ticketing_new.health_monitor.view' AS name
UNION ALL SELECT 'airline_ticketing_new.upgrade.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.installer.run' AS name
UNION ALL SELECT 'airline_ticketing_new.deployment.validate' AS name) p
WHERE NOT EXISTS (
    SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);
