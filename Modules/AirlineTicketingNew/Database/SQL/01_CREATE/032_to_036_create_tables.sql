-- ATN-032 TO ATN-036 CREATE SQL
CREATE TABLE IF NOT EXISTS `atn_portal_users` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`passenger_id` BIGINT UNSIGNED NULL,`corporate_customer_id` BIGINT UNSIGNED NULL,
`name` VARCHAR(150) NOT NULL,`email` VARCHAR(190) NOT NULL,`password` VARCHAR(255) NOT NULL,`portal_type` VARCHAR(30) NOT NULL DEFAULT 'customer',
`email_verified_at` DATETIME NULL,`remember_token` VARCHAR(100) NULL,`last_login_at` DATETIME NULL,`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_portal_user_uq` (`business_id`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_portal_requests` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`portal_user_id` BIGINT UNSIGNED NOT NULL,
`request_type` VARCHAR(80) NOT NULL,`reference_type` VARCHAR(190) NULL,`reference_id` BIGINT UNSIGNED NULL,`payload_json` JSON NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'pending',`requested_at` DATETIME NULL,`completed_at` DATETIME NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_portal_request_idx` (`business_id`,`portal_user_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_b2b_agents` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`agent_code` VARCHAR(40) NOT NULL,`name` VARCHAR(180) NOT NULL,`email` VARCHAR(190) NULL,`phone` VARCHAR(40) NULL,
`credit_limit` DECIMAL(22,4) NOT NULL DEFAULT 0,`available_credit` DECIMAL(22,4) NOT NULL DEFAULT 0,`currency_code` VARCHAR(3) NOT NULL,
`commission_rate` DECIMAL(12,4) NOT NULL DEFAULT 0,`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_b2b_agent_uq` (`business_id`,`agent_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_b2b_wallet_transactions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`b2b_agent_id` BIGINT UNSIGNED NOT NULL,
`transaction_date` DATETIME NOT NULL,`reference_type` VARCHAR(190) NULL,`reference_id` BIGINT UNSIGNED NULL,`description` VARCHAR(500) NULL,
`debit` DECIMAL(22,4) NOT NULL DEFAULT 0,`credit` DECIMAL(22,4) NOT NULL DEFAULT 0,`balance_after` DECIMAL(22,4) NOT NULL DEFAULT 0,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_b2b_wallet_idx` (`business_id`,`b2b_agent_id`,`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_analytics_snapshots` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`snapshot_date` DATE NOT NULL,
`metrics_json` JSON NOT NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_analytics_snapshot_uq` (`business_id`,`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
