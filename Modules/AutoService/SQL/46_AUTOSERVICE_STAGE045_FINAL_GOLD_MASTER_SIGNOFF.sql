-- AutoService Stage 045 - Final Gold Master Signoff
-- Run on tenant databases using AutoService.

CREATE TABLE IF NOT EXISTS autoservice_final_signoff_checks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT NULL,
    location_id INT NULL,
    check_key VARCHAR(120) NOT NULL,
    check_title VARCHAR(255) NOT NULL,
    check_status ENUM('pending','passed','failed','not_applicable') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    checked_by INT NULL,
    checked_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_autoservice_final_signoff_business (business_id),
    KEY idx_autoservice_final_signoff_location (location_id),
    KEY idx_autoservice_final_signoff_key (check_key),
    KEY idx_autoservice_final_signoff_status (check_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO autoservice_final_signoff_checks (check_key, check_title, check_status, created_at, updated_at)
SELECT 'module_visible', 'AutoService module visible in sidebar/menu', 'pending', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM autoservice_final_signoff_checks WHERE check_key = 'module_visible');

INSERT INTO autoservice_final_signoff_checks (check_key, check_title, check_status, created_at, updated_at)
SELECT 'permissions_verified', 'AutoService permissions verified by user role', 'pending', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM autoservice_final_signoff_checks WHERE check_key = 'permissions_verified');

INSERT INTO autoservice_final_signoff_checks (check_key, check_title, check_status, created_at, updated_at)
SELECT 'tenant_scope_verified', 'Tenant/business/location data scope verified', 'pending', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM autoservice_final_signoff_checks WHERE check_key = 'tenant_scope_verified');

INSERT INTO autoservice_final_signoff_checks (check_key, check_title, check_status, created_at, updated_at)
SELECT 'workflow_verified', 'Estimate, job, parts, labour, QC, billing, delivery workflow verified', 'pending', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM autoservice_final_signoff_checks WHERE check_key = 'workflow_verified');

INSERT INTO autoservice_final_signoff_checks (check_key, check_title, check_status, created_at, updated_at)
SELECT 'customer_portal_verified', 'Customer portal, bill, history, parts/accessories filters verified', 'pending', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM autoservice_final_signoff_checks WHERE check_key = 'customer_portal_verified');

INSERT INTO autoservice_final_signoff_checks (check_key, check_title, check_status, created_at, updated_at)
SELECT 'reports_verified', 'Reports, exports, dashboards and KPI pages verified', 'pending', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM autoservice_final_signoff_checks WHERE check_key = 'reports_verified');
