/*
PRODUCTSNEW_012 - Production Hardening & Final Standalone Audit
Run this in each tenant database where Products New is enabled.
No database name is hardcoded.
*/

-- Permissions for final audit and integration bridge pages
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.production_audit.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.production_audit.view' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.integration_bridge.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.integration_bridge.view' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.integration_bridge.api', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.integration_bridge.api' AND guard_name = 'web');

-- Optional menu/page registry entries. Safe when the ERP has module/page registries.
CREATE TABLE IF NOT EXISTS products_new_production_audit_results (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    audit_key VARCHAR(120) NOT NULL,
    audit_status VARCHAR(50) NOT NULL DEFAULT 'ready',
    audit_payload LONGTEXT NULL,
    checked_by BIGINT UNSIGNED NULL,
    checked_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY products_new_prod_audit_business_idx (business_id),
    KEY products_new_prod_audit_location_idx (business_location_id),
    KEY products_new_prod_audit_key_idx (audit_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
