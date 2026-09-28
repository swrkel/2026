-- StockTransferNew STN_017 Go-Live Support SQL
-- Run in EACH TENANT database only.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.go_live', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.go_live');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.training', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.training');

CREATE TABLE IF NOT EXISTS stn_go_live_check_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    checked_by BIGINT UNSIGNED NULL,
    check_group VARCHAR(100) NOT NULL,
    check_key VARCHAR(150) NOT NULL,
    check_status VARCHAR(50) NOT NULL,
    check_message TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX stn_go_live_business_idx (business_id),
    INDEX stn_go_live_group_idx (check_group),
    INDEX stn_go_live_status_idx (check_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
