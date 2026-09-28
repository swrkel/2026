-- Master SQL for Stage 017 Enterprise Excellence. Run in each tenant DB when applying this final parcel.

-- Communication Hub Stage 017 - Enterprise Excellence Final Stage
-- Run this SQL in EACH TENANT DATABASE. Do not prefix database names.

CREATE TABLE IF NOT EXISTS communication_hub_global_notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  business_location_id BIGINT UNSIGNED NULL,
  target_user_id BIGINT UNSIGNED NULL,
  source_module VARCHAR(100) NULL,
  type VARCHAR(50) NULL DEFAULT 'info',
  title VARCHAR(191) NOT NULL,
  message TEXT NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'unread',
  read_at TIMESTAMP NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY ch_global_notifications_business_status_idx (business_id, status),
  KEY ch_global_notifications_user_status_idx (target_user_id, status),
  KEY ch_global_notifications_module_idx (source_module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS communication_hub_event_registry (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  event_key VARCHAR(191) NOT NULL,
  event_name VARCHAR(191) NOT NULL,
  source_module VARCHAR(100) NOT NULL,
  channels JSON NULL,
  template_id BIGINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY ch_event_registry_business_key_unique (business_id, event_key),
  KEY ch_event_registry_module_idx (source_module),
  KEY ch_event_registry_active_idx (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS communication_hub_provider_health (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  provider_id BIGINT UNSIGNED NULL,
  provider_name VARCHAR(191) NOT NULL,
  channel VARCHAR(50) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'unknown',
  response_time_ms INT NULL,
  message TEXT NULL,
  checked_at TIMESTAMP NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY ch_provider_health_business_channel_idx (business_id, channel),
  KEY ch_provider_health_status_idx (status),
  KEY ch_provider_health_checked_idx (checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS communication_hub_cost_entries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  business_location_id BIGINT UNSIGNED NULL,
  channel VARCHAR(50) NOT NULL,
  provider_name VARCHAR(191) NULL,
  cost_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  currency VARCHAR(10) NULL DEFAULT 'LKR',
  reference_no VARCHAR(191) NULL,
  cost_date DATE NOT NULL,
  note TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY ch_cost_entries_business_date_idx (business_id, cost_date),
  KEY ch_cost_entries_channel_idx (channel),
  KEY ch_cost_entries_location_idx (business_location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS communication_hub_advanced_schedules (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  business_location_id BIGINT UNSIGNED NULL,
  name VARCHAR(191) NOT NULL,
  channel VARCHAR(50) NOT NULL,
  frequency VARCHAR(50) NOT NULL DEFAULT 'once',
  start_at TIMESTAMP NULL,
  end_at TIMESTAMP NULL,
  timezone VARCHAR(60) NULL DEFAULT 'Asia/Colombo',
  payload JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_run_at TIMESTAMP NULL,
  next_run_at TIMESTAMP NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY ch_advanced_schedules_business_active_idx (business_id, is_active),
  KEY ch_advanced_schedules_channel_idx (channel),
  KEY ch_advanced_schedules_next_run_idx (next_run_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS communication_hub_public_api_clients (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  client_name VARCHAR(191) NOT NULL,
  api_key VARCHAR(191) NOT NULL,
  allowed_channels JSON NULL,
  rate_limit_per_minute INT NOT NULL DEFAULT 60,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_used_at TIMESTAMP NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY ch_public_api_clients_api_key_unique (api_key),
  KEY ch_public_api_clients_business_active_idx (business_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS communication_hub_mobile_devices (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  business_location_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  platform VARCHAR(30) NULL,
  device_name VARCHAR(191) NULL,
  device_token TEXT NULL,
  app_version VARCHAR(50) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_seen_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY ch_mobile_devices_business_user_idx (business_id, user_id),
  KEY ch_mobile_devices_platform_idx (platform),
  KEY ch_mobile_devices_active_idx (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO communication_hub_event_registry
(business_id, event_key, event_name, source_module, channels, is_active, created_at, updated_at)
VALUES
(NULL, 'customer.created', 'Customer Created', 'Customers', JSON_ARRAY('sms','email','whatsapp','in_app'), 1, NOW(), NOW()),
(NULL, 'invoice.created', 'Sales Invoice Created', 'Sales', JSON_ARRAY('sms','email','whatsapp'), 1, NOW(), NOW()),
(NULL, 'invoice.paid', 'Sales Invoice Paid', 'Finance', JSON_ARRAY('sms','email','whatsapp','in_app'), 1, NOW(), NOW()),
(NULL, 'purchase.approved', 'Purchase Order Approved', 'Purchases', JSON_ARRAY('email','in_app'), 1, NOW(), NOW()),
(NULL, 'shift.closed', 'Shift Closed', 'Petro', JSON_ARRAY('sms','in_app'), 1, NOW(), NOW()),
(NULL, 'pd.settlement.finalized', 'PD Settlement Finalized', 'PetroPD', JSON_ARRAY('sms','email','whatsapp','in_app'), 1, NOW(), NOW()),
(NULL, 'membership.expiring', 'Membership Expiring', 'Membership', JSON_ARRAY('sms','email','whatsapp'), 1, NOW(), NOW()),
(NULL, 'loan.approved', 'Loan Approved', 'BankingSuite', JSON_ARRAY('sms','email','whatsapp'), 1, NOW(), NOW());
