-- EXPNEW_009 Tenant Create Tables - Enterprise Command Center
CREATE TABLE IF NOT EXISTS expnew_command_widgets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  widget_key VARCHAR(100) NOT NULL,
  widget_title VARCHAR(191) NOT NULL,
  widget_type VARCHAR(50) NOT NULL DEFAULT 'kpi',
  settings JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_cmd_widget_unique (business_id, widget_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_command_preferences (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  business_id BIGINT UNSIGNED NULL,
  workspace_key VARCHAR(100) NOT NULL,
  preferences JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_cmd_pref_unique (user_id, business_id, workspace_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_operation_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NULL,
  business_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NULL,
  source_type VARCHAR(100) NULL,
  source_id BIGINT UNSIGNED NULL,
  event_type VARCHAR(100) NOT NULL,
  event_title VARCHAR(191) NOT NULL,
  event_message TEXT NULL,
  event_time DATETIME NOT NULL,
  severity VARCHAR(30) NOT NULL DEFAULT 'info',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_ops_event_time (event_time),
  INDEX expnew_ops_business (business_id, location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_approval_queues (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NULL,
  expense_id BIGINT UNSIGNED NULL,
  expense_no VARCHAR(100) NULL,
  amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  current_level INT NOT NULL DEFAULT 1,
  assigned_to BIGINT UNSIGNED NULL,
  assigned_role VARCHAR(100) NULL,
  priority INT NOT NULL DEFAULT 0,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  last_action VARCHAR(50) NULL,
  last_action_by BIGINT UNSIGNED NULL,
  last_action_at DATETIME NULL,
  last_comments TEXT NULL,
  due_at DATETIME NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_appr_status (status, priority),
  INDEX expnew_appr_business (business_id, location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_command_alerts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NULL,
  alert_type VARCHAR(100) NOT NULL,
  alert_title VARCHAR(191) NOT NULL,
  alert_message TEXT NULL,
  source_type VARCHAR(100) NULL,
  source_id BIGINT UNSIGNED NULL,
  severity VARCHAR(30) NOT NULL DEFAULT 'warning',
  is_resolved TINYINT(1) NOT NULL DEFAULT 0,
  resolved_by BIGINT UNSIGNED NULL,
  resolved_at DATETIME NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_alert_active (alert_type, is_resolved),
  INDEX expnew_alert_business (business_id, location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expnew_saved_filters (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  filter_name VARCHAR(191) NOT NULL,
  filter_context VARCHAR(100) NOT NULL,
  filter_payload JSON NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- EXPNEW_009 Default widgets - safe re-run
INSERT INTO expnew_command_widgets (business_id, widget_key, widget_title, widget_type, sort_order, is_active, created_at, updated_at)
SELECT NULL, 'today_expenses', 'Today Expenses', 'kpi', 10, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_command_widgets WHERE business_id IS NULL AND widget_key='today_expenses');
INSERT INTO expnew_command_widgets (business_id, widget_key, widget_title, widget_type, sort_order, is_active, created_at, updated_at)
SELECT NULL, 'pending_approvals', 'Pending Approvals', 'kpi', 20, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_command_widgets WHERE business_id IS NULL AND widget_key='pending_approvals');
INSERT INTO expnew_command_widgets (business_id, widget_key, widget_title, widget_type, sort_order, is_active, created_at, updated_at)
SELECT NULL, 'pending_payments', 'Pending Payments', 'kpi', 30, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_command_widgets WHERE business_id IS NULL AND widget_key='pending_payments');
INSERT INTO expnew_command_widgets (business_id, widget_key, widget_title, widget_type, sort_order, is_active, created_at, updated_at)
SELECT NULL, 'budget_alerts', 'Budget Alerts', 'alert', 40, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_command_widgets WHERE business_id IS NULL AND widget_key='budget_alerts');
