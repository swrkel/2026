-- StockTransferNew STN_016 Tenant SQL - Tester Support / Cleanup Preview
-- Run on each tenant database where StockTransferNew is enabled.

CREATE TABLE IF NOT EXISTS stn_tester_case_statuses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    test_key VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    remarks TEXT NULL,
    tested_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY stn_tester_case_key_unique (test_key),
    INDEX stn_tester_case_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stn_test_cleanup_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cleanup_note TEXT NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stn_test_cleanup_created_by_idx (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stn_tester_issue_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    issue_title VARCHAR(191) NOT NULL,
    issue_status VARCHAR(50) NOT NULL DEFAULT 'open',
    issue_note TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    resolved_by BIGINT UNSIGNED NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX stn_tester_issue_status_idx (issue_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.tester', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stocktransfernew.tester');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.tester.cleanup', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stocktransfernew.tester.cleanup');
