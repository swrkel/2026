-- AutoService Stage 041: Stabilization Centre and Server Issue Capture
-- Execute in each tenant database. No database name is hard-coded.

CREATE TABLE IF NOT EXISTS auto_service_server_test_issues (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    reported_by BIGINT UNSIGNED NULL,
    page_url VARCHAR(500) NULL,
    issue_title VARCHAR(255) NOT NULL,
    issue_description TEXT NULL,
    severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    status ENUM('open','new','in_progress','fixed','closed','rejected') NOT NULL DEFAULT 'open',
    screenshot_reference VARCHAR(500) NULL,
    log_reference VARCHAR(500) NULL,
    developer_note TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_autoservice_server_test_issues_business (business_id),
    INDEX idx_autoservice_server_test_issues_location (location_id),
    INDEX idx_autoservice_server_test_issues_status (status),
    INDEX idx_autoservice_server_test_issues_severity (severity),
    INDEX idx_autoservice_server_test_issues_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional permission registration. These INSERT statements are written defensively for tenant DBs that have a permissions table.
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.stabilization.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.stabilization.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.stabilization.manage', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.stabilization.manage');
