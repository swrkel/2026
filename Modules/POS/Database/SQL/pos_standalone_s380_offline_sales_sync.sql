-- POS Standalone S380 - Offline Sales Sync
-- Run in every tenant database that will use POS offline sales.
-- No database name is hardcoded.

ALTER TABLE `pos_sales`
  ADD COLUMN IF NOT EXISTS `offline_client_token` VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS `offline_invoice_no` VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS `offline_device_uuid` VARCHAR(80) NULL,
  ADD COLUMN IF NOT EXISTS `offline_synced_at` DATETIME NULL;

CREATE UNIQUE INDEX IF NOT EXISTS `pos_sales_offline_client_token_unique` ON `pos_sales` (`offline_client_token`);
CREATE INDEX IF NOT EXISTS `pos_sales_offline_invoice_idx` ON `pos_sales` (`offline_invoice_no`);
CREATE INDEX IF NOT EXISTS `pos_sales_offline_device_idx` ON `pos_sales` (`offline_device_uuid`);

ALTER TABLE `pos_offline_sync_queue`
  ADD COLUMN IF NOT EXISTS `locked_at` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `locked_by` VARCHAR(120) NULL;

CREATE INDEX IF NOT EXISTS `pos_offline_sync_queue_type_status_idx` ON `pos_offline_sync_queue` (`transaction_type`, `status`);

-- S380 enables server posting of offline sales only.
-- Offline returns, offline register close and offline cash reconciliation remain conflict-controlled until later stages.
