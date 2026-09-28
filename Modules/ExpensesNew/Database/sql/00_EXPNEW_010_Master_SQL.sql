CREATE TABLE IF NOT EXISTS `expnew_budgets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` VARCHAR(191) NULL,
  `location_id` VARCHAR(191) NULL,
  `budget_type` VARCHAR(191) NULL,
  `budget_name` VARCHAR(191) NULL,
  `period_start` VARCHAR(191) NULL,
  `period_end` VARCHAR(191) NULL,
  `original_amount` VARCHAR(191) NULL,
  `revised_amount` VARCHAR(191) NULL,
  `approved_amount` VARCHAR(191) NULL,
  `actual_amount` VARCHAR(191) NULL,
  `committed_amount` VARCHAR(191) NULL,
  `reserved_amount` VARCHAR(191) NULL,
  `remaining_amount` VARCHAR(191) NULL,
  `forecast_amount` VARCHAR(191) NULL,
  `status` VARCHAR(191) NULL,
  `created_by` VARCHAR(191) NULL,
  `updated_by` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_budget_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id` VARCHAR(191) NULL,
  `department_id` VARCHAR(191) NULL,
  `cost_center_id` VARCHAR(191) NULL,
  `project_id` VARCHAR(191) NULL,
  `category_id` VARCHAR(191) NULL,
  `line_name` VARCHAR(191) NULL,
  `original_amount` VARCHAR(191) NULL,
  `revised_amount` VARCHAR(191) NULL,
  `actual_amount` VARCHAR(191) NULL,
  `variance_amount` VARCHAR(191) NULL,
  `utilization_percent` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_budget_revisions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id` VARCHAR(191) NULL,
  `revision_no` VARCHAR(191) NULL,
  `old_amount` VARCHAR(191) NULL,
  `new_amount` VARCHAR(191) NULL,
  `reason` VARCHAR(191) NULL,
  `status` VARCHAR(191) NULL,
  `requested_by` VARCHAR(191) NULL,
  `approved_by` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_budget_approvals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id` VARCHAR(191) NULL,
  `approval_level` VARCHAR(191) NULL,
  `approver_id` VARCHAR(191) NULL,
  `status` VARCHAR(191) NULL,
  `comments` VARCHAR(191) NULL,
  `approved_at` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_budget_forecasts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id` VARCHAR(191) NULL,
  `forecast_month` VARCHAR(191) NULL,
  `forecast_amount` VARCHAR(191) NULL,
  `basis` VARCHAR(191) NULL,
  `confidence_score` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_budget_variances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id` VARCHAR(191) NULL,
  `budget_line_id` VARCHAR(191) NULL,
  `variance_type` VARCHAR(191) NULL,
  `variance_amount` VARCHAR(191) NULL,
  `variance_percent` VARCHAR(191) NULL,
  `reason` VARCHAR(191) NULL,
  `action_required` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_financial_signals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` VARCHAR(191) NULL,
  `location_id` VARCHAR(191) NULL,
  `signal_type` VARCHAR(191) NULL,
  `severity` VARCHAR(191) NULL,
  `title` VARCHAR(191) NULL,
  `message` VARCHAR(191) NULL,
  `reference_type` VARCHAR(191) NULL,
  `reference_id` VARCHAR(191) NULL,
  `status` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_kpi_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` VARCHAR(191) NULL,
  `location_id` VARCHAR(191) NULL,
  `snapshot_date` VARCHAR(191) NULL,
  `kpi_code` VARCHAR(191) NULL,
  `kpi_value` VARCHAR(191) NULL,
  `comparison_value` VARCHAR(191) NULL,
  `trend_direction` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_closing_periods` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` VARCHAR(191) NULL,
  `location_id` VARCHAR(191) NULL,
  `period_month` VARCHAR(191) NULL,
  `period_year` VARCHAR(191) NULL,
  `status` VARCHAR(191) NULL,
  `closed_by` VARCHAR(191) NULL,
  `closed_at` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_audit_exceptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` VARCHAR(191) NULL,
  `location_id` VARCHAR(191) NULL,
  `exception_type` VARCHAR(191) NULL,
  `severity` VARCHAR(191) NULL,
  `reference_type` VARCHAR(191) NULL,
  `reference_id` VARCHAR(191) NULL,
  `description` VARCHAR(191) NULL,
  `status` VARCHAR(191) NULL,
  `resolved_by` VARCHAR(191) NULL,
  `resolved_at` VARCHAR(191) NULL,
  `created_at` VARCHAR(191) NULL,
  `updated_at` VARCHAR(191) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO expnew_financial_signals (business_id, location_id, signal_type, severity, title, message, status, created_at, updated_at)
SELECT 0, 0, 'system_ready', 'info', 'Financial Intelligence Ready', 'EXPNEW_010 financial intelligence parcel installed.', 'open', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_financial_signals WHERE signal_type='system_ready' AND title='Financial Intelligence Ready');

CREATE INDEX idx_expnew_budgets_business ON `expnew_budgets` (`business_id`);
CREATE INDEX idx_expnew_financial_signals_business ON `expnew_financial_signals` (`business_id`);
CREATE INDEX idx_expnew_kpi_snapshots_business ON `expnew_kpi_snapshots` (`business_id`);
CREATE INDEX idx_expnew_closing_periods_business ON `expnew_closing_periods` (`business_id`);
CREATE INDEX idx_expnew_audit_exceptions_business ON `expnew_audit_exceptions` (`business_id`);
