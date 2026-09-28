-- STN_009 Tenant SQL: Advanced Reporting Permissions / Menu Seeds
-- Safe to run multiple times when your permissions table has unique name/guard constraints.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.advanced_reports', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stocktransfernew.advanced_reports' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.export_reports', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stocktransfernew.export_reports' AND guard_name = 'web');

-- Optional menu key for sidebar builders that read module feature flags.
CREATE TABLE IF NOT EXISTS stnew_report_preferences (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    report_key VARCHAR(80) NOT NULL,
    columns_json JSON NULL,
    filters_json JSON NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY stnew_report_preferences_unique (business_id, user_id, report_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
