-- Communication Hub Stage 019
-- Operations Support and Final Sign-Off
-- Run this in EACH tenant database only. Do not prefix database names.

CREATE TABLE IF NOT EXISTS communication_deployment_signoffs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    stage_code VARCHAR(50) NOT NULL DEFAULT 'STAGE_019',
    checklist_key VARCHAR(100) NOT NULL,
    checklist_label VARCHAR(255) NOT NULL,
    status ENUM('pending','passed','failed','not_applicable') NOT NULL DEFAULT 'pending',
    remarks TEXT NULL,
    checked_by BIGINT UNSIGNED NULL,
    checked_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ch_signoff_business (business_id, business_location_id),
    INDEX idx_ch_signoff_stage (stage_code, checklist_key),
    INDEX idx_ch_signoff_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS communication_rollout_notes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    note_type ENUM('deployment','testing','issue','resolution','handover') NOT NULL DEFAULT 'deployment',
    title VARCHAR(255) NOT NULL,
    note LONGTEXT NULL,
    severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'low',
    status ENUM('open','in_progress','closed') NOT NULL DEFAULT 'open',
    created_by BIGINT UNSIGNED NULL,
    closed_by BIGINT UNSIGNED NULL,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ch_rollout_business (business_id),
    INDEX idx_ch_rollout_status (status, severity),
    INDEX idx_ch_rollout_type (note_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('communicationhub.operations.view', 'web', NOW(), NOW()),
('communicationhub.operations.signoff', 'web', NOW(), NOW()),
('communicationhub.operations.notes', 'web', NOW(), NOW());

INSERT IGNORE INTO communication_deployment_signoffs (stage_code, checklist_key, checklist_label, status, created_at, updated_at) VALUES
('STAGE_019', 'menu_visible', 'Communication Hub menu is visible for permitted users', 'pending', NOW(), NOW()),
('STAGE_019', 'tenant_tables_ok', 'All Communication Hub tenant tables exist', 'pending', NOW(), NOW()),
('STAGE_019', 'business_scope_ok', 'Business-wise data isolation is verified', 'pending', NOW(), NOW()),
('STAGE_019', 'provider_test_ok', 'At least one provider test is completed', 'pending', NOW(), NOW()),
('STAGE_019', 'queue_test_ok', 'Pending/failed queue processing is verified', 'pending', NOW(), NOW()),
('STAGE_019', 'reports_ok', 'Delivery reports and audit reports are verified', 'pending', NOW(), NOW()),
('STAGE_019', 'automation_safe', 'Automation/workflow rules are tested before enabling', 'pending', NOW(), NOW()),
('STAGE_019', 'final_backup_done', 'Final tenant database backup completed after deployment', 'pending', NOW(), NOW());
