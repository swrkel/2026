-- PRODUCTSNEW_013 - Migration readiness, legacy comparison, and testing helpers
-- Global tenant-safe SQL. Do not add database names before tables.

CREATE TABLE IF NOT EXISTS products_new_migration_checks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NOT NULL,
    check_key VARCHAR(120) NOT NULL,
    check_name VARCHAR(191) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'review',
    note TEXT NULL,
    checked_by INT UNSIGNED NULL,
    checked_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY products_new_migration_checks_business_status_idx (business_id, status),
    UNIQUE KEY products_new_migration_checks_business_key_unique (business_id, check_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_testing_results (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NOT NULL,
    test_key VARCHAR(120) NOT NULL,
    test_name VARCHAR(191) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    remarks TEXT NULL,
    tested_by INT UNSIGNED NULL,
    tested_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY products_new_testing_results_business_status_idx (business_id, status),
    UNIQUE KEY products_new_testing_results_business_key_unique (business_id, test_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('products_new.migration_readiness.view', 'web', NOW(), NOW()),
('products_new.legacy_comparison.view', 'web', NOW(), NOW()),
('products_new.testing_checklist.view', 'web', NOW(), NOW());
