-- Stock Transfer-New final alter table guard SQL
-- Tenant database only. Safe guards are intentionally additive.

ALTER TABLE stn_transfers
    ADD COLUMN IF NOT EXISTS final_checked_at DATETIME NULL AFTER updated_at,
    ADD COLUMN IF NOT EXISTS final_checked_by INT UNSIGNED NULL AFTER final_checked_at;

ALTER TABLE stn_transfer_lines
    ADD COLUMN IF NOT EXISTS final_variance_note TEXT NULL AFTER updated_at;
