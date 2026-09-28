-- Expenses-New EXPNEW_005 Enterprise Financial Control Platform SQL
-- Run in every tenant database. Tables use expnew_ prefix only.

CREATE TABLE IF NOT EXISTS expnew_budgets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NULL,
  budget_code VARCHAR(50) NOT NULL,
  budget_name VARCHAR(191) NOT NULL,
  budget_type VARCHAR(50) DEFAULT 'monthly',
  period_start DATE NULL,
  period_end DATE NULL,
  original_amount DECIMAL(22,4) DEFAULT 0,
  revised_amount DECIMAL(22,4) DEFAULT 0,
  committed_amount DECIMAL(22,4) DEFAULT 0,
  actual_amount DECIMAL(22,4) DEFAULT 0,
  status VARCHAR(30) DEFAULT 'active',
  metadata JSON NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_budgets_code_business (business_id, budget_code)
);

CREATE TABLE IF NOT EXISTS expnew_budget_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  budget_id BIGINT UNSIGNED NOT NULL,
  department_id BIGINT UNSIGNED NULL,
  cost_center_id BIGINT UNSIGNED NULL,
  project_id BIGINT UNSIGNED NULL,
  category_id BIGINT UNSIGNED NULL,
  month_no TINYINT UNSIGNED NULL,
  original_amount DECIMAL(22,4) DEFAULT 0,
  revised_amount DECIMAL(22,4) DEFAULT 0,
  committed_amount DECIMAL(22,4) DEFAULT 0,
  actual_amount DECIMAL(22,4) DEFAULT 0,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_budget_lines_budget (budget_id)
);

CREATE TABLE IF NOT EXISTS expnew_budget_revisions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  budget_id BIGINT UNSIGNED NOT NULL,
  revision_no INT UNSIGNED DEFAULT 1,
  old_amount DECIMAL(22,4) DEFAULT 0,
  new_amount DECIMAL(22,4) DEFAULT 0,
  reason TEXT NULL,
  approved_by BIGINT UNSIGNED NULL,
  approved_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_budget_revisions_budget (budget_id)
);

CREATE TABLE IF NOT EXISTS expnew_budget_consumptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  budget_id BIGINT UNSIGNED NULL,
  budget_line_id BIGINT UNSIGNED NULL,
  expense_id BIGINT UNSIGNED NULL,
  source_type VARCHAR(80) NULL,
  source_id BIGINT UNSIGNED NULL,
  reserved_amount DECIMAL(22,4) DEFAULT 0,
  consumed_amount DECIMAL(22,4) DEFAULT 0,
  released_amount DECIMAL(22,4) DEFAULT 0,
  status VARCHAR(30) DEFAULT 'reserved',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_budget_consumptions_expense (expense_id),
  INDEX expnew_budget_consumptions_budget (budget_id)
);

CREATE TABLE IF NOT EXISTS expnew_policies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  policy_code VARCHAR(50) NOT NULL,
  policy_name VARCHAR(191) NOT NULL,
  applies_to VARCHAR(80) DEFAULT 'expense',
  action_on_violation VARCHAR(30) DEFAULT 'warning',
  is_active TINYINT(1) DEFAULT 1,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_policies_code_business (business_id, policy_code)
);

CREATE TABLE IF NOT EXISTS expnew_policy_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  policy_id BIGINT UNSIGNED NOT NULL,
  rule_key VARCHAR(80) NOT NULL,
  operator VARCHAR(20) DEFAULT '<=',
  rule_value VARCHAR(191) NULL,
  amount_limit DECIMAL(22,4) NULL,
  severity VARCHAR(30) DEFAULT 'warning',
  message TEXT NULL,
  is_active TINYINT(1) DEFAULT 1,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_policy_rules_policy (policy_id)
);

CREATE TABLE IF NOT EXISTS expnew_tax_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  tax_code VARCHAR(50) NOT NULL,
  tax_name VARCHAR(191) NOT NULL,
  tax_type VARCHAR(50) DEFAULT 'vat',
  calculation_type VARCHAR(30) DEFAULT 'exclusive',
  is_active TINYINT(1) DEFAULT 1,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_tax_codes_code_business (business_id, tax_code)
);

CREATE TABLE IF NOT EXISTS expnew_tax_rates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tax_code_id BIGINT UNSIGNED NOT NULL,
  rate DECIMAL(10,4) DEFAULT 0,
  effective_from DATE NULL,
  effective_to DATE NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_tax_rates_code (tax_code_id)
);

CREATE TABLE IF NOT EXISTS expnew_recurring_expenses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  recurring_code VARCHAR(50) NOT NULL,
  title VARCHAR(191) NOT NULL,
  frequency VARCHAR(30) DEFAULT 'monthly',
  start_date DATE NULL,
  end_date DATE NULL,
  next_run_date DATE NULL,
  max_occurrences INT UNSIGNED NULL,
  occurrence_count INT UNSIGNED DEFAULT 0,
  amount DECIMAL(22,4) DEFAULT 0,
  auto_submit TINYINT(1) DEFAULT 0,
  auto_approve TINYINT(1) DEFAULT 0,
  auto_pay TINYINT(1) DEFAULT 0,
  status VARCHAR(30) DEFAULT 'active',
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_recurring_code_business (business_id, recurring_code)
);

CREATE TABLE IF NOT EXISTS expnew_recurring_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recurring_expense_id BIGINT UNSIGNED NOT NULL,
  run_date DATE NOT NULL,
  generated_expense_id BIGINT UNSIGNED NULL,
  status VARCHAR(30) DEFAULT 'generated',
  message TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_recurring_runs_recurring (recurring_expense_id)
);

CREATE TABLE IF NOT EXISTS expnew_duplicate_checks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  expense_id BIGINT UNSIGNED NULL,
  matched_expense_id BIGINT UNSIGNED NULL,
  check_type VARCHAR(80) NOT NULL,
  score DECIMAL(8,4) DEFAULT 0,
  status VARCHAR(30) DEFAULT 'open',
  details JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_duplicate_checks_expense (expense_id)
);

CREATE TABLE IF NOT EXISTS expnew_project_cost_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id BIGINT UNSIGNED NOT NULL,
  snapshot_date DATE NOT NULL,
  expense_amount DECIMAL(22,4) DEFAULT 0,
  committed_amount DECIMAL(22,4) DEFAULT 0,
  budget_amount DECIMAL(22,4) DEFAULT 0,
  variance_amount DECIMAL(22,4) DEFAULT 0,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_project_cost_snapshots_project (project_id)
);

CREATE TABLE IF NOT EXISTS expnew_vendor_metrics (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payee_id BIGINT UNSIGNED NOT NULL,
  period_start DATE NULL,
  period_end DATE NULL,
  total_expenses DECIMAL(22,4) DEFAULT 0,
  total_paid DECIMAL(22,4) DEFAULT 0,
  outstanding DECIMAL(22,4) DEFAULT 0,
  expense_count INT UNSIGNED DEFAULT 0,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_vendor_metrics_payee (payee_id)
);
