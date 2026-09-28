-- EXPNEW_011 Tenant Create Tables
CREATE TABLE IF NOT EXISTS expnew_report_definitions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 code VARCHAR(191) NOT NULL,
 title VARCHAR(191) NOT NULL,
 category VARCHAR(100) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 UNIQUE KEY expnew_report_definitions_code_unique (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_report_runs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 report_code VARCHAR(191) NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 filters JSON NULL,
 status VARCHAR(50) NOT NULL DEFAULT 'completed',
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 INDEX expnew_report_runs_business_report_idx (business_id, report_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_dashboard_widgets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 widget_code VARCHAR(191) NOT NULL,
 title VARCHAR(191) NOT NULL,
 settings JSON NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 INDEX expnew_dashboard_widgets_business_idx (business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
