-- Stock Transfer-New final create table guard SQL
-- Tenant database only.

CREATE TABLE IF NOT EXISTS stn_installation_checks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NULL,
    check_key VARCHAR(100) NOT NULL,
    check_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    check_note TEXT NULL,
    checked_by INT UNSIGNED NULL,
    checked_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY stn_installation_checks_unique (business_id, check_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stn_deployment_notes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NULL,
    note_type VARCHAR(60) NOT NULL DEFAULT 'deployment',
    note_title VARCHAR(191) NOT NULL,
    note_body TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX stn_deployment_notes_business_idx (business_id),
    INDEX stn_deployment_notes_type_idx (note_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
