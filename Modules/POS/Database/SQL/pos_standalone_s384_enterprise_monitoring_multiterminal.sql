-- POS Standalone S384 - Enterprise Monitoring & Multi-Terminal Synchronization
-- Global SQL: run inside each tenant database. No database name is hardcoded.

ALTER TABLE pos_devices
    ADD COLUMN IF NOT EXISTS register_id BIGINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS cashier_id BIGINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS pos_version VARCHAR(60) NULL,
    ADD COLUMN IF NOT EXISTS trust_status VARCHAR(40) NOT NULL DEFAULT 'trusted',
    ADD COLUMN IF NOT EXISTS trusted_by BIGINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS trusted_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS device_certificate_hash VARCHAR(128) NULL,
    ADD COLUMN IF NOT EXISTS last_successful_sync_at TIMESTAMP NULL,
    ADD INDEX IF NOT EXISTS idx_pos_devices_location_seen (business_id, location_id, last_seen_at),
    ADD INDEX IF NOT EXISTS idx_pos_devices_trust (trust_status),
    ADD INDEX IF NOT EXISTS idx_pos_devices_register (register_id);

CREATE TABLE IF NOT EXISTS pos_sync_device_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    device_uuid VARCHAR(120) NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    event_message VARCHAR(500) NULL,
    queue_size INT NULL DEFAULT 0,
    network_quality VARCHAR(40) NULL,
    payload JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_pos_sync_device_events_device (device_uuid),
    INDEX idx_pos_sync_device_events_type (event_type),
    INDEX idx_pos_sync_device_events_location (business_id, location_id),
    INDEX idx_pos_sync_device_events_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE pos_offline_sync_queue
    ADD COLUMN IF NOT EXISTS branch_sync_scope VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS replay_nonce VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS request_signature VARCHAR(255) NULL,
    ADD INDEX IF NOT EXISTS idx_pos_sync_queue_scope (business_id, location_id, status),
    ADD INDEX IF NOT EXISTS idx_pos_sync_queue_replay (replay_nonce);
