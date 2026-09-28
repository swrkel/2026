/*
Communication Hub Stage 014/015 - Post handover stabilization
Run this SQL in EVERY tenant database after Stage 013.
It only inserts missing permissions and creates optional deployment health-check views/tables.
It is safe to re-run.
*/

CREATE TABLE IF NOT EXISTS communication_hub_deployment_checks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NULL,
    location_id INT UNSIGNED NULL,
    check_code VARCHAR(100) NOT NULL,
    check_title VARCHAR(191) NOT NULL,
    check_status ENUM('pending','pass','fail','warning') NOT NULL DEFAULT 'pending',
    check_message TEXT NULL,
    checked_by BIGINT UNSIGNED NULL,
    checked_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ch_deployment_checks_business_idx (business_id),
    KEY ch_deployment_checks_location_idx (location_id),
    KEY ch_deployment_checks_code_idx (check_code),
    KEY ch_deployment_checks_status_idx (check_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS communication_hub_menu_health_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NULL,
    route_name VARCHAR(191) NOT NULL,
    menu_title VARCHAR(191) NULL,
    permission_name VARCHAR(191) NULL,
    status ENUM('available','missing_route','missing_permission','hidden','error') NOT NULL DEFAULT 'available',
    message TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ch_menu_health_business_idx (business_id),
    KEY ch_menu_health_route_idx (route_name),
    KEY ch_menu_health_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional permission seed. Supports common ERP permission table shapes.
SET @permission_table_exists := (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'permissions'
);

-- If your permissions table uses the standard Spatie columns (name, guard_name), run this block.
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT p.name, 'web', NOW(), NOW()
FROM (
    SELECT 'communicationhub.commercial.view' AS name UNION ALL
    SELECT 'communicationhub.commercial.sms.view' UNION ALL
    SELECT 'communicationhub.commercial.sms.send' UNION ALL
    SELECT 'communicationhub.commercial.sms.bulk' UNION ALL
    SELECT 'communicationhub.commercial.sms.schedule' UNION ALL
    SELECT 'communicationhub.commercial.sms.packages' UNION ALL
    SELECT 'communicationhub.commercial.wallets.view' UNION ALL
    SELECT 'communicationhub.commercial.wallets.refill' UNION ALL
    SELECT 'communicationhub.commercial.reports.view' UNION ALL
    SELECT 'communicationhub.commercial.api.view' UNION ALL
    SELECT 'communicationhub.commercial.api.manage' UNION ALL
    SELECT 'communicationhub.email.view' UNION ALL
    SELECT 'communicationhub.email.send' UNION ALL
    SELECT 'communicationhub.email.bulk' UNION ALL
    SELECT 'communicationhub.email.queue' UNION ALL
    SELECT 'communicationhub.whatsapp.view' UNION ALL
    SELECT 'communicationhub.whatsapp.send' UNION ALL
    SELECT 'communicationhub.whatsapp.bulk' UNION ALL
    SELECT 'communicationhub.whatsapp.schedule' UNION ALL
    SELECT 'communicationhub.whatsapp.templates' UNION ALL
    SELECT 'communicationhub.whatsapp.profiles' UNION ALL
    SELECT 'communicationhub.push.view' UNION ALL
    SELECT 'communicationhub.push.send' UNION ALL
    SELECT 'communicationhub.push.bulk' UNION ALL
    SELECT 'communicationhub.push.schedule' UNION ALL
    SELECT 'communicationhub.push.devices' UNION ALL
    SELECT 'communicationhub.push.templates' UNION ALL
    SELECT 'communicationhub.inapp.view' UNION ALL
    SELECT 'communicationhub.inapp.send' UNION ALL
    SELECT 'communicationhub.inapp.inbox' UNION ALL
    SELECT 'communicationhub.inapp.templates' UNION ALL
    SELECT 'communicationhub.chat.view' UNION ALL
    SELECT 'communicationhub.chat.reply' UNION ALL
    SELECT 'communicationhub.chat.close' UNION ALL
    SELECT 'communicationhub.internal_messages.view' UNION ALL
    SELECT 'communicationhub.internal_messages.send' UNION ALL
    SELECT 'communicationhub.automation.view' UNION ALL
    SELECT 'communicationhub.automation.manage' UNION ALL
    SELECT 'communicationhub.automation.execute' UNION ALL
    SELECT 'communicationhub.workflow.view' UNION ALL
    SELECT 'communicationhub.workflow.manage' UNION ALL
    SELECT 'communicationhub.workflow.execute' UNION ALL
    SELECT 'communicationhub.analytics.view' UNION ALL
    SELECT 'communicationhub.api_gateway.view' UNION ALL
    SELECT 'communicationhub.api_gateway.manage' UNION ALL
    SELECT 'communicationhub.audit_centre.view'
) p
WHERE @permission_table_exists = 1
  AND EXISTS (
      SELECT 1 FROM information_schema.columns
      WHERE table_schema = DATABASE() AND table_name = 'permissions' AND column_name = 'name'
  )
  AND NOT EXISTS (SELECT 1 FROM permissions x WHERE x.name = p.name);

INSERT INTO communication_hub_deployment_checks (check_code, check_title, check_status, check_message, created_at, updated_at)
SELECT 'stage_014_sql_loaded', 'Communication Hub Stage 014 SQL loaded', 'pass', 'Post handover menu, permission and health-check SQL has been executed for this tenant database.', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM communication_hub_deployment_checks WHERE check_code = 'stage_014_sql_loaded'
);
