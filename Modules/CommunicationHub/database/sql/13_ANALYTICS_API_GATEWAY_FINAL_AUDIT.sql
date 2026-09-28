-- Communication Hub Final Milestone - Analytics, Reports, API Gateway and Audit Centre
-- Run inside EACH tenant database. Do NOT include a database name.

CREATE TABLE IF NOT EXISTS `communication_hub_report_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `report_key` VARCHAR(191) NOT NULL,
  `report_name` VARCHAR(191) NOT NULL,
  `period_from` DATE NULL,
  `period_to` DATE NULL,
  `filters_json` LONGTEXT NULL,
  `summary_json` LONGTEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ch_report_snap_business_idx` (`business_id`),
  KEY `ch_report_snap_location_idx` (`business_location_id`),
  KEY `ch_report_snap_key_idx` (`report_key`),
  KEY `ch_report_snap_period_idx` (`period_from`,`period_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `communication_hub_api_gateway_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `client_name` VARCHAR(191) NOT NULL,
  `token_hash` VARCHAR(191) NOT NULL,
  `allowed_channels` TEXT NULL,
  `allowed_events` TEXT NULL,
  `daily_limit` INT UNSIGNED NOT NULL DEFAULT 0,
  `monthly_limit` INT UNSIGNED NOT NULL DEFAULT 0,
  `used_today` INT UNSIGNED NOT NULL DEFAULT 0,
  `used_month` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_used_at` TIMESTAMP NULL DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ch_api_gt_business_idx` (`business_id`),
  KEY `ch_api_gt_location_idx` (`business_location_id`),
  KEY `ch_api_gt_status_idx` (`status`),
  UNIQUE KEY `ch_api_gt_token_unique` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `communication_hub_final_audit_checks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `check_key` VARCHAR(191) NOT NULL,
  `check_name` VARCHAR(191) NOT NULL,
  `category` VARCHAR(100) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `severity` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `result_message` TEXT NULL,
  `checked_by` INT UNSIGNED NULL,
  `checked_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ch_final_audit_business_idx` (`business_id`),
  KEY `ch_final_audit_category_idx` (`category`),
  KEY `ch_final_audit_status_idx` (`status`),
  UNIQUE KEY `ch_final_audit_key_unique` (`business_id`,`check_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `communication_hub_messages`
  ADD COLUMN IF NOT EXISTS `api_request_id` BIGINT UNSIGNED NULL AFTER `client_id`,
  ADD COLUMN IF NOT EXISTS `delivery_latency_ms` INT UNSIGNED NULL AFTER `sent_at`,
  ADD COLUMN IF NOT EXISTS `opened_at` TIMESTAMP NULL DEFAULT NULL AFTER `delivered_at`,
  ADD COLUMN IF NOT EXISTS `read_at` TIMESTAMP NULL DEFAULT NULL AFTER `opened_at`;

ALTER TABLE `communication_hub_api_request_logs`
  ADD COLUMN IF NOT EXISTS `business_id` INT UNSIGNED NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `business_location_id` INT UNSIGNED NULL AFTER `business_id`,
  ADD COLUMN IF NOT EXISTS `channel` VARCHAR(50) NULL AFTER `business_location_id`,
  ADD COLUMN IF NOT EXISTS `event_code` VARCHAR(191) NULL AFTER `channel`,
  ADD COLUMN IF NOT EXISTS `response_time_ms` INT UNSIGNED NULL AFTER `response_status`;

CREATE INDEX IF NOT EXISTS `ch_msg_api_req_idx` ON `communication_hub_messages` (`api_request_id`);
CREATE INDEX IF NOT EXISTS `ch_msg_channel_status_idx` ON `communication_hub_messages` (`channel`,`status`);
CREATE INDEX IF NOT EXISTS `ch_msg_business_created_idx` ON `communication_hub_messages` (`business_id`,`created_at`);
CREATE INDEX IF NOT EXISTS `ch_api_log_business_created_idx` ON `communication_hub_api_request_logs` (`business_id`,`created_at`);
CREATE INDEX IF NOT EXISTS `ch_api_log_channel_idx` ON `communication_hub_api_request_logs` (`channel`);

INSERT IGNORE INTO `communication_hub_final_audit_checks`
(`business_id`, `check_key`, `check_name`, `category`, `status`, `severity`, `created_at`, `updated_at`) VALUES
(NULL, 'tenant_scope', 'Tenant database scope verified', 'Architecture', 'pending', 'critical', NOW(), NOW()),
(NULL, 'business_scope', 'Business and location scope verified', 'Architecture', 'pending', 'critical', NOW(), NOW()),
(NULL, 'permissions', 'Communication Hub permissions verified', 'Security', 'pending', 'critical', NOW(), NOW()),
(NULL, 'queue_indexes', 'Queue and report indexes verified', 'Performance', 'pending', 'normal', NOW(), NOW()),
(NULL, 'provider_failover', 'Provider failover path verified', 'Reliability', 'pending', 'normal', NOW(), NOW()),
(NULL, 'ui_standard', 'ERP UI standard verified', 'UI', 'pending', 'normal', NOW(), NOW());
