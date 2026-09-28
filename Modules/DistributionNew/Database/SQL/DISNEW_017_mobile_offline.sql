-- DISNEW_017 Mobile / Customer / Driver API + Offline Operations

CREATE TABLE IF NOT EXISTS disnew_mobile_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  device_uuid VARCHAR(100) NOT NULL,
  token VARCHAR(128) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  expires_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY disnew_mobile_tokens_token_unique (token),
  KEY disnew_mobile_tokens_business_device_idx (business_id, device_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_offline_devices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  device_uuid VARCHAR(100) NOT NULL,
  device_name VARCHAR(191) NULL,
  platform VARCHAR(50) NULL,
  app_version VARCHAR(50) NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  last_seen_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY disnew_offline_devices_uuid_unique (device_uuid),
  KEY disnew_offline_devices_business_idx (business_id, location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_sync_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  device_uuid VARCHAR(100) NULL,
  batch_uuid VARCHAR(100) NOT NULL,
  direction VARCHAR(20) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'received',
  payload_count INT NOT NULL DEFAULT 0,
  conflict_count INT NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  processed_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY disnew_sync_batches_uuid_unique (batch_uuid),
  KEY disnew_sync_batches_business_idx (business_id, location_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_sync_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sync_batch_id BIGINT UNSIGNED NOT NULL,
  entity_type VARCHAR(100) NOT NULL,
  entity_uuid VARCHAR(100) NULL,
  operation VARCHAR(30) NOT NULL DEFAULT 'upsert',
  payload_json LONGTEXT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'queued',
  conflict_reason TEXT NULL,
  processed_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  KEY disnew_sync_items_batch_idx (sync_batch_id),
  KEY disnew_sync_items_entity_idx (entity_type, entity_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_delivery_checkpoints (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  trip_id BIGINT UNSIGNED NULL,
  delivery_id BIGINT UNSIGNED NULL,
  checkpoint_type VARCHAR(50) NOT NULL,
  latitude DECIMAL(12,8) NULL,
  longitude DECIMAL(12,8) NULL,
  remarks TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  KEY disnew_delivery_checkpoints_trip_idx (business_id, trip_id, delivery_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disnew_epods (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  trip_id BIGINT UNSIGNED NULL,
  delivery_id BIGINT UNSIGNED NULL,
  receiver_name VARCHAR(191) NULL,
  receiver_mobile VARCHAR(50) NULL,
  signature_path VARCHAR(255) NULL,
  photo_path VARCHAR(255) NULL,
  remarks TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  KEY disnew_epods_delivery_idx (business_id, delivery_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('distribution_new.mobile_sync.view','web',NOW(),NOW()),
('distribution_new.mobile_devices.view','web',NOW(),NOW()),
('distribution_new.mobile_api.access','web',NOW(),NOW()),
('distribution_new.driver_api.access','web',NOW(),NOW()),
('distribution_new.customer_api.access','web',NOW(),NOW());
