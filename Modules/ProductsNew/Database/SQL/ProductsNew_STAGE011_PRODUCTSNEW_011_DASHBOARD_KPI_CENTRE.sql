-- ProductsNew_STAGE011_PRODUCTSNEW_011_DASHBOARD_KPI_CENTRE.sql
-- Global tenant SQL. Run inside each tenant database. Do not prefix database name.
CREATE TABLE IF NOT EXISTS products_new_dashboard_snapshots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NULL,
  snapshot_date DATE NOT NULL,
  snapshot_payload LONGTEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY products_new_dashboard_snapshots_business_date_idx (business_id, snapshot_date),
  KEY products_new_dashboard_snapshots_location_idx (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products_new_kpi_preferences (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  visible_cards LONGTEXT NULL,
  settings LONGTEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY products_new_kpi_preferences_user_unique (business_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.kpi.index', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.kpi.index' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.kpi.snapshot', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.kpi.snapshot' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.dashboard.management', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.dashboard.management' AND guard_name = 'web');
