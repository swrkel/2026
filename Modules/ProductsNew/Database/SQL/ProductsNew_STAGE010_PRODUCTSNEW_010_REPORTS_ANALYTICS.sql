-- ProductsNew_STAGE010_PRODUCTSNEW_010_REPORTS_ANALYTICS.sql
-- Global tenant SQL. Do not prefix database name.
CREATE TABLE IF NOT EXISTS products_new_report_presets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  report_key VARCHAR(120) NOT NULL,
  name VARCHAR(191) NOT NULL,
  filters LONGTEXT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY products_new_report_presets_business_report_idx (business_id, report_key),
  KEY products_new_report_presets_location_idx (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_report_snapshots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  report_key VARCHAR(120) NOT NULL,
  snapshot_date DATE NOT NULL,
  metric_key VARCHAR(120) NOT NULL,
  metric_value DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  extra_data LONGTEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY products_new_report_snapshots_business_report_idx (business_id, report_key, snapshot_date),
  KEY products_new_report_snapshots_metric_idx (metric_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.index', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.index' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.movement', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.movement' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.profitability', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.profitability' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.aging', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.aging' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.fast_slow_dead', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.fast_slow_dead' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.negative_overstock', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.negative_overstock' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.expiry', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.expiry' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.serial', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.serial' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.category_brand', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.category_brand' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.price_history', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.price_history' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.inventory_turnover', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.inventory_turnover' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.abc_xyz', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.abc_xyz' AND guard_name = 'web');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.reorder_recommendation', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.reorder_recommendation' AND guard_name = 'web');
