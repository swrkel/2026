-- EXPNEW_007 Tenant Create Tables
CREATE TABLE IF NOT EXISTS expnew_integration_sources (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  source_module VARCHAR(100) NOT NULL,
  display_name VARCHAR(191) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  settings_json LONGTEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_source_unique (business_id, source_module)
);

CREATE TABLE IF NOT EXISTS expnew_integration_postings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  business_location_id BIGINT UNSIGNED NULL,
  expense_id BIGINT UNSIGNED NULL,
  source_module VARCHAR(100) NOT NULL,
  source_reference VARCHAR(191) NULL,
  posting_type VARCHAR(50) NOT NULL DEFAULT 'expense',
  amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'LKR',
  payload_json LONGTEXT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'received',
  processed_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_postings_business_idx (business_id, business_location_id),
  INDEX expnew_postings_source_idx (source_module, source_reference),
  INDEX expnew_postings_status_idx (status)
);

CREATE TABLE IF NOT EXISTS expnew_integration_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  posting_id BIGINT UNSIGNED NULL,
  log_level VARCHAR(50) NOT NULL DEFAULT 'info',
  message TEXT NULL,
  context_json LONGTEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS expnew_notification_templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  event_key VARCHAR(120) NOT NULL,
  subject VARCHAR(191) NULL,
  body LONGTEXT NULL,
  channels_json LONGTEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY expnew_notification_template_unique (business_id, event_key)
);

CREATE TABLE IF NOT EXISTS expnew_notification_queue (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_key VARCHAR(120) NOT NULL,
  channels_json LONGTEXT NULL,
  payload_json LONGTEXT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  sent_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_notification_status_idx (status, event_key)
);

CREATE TABLE IF NOT EXISTS expnew_api_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  name VARCHAR(191) NOT NULL,
  token_hash VARCHAR(191) NOT NULL,
  abilities_json LONGTEXT NULL,
  last_used_at TIMESTAMP NULL,
  expires_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS expnew_webhook_endpoints (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  name VARCHAR(191) NOT NULL,
  endpoint_url VARCHAR(500) NOT NULL,
  events_json LONGTEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS expnew_webhook_deliveries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  endpoint_id BIGINT UNSIGNED NOT NULL,
  event_key VARCHAR(120) NOT NULL,
  payload_json LONGTEXT NULL,
  response_code INT NULL,
  response_body LONGTEXT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  delivered_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX expnew_webhook_delivery_idx (endpoint_id, status)
);


-- EXPNEW_007 Idempotent Default Data
INSERT INTO expnew_integration_sources (business_id, source_module, display_name, is_active, created_at, updated_at)
SELECT NULL, 'HRManager', 'HR Manager', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_integration_sources WHERE business_id IS NULL AND source_module='HRManager');
INSERT INTO expnew_integration_sources (business_id, source_module, display_name, is_active, created_at, updated_at)
SELECT NULL, 'POS', 'Point of Sale', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_integration_sources WHERE business_id IS NULL AND source_module='POS');
INSERT INTO expnew_notification_templates (business_id, event_key, subject, body, channels_json, is_active, created_at, updated_at)
SELECT NULL, 'expense.submitted', 'Expense Submitted', 'An expense has been submitted for approval.', '["system","email"]', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_notification_templates WHERE business_id IS NULL AND event_key='expense.submitted');
INSERT INTO expnew_notification_templates (business_id, event_key, subject, body, channels_json, is_active, created_at, updated_at)
SELECT NULL, 'expense.approved', 'Expense Approved', 'An expense has been approved.', '["system","email"]', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_notification_templates WHERE business_id IS NULL AND event_key='expense.approved');
INSERT INTO expnew_notification_templates (business_id, event_key, subject, body, channels_json, is_active, created_at, updated_at)
SELECT NULL, 'expense.paid', 'Expense Paid', 'An expense payment has been completed.', '["system","sms","email"]', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_notification_templates WHERE business_id IS NULL AND event_key='expense.paid');
