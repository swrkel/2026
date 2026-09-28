-- AutoService Stage 040 - Final Enterprise Audit and Production Hardening
-- Run this SQL on each tenant database that uses the Auto Service module.
-- This stage is additive and rollback-safe. It adds audit metadata and sign-off support only.

CREATE TABLE IF NOT EXISTS auto_service_production_audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    audit_area VARCHAR(100) NOT NULL,
    audit_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    severity VARCHAR(50) NOT NULL DEFAULT 'info',
    reference_key VARCHAR(191) NULL,
    message TEXT NULL,
    checked_by BIGINT UNSIGNED NULL,
    checked_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_as_prod_audit_business (business_id),
    INDEX idx_as_prod_audit_location (location_id),
    INDEX idx_as_prod_audit_area (audit_area),
    INDEX idx_as_prod_audit_status (audit_status),
    INDEX idx_as_prod_audit_checked (checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_deployment_signoffs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    stage_code VARCHAR(50) NOT NULL DEFAULT 'STAGE040',
    checklist_key VARCHAR(191) NOT NULL,
    checklist_label VARCHAR(255) NOT NULL,
    signoff_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    signed_by BIGINT UNSIGNED NULL,
    signed_at TIMESTAMP NULL DEFAULT NULL,
    note TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_as_signoff_business (business_id),
    INDEX idx_as_signoff_location (location_id),
    INDEX idx_as_signoff_stage (stage_code),
    INDEX idx_as_signoff_status (signoff_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
DROP PROCEDURE IF EXISTS autoservice_stage040_add_index$$
CREATE PROCEDURE autoservice_stage040_add_index(IN p_table VARCHAR(191), IN p_index VARCHAR(191), IN p_sql TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index) THEN
        SET @ddl = p_sql;
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL autoservice_stage040_add_index('auto_service_jobs', 'idx_as_jobs_business_status_due', 'CREATE INDEX idx_as_jobs_business_status_due ON auto_service_jobs (business_id, status, expected_delivery_date)');
CALL autoservice_stage040_add_index('auto_service_invoices', 'idx_as_invoices_business_status_date', 'CREATE INDEX idx_as_invoices_business_status_date ON auto_service_invoices (business_id, status, invoice_date)');
CALL autoservice_stage040_add_index('auto_service_part_movements', 'idx_as_parts_business_job_date', 'CREATE INDEX idx_as_parts_business_job_date ON auto_service_part_movements (business_id, job_id, movement_date)');
CALL autoservice_stage040_add_index('auto_service_timeline', 'idx_as_timeline_business_ref_date', 'CREATE INDEX idx_as_timeline_business_ref_date ON auto_service_timeline (business_id, reference_type, reference_id, created_at)');

DROP PROCEDURE IF EXISTS autoservice_stage040_add_index;

INSERT INTO auto_service_deployment_signoffs (stage_code, checklist_key, checklist_label, signoff_status, created_at, updated_at)
SELECT 'STAGE040', x.checklist_key, x.checklist_label, 'pending', NOW(), NOW()
FROM (
    SELECT 'module_visible' checklist_key, 'Auto Service menu/sidebar visible for permitted users' checklist_label UNION ALL
    SELECT 'tenant_tables_ok', 'All Auto Service tenant tables are available' UNION ALL
    SELECT 'business_scope_ok', 'Pages and reports filter by selected business/location' UNION ALL
    SELECT 'workflow_ok', 'Appointment to delivery workflow tested successfully' UNION ALL
    SELECT 'customer_portal_ok', 'Customer portal status, bill and history tested successfully' UNION ALL
    SELECT 'reports_exports_ok', 'Reports and CSV exports tested successfully'
) x
WHERE NOT EXISTS (
    SELECT 1 FROM auto_service_deployment_signoffs s
    WHERE s.stage_code = 'STAGE040' AND s.checklist_key = x.checklist_key
);

-- Permission keys for permission seeders/importers. If your permission table is named differently,
-- add these permission keys through Super Admin permission management:
-- autoservice.production_audit.view
-- autoservice.production_audit.manage
-- autoservice.deployment_signoff.view
-- autoservice.deployment_signoff.manage
