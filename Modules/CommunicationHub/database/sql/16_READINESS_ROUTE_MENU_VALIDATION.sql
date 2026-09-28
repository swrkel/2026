-- Communication Hub Stage 015 - Readiness / Route / Menu Validation
-- Run this in every tenant database where Communication Hub is enabled.
-- This script is intentionally database-name-free for multi-tenant deployments.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'communicationhub.readiness.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE name = 'communicationhub.readiness.view' AND guard_name = 'web'
);

-- Optional permission group/menu marker for systems that keep module page keys in module_permissions.
-- Safe no-op when the table does not exist should be handled manually by skipping this section if your tenant does not use module_permissions.
-- INSERT INTO module_permissions (module, permission, created_at, updated_at)
-- SELECT 'CommunicationHub', 'communicationhub.readiness.view', NOW(), NOW()
-- WHERE NOT EXISTS (SELECT 1 FROM module_permissions WHERE module = 'CommunicationHub' AND permission = 'communicationhub.readiness.view');

-- Health checks to run after upload:
SELECT 'communicationhub.readiness.view permission' AS check_name,
       COUNT(*) AS found
FROM permissions
WHERE name = 'communicationhub.readiness.view';

SELECT 'communication_hub_messages table' AS check_name,
       COUNT(*) AS found
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name = 'communication_hub_messages';
