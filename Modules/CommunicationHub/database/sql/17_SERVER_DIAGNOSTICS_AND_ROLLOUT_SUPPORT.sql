-- Communication Hub Stage 016 - Server Diagnostics and Rollout Support
-- Run this in every tenant database where Communication Hub is enabled.
-- This script is database-name-free for multi-tenant deployments.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'communicationhub.diagnostics.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE name = 'communicationhub.diagnostics.view' AND guard_name = 'web'
);

-- Helpful tenant health checks. These do not modify data.
SELECT 'Communication Hub diagnostics permission' AS check_name, COUNT(*) AS found
FROM permissions
WHERE name = 'communicationhub.diagnostics.view' AND guard_name = 'web';

SELECT 'Required Communication Hub tables present' AS check_name, COUNT(*) AS found
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN (
    'communication_hub_messages',
    'communication_hub_delivery_events',
    'communication_hub_otps',
    'communication_hub_providers',
    'communication_hub_templates',
    'communication_hub_campaigns',
    'communication_hub_api_clients',
    'communication_hub_api_request_logs',
    'communication_hub_sms_wallets',
    'communication_hub_automation_events',
    'communication_hub_workflow_event_logs'
  );

SELECT COALESCE(channel, 'unknown') AS channel, COALESCE(status, 'unknown') AS status, COUNT(*) AS total
FROM communication_hub_messages
GROUP BY COALESCE(channel, 'unknown'), COALESCE(status, 'unknown')
ORDER BY channel, status;

SELECT COALESCE(status, 'unknown') AS otp_status, COUNT(*) AS total
FROM communication_hub_otps
GROUP BY COALESCE(status, 'unknown')
ORDER BY otp_status;
