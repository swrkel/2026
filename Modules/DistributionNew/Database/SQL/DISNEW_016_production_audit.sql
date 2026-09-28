CREATE TABLE IF NOT EXISTS disnew_audit_checks (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 business_id INT UNSIGNED NULL,
 check_key VARCHAR(191) NOT NULL,
 check_group VARCHAR(100) NOT NULL,
 expected_value TEXT NULL,
 is_required TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_audit_checks_business_idx (business_id),
 INDEX disnew_audit_checks_group_idx (check_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_audit_results (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 business_id INT UNSIGNED NULL,
 user_id INT UNSIGNED NULL,
 check_type VARCHAR(100) NOT NULL,
 status VARCHAR(50) NOT NULL DEFAULT 'pending',
 message TEXT NULL,
 payload JSON NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 INDEX disnew_audit_results_business_idx (business_id),
 INDEX disnew_audit_results_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO disnew_audit_checks (business_id, check_key, check_group, expected_value, is_required, created_at, updated_at) VALUES
(NULL,'disnew_module_visible','menu','Distribution New sidebar visible',1,NOW(),NOW()),
(NULL,'disnew_permissions_seeded','permission','Core Distribution New permissions seeded',1,NOW(),NOW()),
(NULL,'disnew_routes_loaded','route','Distribution New routes return non-404',1,NOW(),NOW()),
(NULL,'disnew_tables_exist','sql','All required disnew_ tables exist',1,NOW(),NOW()),
(NULL,'disnew_pos_ui','ui','POS dashboard design standard applied',1,NOW(),NOW());

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('disnew.audit.view','web',NOW(),NOW()),
('disnew.audit.run','web',NOW(),NOW());
