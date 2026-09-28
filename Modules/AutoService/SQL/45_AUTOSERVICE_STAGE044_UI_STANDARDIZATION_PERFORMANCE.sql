/*
Auto Service Stage 044 - UI Standardization & Performance Hardening
Run on every tenant database after Stage 043.
All statements are written to be safe for repeat execution where MySQL supports IF NOT EXISTS.
*/

CREATE TABLE IF NOT EXISTS auto_service_ui_audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NULL,
    location_id INT UNSIGNED NULL,
    audit_area VARCHAR(100) NOT NULL,
    audit_status VARCHAR(50) NOT NULL DEFAULT 'checked',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX idx_as_ui_audit_business_location (business_id, location_id),
    INDEX idx_as_ui_audit_area_status (audit_area, audit_status),
    INDEX idx_as_ui_audit_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_page_performance_checks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NULL,
    location_id INT UNSIGNED NULL,
    page_key VARCHAR(150) NOT NULL,
    route_name VARCHAR(150) NULL,
    expected_permission VARCHAR(150) NULL,
    expected_table VARCHAR(150) NULL,
    check_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_as_page_perf_page_route (page_key, route_name),
    INDEX idx_as_page_perf_business_location (business_id, location_id),
    INDEX idx_as_page_perf_status (check_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO auto_service_page_performance_checks
(page_key, route_name, expected_permission, expected_table, check_status, remarks, created_at, updated_at)
VALUES
('ui_standardization', 'autoservice.ui_standardization.index', 'autoservice.ui_standardization.view', 'auto_service_ui_audit_logs', 'ready', 'Stage 044 UI and performance audit page.', NOW(), NOW()),
('dashboard', 'autoservice.dashboard', 'autoservice.dashboard.view', 'auto_service_jobs', 'ready', 'Main Auto Service dashboard route validation.', NOW(), NOW()),
('command_centre', 'autoservice.command_centre.index', 'autoservice.command_centre.view', 'auto_service_jobs', 'ready', 'Workshop command centre route validation.', NOW(), NOW()),
('customer_portal', 'autoservice.customer_portal.lookup', 'autoservice.customer_portal.view', 'auto_service_jobs', 'ready', 'Customer portal route validation.', NOW(), NOW()),
('advanced_vehicle_history', 'autoservice.advanced_vehicle_history.index', 'autoservice.advanced_vehicle_history.view', 'auto_service_job_parts', 'ready', 'Advanced vehicle history route validation.', NOW(), NOW()),
('enterprise_integration', 'autoservice.enterprise_integration.index', 'autoservice.enterprise_integration.view', 'auto_service_integration_bridge_logs', 'ready', 'ERP integration route validation.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
expected_permission = VALUES(expected_permission),
expected_table = VALUES(expected_table),
check_status = VALUES(check_status),
remarks = VALUES(remarks),
updated_at = NOW();

/* Permission inserts guarded for tenants where a standard permissions table exists. */
DROP PROCEDURE IF EXISTS autoservice_stage044_add_permission;
DELIMITER $$
CREATE PROCEDURE autoservice_stage044_add_permission(IN p_name VARCHAR(150))
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions') THEN
        SET @sql = CONCAT("INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT '", p_name, "', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = '", p_name, "')");
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;
CALL autoservice_stage044_add_permission('autoservice.ui_standardization.view');
CALL autoservice_stage044_add_permission('autoservice.ui_standardization.manage');
DROP PROCEDURE IF EXISTS autoservice_stage044_add_permission;

/* Recommended performance indexes, guarded to avoid duplicate-index failures. */
DROP PROCEDURE IF EXISTS autoservice_stage044_add_index;
DELIMITER $$
CREATE PROCEDURE autoservice_stage044_add_index(IN p_table VARCHAR(150), IN p_index VARCHAR(150), IN p_columns TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index) THEN
        SET @sql = CONCAT('ALTER TABLE ', p_table, ' ADD INDEX ', p_index, ' (', p_columns, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;
CALL autoservice_stage044_add_index('auto_service_jobs', 'idx_as_jobs_business_location_status', 'business_id, location_id, status');
CALL autoservice_stage044_add_index('auto_service_job_parts', 'idx_as_parts_business_location_job_product', 'business_id, location_id, job_id, product_id');
CALL autoservice_stage044_add_index('auto_service_invoices', 'idx_as_invoices_business_location_status', 'business_id, location_id, status');
CALL autoservice_stage044_add_index('auto_service_appointments', 'idx_as_appt_business_location_date_status', 'business_id, location_id, appointment_date, status');
DROP PROCEDURE IF EXISTS autoservice_stage044_add_index;

INSERT INTO auto_service_ui_audit_logs
(business_id, location_id, audit_area, audit_status, notes, created_at, updated_at)
VALUES
(NULL, NULL, 'stage_044_sql', 'installed', 'Stage 044 UI standardization and performance SQL installed.', NOW(), NOW());
