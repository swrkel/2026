-- POS Standalone S382 - Sync Conflict Manager
-- Global SQL. Run inside each tenant database. Do not add database names.

ALTER TABLE pos_offline_sync_conflicts
    ADD COLUMN IF NOT EXISTS resolution_action VARCHAR(80) NULL AFTER resolution_status,
    ADD COLUMN IF NOT EXISTS manager_decision_payload JSON NULL AFTER resolution_note;

ALTER TABLE pos_offline_sync_queue
    ADD COLUMN IF NOT EXISTS dependency_key VARCHAR(191) NULL AFTER transaction_type,
    ADD COLUMN IF NOT EXISTS parent_client_token VARCHAR(120) NULL AFTER dependency_key,
    ADD COLUMN IF NOT EXISTS sync_priority INT NOT NULL DEFAULT 100 AFTER parent_client_token,
    ADD COLUMN IF NOT EXISTS manager_resolution JSON NULL AFTER preflight_warnings;

CREATE TABLE IF NOT EXISTS pos_offline_sync_audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    queue_id BIGINT UNSIGNED NULL,
    conflict_id BIGINT UNSIGNED NULL,
    device_uuid VARCHAR(80) NULL,
    action VARCHAR(80) NOT NULL,
    action_note TEXT NULL,
    before_payload LONGTEXT NULL,
    after_payload LONGTEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_pos_offline_audit_queue (queue_id),
    KEY idx_pos_offline_audit_conflict (conflict_id),
    KEY idx_pos_offline_audit_device (device_uuid),
    KEY idx_pos_offline_audit_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_pos_offline_queue_dependency ON pos_offline_sync_queue (parent_client_token, status, sync_priority);
CREATE INDEX IF NOT EXISTS idx_pos_offline_conflict_type_status ON pos_offline_sync_conflicts (conflict_type, resolution_status);
