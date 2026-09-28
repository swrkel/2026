-- Distribution New Stage 9 SQL only
-- Mobile/offline API foundation, approvals, audit trails, report schedules.

CREATE TABLE IF NOT EXISTS disnew_mobile_devices (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 sales_rep_id BIGINT UNSIGNED NULL,
 device_uid VARCHAR(191) NOT NULL,
 device_name VARCHAR(191) NULL,
 platform VARCHAR(50) NULL,
 app_version VARCHAR(50) NULL,
 last_sync_at DATETIME NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 registered_at DATETIME NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 UNIQUE KEY disnew_device_uid_unique (business_id, device_uid),
 INDEX disnew_mobile_devices_idx (business_id, user_id, sales_rep_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_offline_queues (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 device_id BIGINT UNSIGNED NULL,
 entity_type VARCHAR(100) NOT NULL,
 entity_local_id VARCHAR(191) NULL,
 entity_server_id BIGINT UNSIGNED NULL,
 payload_json JSON NULL,
 sync_status VARCHAR(50) NOT NULL DEFAULT 'pending',
 error_message TEXT NULL,
 synced_at DATETIME NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_offline_queue_idx (business_id, device_id, entity_type, sync_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_approval_rules (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 rule_for VARCHAR(100) NOT NULL,
 min_amount DECIMAL(22,4) NULL,
 max_amount DECIMAL(22,4) NULL,
 requires_role VARCHAR(100) NULL,
 requires_user_id BIGINT UNSIGNED NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_approval_rules_idx (business_id, location_id, rule_for)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_approval_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 rule_id BIGINT UNSIGNED NULL,
 reference_type VARCHAR(100) NOT NULL,
 reference_id BIGINT UNSIGNED NOT NULL,
 requested_by BIGINT UNSIGNED NULL,
 approved_by BIGINT UNSIGNED NULL,
 status VARCHAR(50) NOT NULL DEFAULT 'pending',
 requested_at DATETIME NULL,
 approved_at DATETIME NULL,
 remarks TEXT NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_approval_requests_idx (business_id, reference_type, reference_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_audit_trails (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 action VARCHAR(100) NOT NULL,
 entity_type VARCHAR(100) NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 before_json JSON NULL,
 after_json JSON NULL,
 ip_address VARCHAR(50) NULL,
 user_agent VARCHAR(500) NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_audit_trails_idx (business_id, entity_type, entity_id, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_api_tokens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 device_id BIGINT UNSIGNED NULL,
 token_hash VARCHAR(191) NOT NULL,
 abilities_json JSON NULL,
 last_used_at DATETIME NULL,
 expires_at DATETIME NULL,
 revoked_at DATETIME NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 UNIQUE KEY disnew_api_token_hash_unique (token_hash),
 INDEX disnew_api_tokens_idx (business_id, user_id, device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_sync_conflicts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 device_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 entity_type VARCHAR(100) NOT NULL,
 entity_local_id VARCHAR(191) NULL,
 entity_server_id BIGINT UNSIGNED NULL,
 server_payload_json JSON NULL,
 client_payload_json JSON NULL,
 resolution_status VARCHAR(50) NOT NULL DEFAULT 'open',
 resolved_by BIGINT UNSIGNED NULL,
 resolved_at DATETIME NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_sync_conflicts_idx (business_id, entity_type, resolution_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_report_schedules (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 report_key VARCHAR(100) NOT NULL,
 frequency VARCHAR(50) NOT NULL DEFAULT 'daily',
 recipient_emails TEXT NULL,
 recipient_user_ids TEXT NULL,
 last_run_at DATETIME NULL,
 next_run_at DATETIME NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 deleted_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_report_schedules_idx (business_id, location_id, report_key, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE disnew_sales_orders ADD COLUMN IF NOT EXISTS approval_status VARCHAR(50) NULL AFTER status;
ALTER TABLE disnew_sales_orders ADD COLUMN IF NOT EXISTS offline_reference VARCHAR(191) NULL AFTER approval_status;
ALTER TABLE disnew_sales_invoices ADD COLUMN IF NOT EXISTS approval_status VARCHAR(50) NULL AFTER status;
