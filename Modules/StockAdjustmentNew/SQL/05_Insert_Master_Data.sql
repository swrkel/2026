INSERT INTO `san_stock_adjustment_reasons`
(`business_id`,`name`,`code`,`effect`,`requires_approval`,`is_active`,`created_at`,`updated_at`)
SELECT NULL,'Physical stock count difference','COUNT_DIFF','both',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `san_stock_adjustment_reasons` WHERE `business_id` IS NULL AND `code`='COUNT_DIFF');

INSERT INTO `san_stock_adjustment_reasons`
(`business_id`,`name`,`code`,`effect`,`requires_approval`,`is_active`,`created_at`,`updated_at`)
SELECT NULL,'Damaged stock write-off','DAMAGE','decrease',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `san_stock_adjustment_reasons` WHERE `business_id` IS NULL AND `code`='DAMAGE');

INSERT INTO `san_stock_adjustment_reasons`
(`business_id`,`name`,`code`,`effect`,`requires_approval`,`is_active`,`created_at`,`updated_at`)
SELECT NULL,'Expired stock write-off','EXPIRY','decrease',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `san_stock_adjustment_reasons` WHERE `business_id` IS NULL AND `code`='EXPIRY');

INSERT INTO `san_stock_adjustment_reasons`
(`business_id`,`name`,`code`,`effect`,`requires_approval`,`is_active`,`created_at`,`updated_at`)
SELECT NULL,'System correction','SYSTEM_CORRECTION','both',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `san_stock_adjustment_reasons` WHERE `business_id` IS NULL AND `code`='SYSTEM_CORRECTION');

INSERT INTO `san_stock_adjustment_reasons`
(`business_id`,`name`,`code`,`effect`,`requires_approval`,`is_active`,`created_at`,`updated_at`)
SELECT NULL,'Opening balance correction','OPENING_CORRECTION','both',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `san_stock_adjustment_reasons` WHERE `business_id` IS NULL AND `code`='OPENING_CORRECTION');
