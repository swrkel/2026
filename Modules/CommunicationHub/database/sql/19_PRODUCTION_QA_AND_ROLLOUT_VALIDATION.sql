-- Communication Hub Stage 018 - Production QA and Rollout Validation
-- Run inside each tenant database after applying Stage 017.
-- This file is intentionally safe and uses CREATE/ALTER IF NOT EXISTS patterns where possible.

CREATE TABLE IF NOT EXISTS communication_hub_deployment_checks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    check_key VARCHAR(191) NOT NULL,
    check_label VARCHAR(255) NOT NULL,
    check_status ENUM('pending','pass','warning','fail') NOT NULL DEFAULT 'pending',
    checked_at TIMESTAMP NULL,
    checked_by BIGINT UNSIGNED NULL,
    details JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX ch_deploy_business_idx (business_id),
    INDEX ch_deploy_location_idx (location_id),
    INDEX ch_deploy_status_idx (check_status),
    INDEX ch_deploy_key_idx (check_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO communication_hub_deployment_checks (business_id, location_id, check_key, check_label, check_status, checked_at, created_at, updated_at)
SELECT NULL, NULL, 'stage_018_installed', 'Communication Hub Stage 018 Production QA installed', 'pass', NOW(), NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM communication_hub_deployment_checks WHERE check_key = 'stage_018_installed');

-- Permission seed. Adapt to your ERP permission table if the table/columns differ.
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'communicationhub.production_qa.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'communicationhub.production_qa.view');

-- Useful final validation queries:
-- 1) Missing critical tables
SELECT required.table_name AS missing_table
FROM (
    SELECT 'communication_hub_messages' table_name UNION ALL
    SELECT 'communication_hub_templates' UNION ALL
    SELECT 'communication_hub_providers' UNION ALL
    SELECT 'communication_hub_otp_requests' UNION ALL
    SELECT 'communication_hub_campaigns' UNION ALL
    SELECT 'communication_hub_automation_rules' UNION ALL
    SELECT 'communication_hub_workflow_events' UNION ALL
    SELECT 'communication_hub_workflow_rules' UNION ALL
    SELECT 'communication_hub_in_app_notifications' UNION ALL
    SELECT 'communication_hub_live_chat_conversations' UNION ALL
    SELECT 'communication_hub_internal_messages' UNION ALL
    SELECT 'communication_hub_api_clients' UNION ALL
    SELECT 'communication_hub_audit_logs'
) required
LEFT JOIN information_schema.tables t ON t.table_schema = DATABASE() AND t.table_name = required.table_name
WHERE t.table_name IS NULL;

-- 2) Message status summary
SELECT channel, status, COUNT(*) AS total
FROM communication_hub_messages
GROUP BY channel, status
ORDER BY channel, status;

-- 3) Failed/error messages for checking before go-live
SELECT id, business_id, channel, recipient, status, created_at
FROM communication_hub_messages
WHERE status IN ('failed', 'error', 'bounced', 'cancelled')
ORDER BY id DESC
LIMIT 50;
