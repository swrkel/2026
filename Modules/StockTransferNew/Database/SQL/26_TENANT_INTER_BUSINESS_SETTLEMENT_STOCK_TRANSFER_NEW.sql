-- StockTransferNew STN_026 - Tenant SQL
-- Inter-business transfer settlement additions. Run in each tenant database after earlier STN SQL files.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.inter_business_settlement.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.inter_business_settlement.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.inter_business_settlement.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.inter_business_settlement.create');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.inter_business_settlement.approve', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.inter_business_settlement.approve');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.inter_business_settlement.export', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.inter_business_settlement.export');

CREATE TABLE IF NOT EXISTS stock_transfer_new_inter_business_settlements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  settlement_no VARCHAR(60) NOT NULL,
  from_business_id BIGINT UNSIGNED NOT NULL,
  to_business_id BIGINT UNSIGNED NOT NULL,
  settlement_date DATE NOT NULL,
  transfer_count INT UNSIGNED NOT NULL DEFAULT 0,
  transfer_value DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  variance_value DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  status VARCHAR(30) NOT NULL DEFAULT 'draft',
  remarks TEXT NULL,
  approved_by BIGINT UNSIGNED NULL,
  approved_at TIMESTAMP NULL DEFAULT NULL,
  cancelled_by BIGINT UNSIGNED NULL,
  cancelled_at TIMESTAMP NULL DEFAULT NULL,
  cancel_reason TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY stn_ibs_no_unique (settlement_no),
  KEY stn_ibs_business_pair_idx (from_business_id, to_business_id),
  KEY stn_ibs_date_status_idx (settlement_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_transfer_new_inter_business_settlement_lines (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  settlement_id BIGINT UNSIGNED NOT NULL,
  transfer_id BIGINT UNSIGNED NOT NULL,
  transfer_no VARCHAR(80) NULL,
  transfer_value DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  variance_value DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY stn_ibs_line_transfer_unique (transfer_id),
  KEY stn_ibs_line_settlement_idx (settlement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_transfer_new_inter_business_settlement_audits (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  settlement_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(40) NOT NULL,
  remarks TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY stn_ibs_audit_settlement_idx (settlement_id),
  KEY stn_ibs_audit_action_idx (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE stock_transfer_new_transfers
  ADD COLUMN IF NOT EXISTS from_business_id BIGINT UNSIGNED NULL AFTER business_id,
  ADD COLUMN IF NOT EXISTS to_business_id BIGINT UNSIGNED NULL AFTER from_business_id;
