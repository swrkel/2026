-- StockTransferNew safe default settings insert. Replace BUSINESS_ID before running per tenant/business.
INSERT INTO `stnew_stock_transfer_settings` (`business_id`,`approval_required`,`allow_partial_receive`,`created_at`,`updated_at`)
SELECT BUSINESS_ID,1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `stnew_stock_transfer_settings` WHERE `business_id`=BUSINESS_ID);
