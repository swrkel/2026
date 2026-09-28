-- POS Standalone S381 - Offline Cache, Stock Snapshot and Conflict Validation
-- Global SQL. Do not add database names.

ALTER TABLE pos_offline_sync_queue
    ADD COLUMN IF NOT EXISTS cache_version_hash VARCHAR(191) NULL AFTER server_invoice_no,
    ADD COLUMN IF NOT EXISTS preflight_status VARCHAR(50) NULL AFTER cache_version_hash,
    ADD COLUMN IF NOT EXISTS preflight_warnings JSON NULL AFTER preflight_status;

ALTER TABLE pos_offline_sync_conflicts
    ADD COLUMN IF NOT EXISTS resolved_by BIGINT UNSIGNED NULL AFTER resolution_status,
    ADD COLUMN IF NOT EXISTS resolution_note TEXT NULL AFTER resolved_by;

CREATE INDEX IF NOT EXISTS idx_pos_offline_queue_device_status ON pos_offline_sync_queue (device_uuid, status);
CREATE INDEX IF NOT EXISTS idx_pos_offline_queue_invoice ON pos_offline_sync_queue (offline_invoice_no);
CREATE INDEX IF NOT EXISTS idx_pos_offline_conflict_status ON pos_offline_sync_conflicts (resolution_status, resolved_at);
