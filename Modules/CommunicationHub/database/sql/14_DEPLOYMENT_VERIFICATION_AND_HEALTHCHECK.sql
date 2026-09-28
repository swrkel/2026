-- Communication Hub Stage 013 - Deployment Verification & Health Check
-- Purpose: read-only checks to run inside each tenant database after uploading the module and running SQL 01-13.
-- This file does not change data.

SELECT 'communication_hub_tables' AS check_name, COUNT(*) AS table_count
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND (table_name LIKE 'communication\_hub\_%' OR table_name LIKE 'ch\_%');

SELECT 'core_tables_present' AS check_name, table_name
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN (
    'communication_hub_messages',
    'communication_hub_queue',
    'communication_hub_templates',
    'communication_hub_providers',
    'communication_hub_otp_requests',
    'communication_hub_whatsapp_profiles',
    'communication_hub_push_devices',
    'communication_hub_in_app_notifications',
    'communication_hub_live_chat_conversations',
    'communication_hub_automation_rules',
    'communication_hub_workflow_rules',
    'communication_hub_api_tokens',
    'communication_hub_audit_logs'
  )
ORDER BY table_name;

SELECT 'business_scope_columns' AS check_name, table_name, column_name
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name LIKE 'communication\_hub\_%'
  AND column_name IN ('business_id','location_id','business_location_id','created_by','tenant_id')
ORDER BY table_name, column_name;

SELECT 'missing_business_scope_warning' AS check_name, t.table_name
FROM information_schema.tables t
LEFT JOIN information_schema.columns c
  ON c.table_schema = t.table_schema
 AND c.table_name = t.table_name
 AND c.column_name = 'business_id'
WHERE t.table_schema = DATABASE()
  AND t.table_name LIKE 'communication\_hub\_%'
  AND c.column_name IS NULL
ORDER BY t.table_name;

SELECT 'communication_hub_indexes' AS check_name, table_name, index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS columns_in_index
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name LIKE 'communication\_hub\_%'
GROUP BY table_name, index_name
ORDER BY table_name, index_name;
