-- POS Standalone S383 - Background Synchronization Engine
-- Global SQL: run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS pos_sync_network_samples (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    device_uuid VARCHAR(120) NOT NULL,
    latency_ms INT NULL DEFAULT 0,
    online TINYINT(1) NOT NULL DEFAULT 1,
    quality VARCHAR(40) NULL,
    sampled_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_pos_sync_net_device (device_uuid),
    INDEX idx_pos_sync_net_sampled (sampled_at),
    INDEX idx_pos_sync_net_location (business_id, location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE pos_devices
    ADD COLUMN IF NOT EXISTS last_seen_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS status VARCHAR(40) NULL,
    ADD COLUMN IF NOT EXISTS network_quality VARCHAR(40) NULL,
    ADD COLUMN IF NOT EXISTS queue_size INT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS browser_info TEXT NULL;

ALTER TABLE pos_offline_sync_queue
    ADD COLUMN IF NOT EXISTS dependency_key VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS parent_client_token VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS priority INT NOT NULL DEFAULT 100,
    ADD COLUMN IF NOT EXISTS locked_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS locked_by VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS batch_id VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS payload_hash VARCHAR(128) NULL,
    ADD INDEX IF NOT EXISTS idx_pos_sync_queue_priority (status, priority, id),
    ADD INDEX IF NOT EXISTS idx_pos_sync_queue_batch (batch_id),
    ADD INDEX IF NOT EXISTS idx_pos_sync_queue_parent (parent_client_token);
