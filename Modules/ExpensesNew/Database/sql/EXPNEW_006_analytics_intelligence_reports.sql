-- EXPNEW_006 Executive Analytics and Expense Intelligence SQL
-- Tenant database only. All tables use expnew_ prefix.

CREATE TABLE IF NOT EXISTS expnew_expense_kpi_snapshots (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 location_id BIGINT UNSIGNED NULL,
 snapshot_date DATE NOT NULL,
 kpi_code VARCHAR(100) NOT NULL,
 kpi_value DECIMAL(22,4) NOT NULL DEFAULT 0,
 meta JSON NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX expnew_kpi_biz_date (business_id, snapshot_date),
 UNIQUE KEY expnew_kpi_unique (business_id, location_id, snapshot_date, kpi_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_expense_analytics_snapshots (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 location_id BIGINT UNSIGNED NULL,
 dimension_type VARCHAR(80) NOT NULL,
 dimension_id BIGINT UNSIGNED NULL,
 period_type VARCHAR(30) NOT NULL DEFAULT 'monthly',
 period_start DATE NOT NULL,
 period_end DATE NOT NULL,
 total_expenses DECIMAL(22,4) NOT NULL DEFAULT 0,
 total_paid DECIMAL(22,4) NOT NULL DEFAULT 0,
 total_pending DECIMAL(22,4) NOT NULL DEFAULT 0,
 record_count INT NOT NULL DEFAULT 0,
 meta JSON NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX expnew_analytics_period (business_id, dimension_type, period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_expense_intelligence_alerts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 location_id BIGINT UNSIGNED NULL,
 expense_id BIGINT UNSIGNED NULL,
 alert_type VARCHAR(80) NOT NULL,
 severity VARCHAR(30) NOT NULL DEFAULT 'info',
 title VARCHAR(191) NOT NULL,
 message TEXT NULL,
 score DECIMAL(10,4) NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'open',
 detected_at DATETIME NULL,
 resolved_at DATETIME NULL,
 resolved_by BIGINT UNSIGNED NULL,
 meta JSON NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX expnew_alert_status (business_id, alert_type, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_expense_report_definitions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 report_code VARCHAR(100) NOT NULL UNIQUE,
 report_group VARCHAR(80) NOT NULL,
 report_title VARCHAR(191) NOT NULL,
 permission_name VARCHAR(191) NULL,
 columns_json JSON NULL,
 filters_json JSON NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_expense_report_runs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 report_code VARCHAR(100) NOT NULL,
 filters_json JSON NULL,
 export_type VARCHAR(30) NULL,
 run_by BIGINT UNSIGNED NULL,
 started_at DATETIME NULL,
 completed_at DATETIME NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'pending',
 file_path VARCHAR(500) NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX expnew_report_runs_biz (business_id, report_code, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_expense_dashboard_widgets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 widget_code VARCHAR(100) NOT NULL,
 widget_title VARCHAR(191) NOT NULL,
 widget_type VARCHAR(50) NOT NULL DEFAULT 'card',
 sort_order INT NOT NULL DEFAULT 0,
 settings_json JSON NULL,
 is_visible TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX expnew_widgets_user (business_id, user_id, is_visible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_expense_chart_definitions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 chart_code VARCHAR(100) NOT NULL UNIQUE,
 chart_title VARCHAR(191) NOT NULL,
 chart_type VARCHAR(50) NOT NULL,
 data_source VARCHAR(191) NULL,
 config_json JSON NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_expense_drilldown_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 source_type VARCHAR(80) NOT NULL,
 source_code VARCHAR(100) NULL,
 filters_json JSON NULL,
 created_at TIMESTAMP NULL,
 INDEX expnew_drill_logs_user (business_id, user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO expnew_expense_report_definitions (report_code, report_group, report_title, permission_name, is_active, created_at, updated_at)
SELECT 'expense_register','operational','Expense Register','expenses_new.reports.view',1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_expense_report_definitions WHERE report_code='expense_register');
INSERT INTO expnew_expense_report_definitions (report_code, report_group, report_title, permission_name, is_active, created_at, updated_at)
SELECT 'budget_vs_actual','management','Budget vs Actual','expenses_new.reports.view',1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_expense_report_definitions WHERE report_code='budget_vs_actual');
INSERT INTO expnew_expense_report_definitions (report_code, report_group, report_title, permission_name, is_active, created_at, updated_at)
SELECT 'executive_kpi','executive','Executive KPI Dashboard','expenses_new.executive_dashboard.view',1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_expense_report_definitions WHERE report_code='executive_kpi');
