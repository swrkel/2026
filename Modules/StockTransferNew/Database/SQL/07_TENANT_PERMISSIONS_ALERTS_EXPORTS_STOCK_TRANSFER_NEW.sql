-- StockTransferNew STN_005 tenant SQL
ALTER TABLE stnew_stock_transfer_settings
  ADD COLUMN IF NOT EXISTS require_stock_before_dispatch TINYINT(1) NOT NULL DEFAULT 0 AFTER allow_partial_receive,
  ADD COLUMN IF NOT EXISTS auto_generate_document_numbers TINYINT(1) NOT NULL DEFAULT 1 AFTER require_stock_before_dispatch;

CREATE TABLE IF NOT EXISTS stnew_stock_transfer_alerts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  transfer_id BIGINT UNSIGNED NOT NULL,
  event VARCHAR(191) NOT NULL,
  message TEXT NULL,
  target_user_id BIGINT UNSIGNED NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX stnew_alert_business_idx (business_id),
  INDEX stnew_alert_transfer_idx (transfer_id),
  INDEX stnew_alert_event_idx (event),
  INDEX stnew_alert_user_idx (target_user_id),
  INDEX stnew_alert_read_idx (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permission keys: stocktransfernew.view, stocktransfernew.create, stocktransfernew.edit,
-- stocktransfernew.submit, stocktransfernew.approve, stocktransfernew.dispatch,
-- stocktransfernew.receive, stocktransfernew.reports, stocktransfernew.settings, stocktransfernew.export
