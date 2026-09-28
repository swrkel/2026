-- HOTELMGT_014_SQL.sql
-- Hotel Management Parcel 014 specific SQL only.
-- Purpose: management analytics snapshots, report export/audit support and business/location scope.

ALTER TABLE hm_report_snapshots
    ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id,
    ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id,
    ADD COLUMN IF NOT EXISTS created_by BIGINT UNSIGNED NULL AFTER payload,
    ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;

CREATE INDEX IF NOT EXISTS hm_report_snapshots_business_location_idx ON hm_report_snapshots (business_id, business_location_id);
CREATE INDEX IF NOT EXISTS hm_report_snapshots_report_date_idx ON hm_report_snapshots (report_key, snapshot_date);
CREATE INDEX IF NOT EXISTS hm_report_snapshots_deleted_idx ON hm_report_snapshots (deleted_at);

CREATE TABLE IF NOT EXISTS hm_report_export_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    report_key VARCHAR(100) NOT NULL,
    export_type VARCHAR(30) NULL,
    filters JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    INDEX hm_report_export_logs_scope_idx (business_id, business_location_id),
    INDEX hm_report_export_logs_report_idx (report_key, export_type),
    INDEX hm_report_export_logs_deleted_idx (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
