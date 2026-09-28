-- Auto Service Stage 043 - Enterprise Integration
-- Run this in every tenant database that uses Auto Service.

CREATE TABLE IF NOT EXISTS auto_service_integration_bridge_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    bridge_type VARCHAR(80) NOT NULL DEFAULT 'system_bridge',
    source_module VARCHAR(80) NOT NULL DEFAULT 'AutoService',
    target_module VARCHAR(80) NOT NULL,
    reference_type VARCHAR(80) NULL,
    reference_id BIGINT UNSIGNED NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'pending',
    message TEXT NULL,
    payload LONGTEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_as_bridge_business_location (business_id, location_id),
    INDEX idx_as_bridge_target_status (target_module, status),
    INDEX idx_as_bridge_reference (reference_type, reference_id),
    INDEX idx_as_bridge_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_external_posting_map (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    source_type VARCHAR(80) NOT NULL,
    source_id BIGINT UNSIGNED NOT NULL,
    target_module VARCHAR(80) NOT NULL,
    target_type VARCHAR(80) NULL,
    target_id BIGINT UNSIGNED NULL,
    posting_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    posted_at TIMESTAMP NULL DEFAULT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_as_external_posting (source_type, source_id, target_module, target_type),
    INDEX idx_as_external_posting_scope (business_id, location_id),
    INDEX idx_as_external_posting_status (posting_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.enterprise_integration.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.enterprise_integration.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.enterprise_integration.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.enterprise_integration.manage');
