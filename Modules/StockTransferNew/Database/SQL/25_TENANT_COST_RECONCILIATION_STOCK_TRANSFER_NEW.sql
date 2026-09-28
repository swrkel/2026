-- StockTransferNew STN_025 - Tenant SQL
-- Cost reconciliation / financial control additions only. Run in each tenant database after earlier STN SQL files.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.cost_reconciliation.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.cost_reconciliation.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.cost_reconciliation.export', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.cost_reconciliation.export');

CREATE TABLE IF NOT EXISTS stock_transfer_new_cost_reconciliation_notes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  transfer_id BIGINT UNSIGNED NOT NULL,
  note TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY stn_crn_business_transfer_idx (business_id, transfer_id),
  KEY stn_crn_created_by_idx (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE stock_transfer_new_transfer_lines
  ADD COLUMN IF NOT EXISTS unit_cost DECIMAL(22,4) NULL AFTER unit_price,
  ADD COLUMN IF NOT EXISTS received_quantity DECIMAL(22,4) NULL AFTER quantity;
