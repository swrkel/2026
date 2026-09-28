-- Set the business and user that should initially administer Egg Management.
SET @BUSINESS_ID = 1;
SET @USER_ID = 1;
INSERT INTO egg_access_grants (business_id,user_id,permission,allowed,created_at,updated_at) VALUES
(@BUSINESS_ID,@USER_ID,'egg.dashboard.view',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.flocks.view',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.flocks.create',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.production.view',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.production.create',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.grading.view',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.grading.create',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.stock.view',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.sales.view',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.sales.create',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.purchases.view',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.purchases.create',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.transfers.view',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.transfers.create',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.adjustments.view',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.adjustments.create',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.settings.view',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.settings.manage',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.integrations.view',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.share',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.reports.production',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.reports.stock',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.reports.sales',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.reports.purchases',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.reports.movements',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.reports.wastage',1,NOW(),NOW()),
(@BUSINESS_ID,@USER_ID,'egg.reports.audit',1,NOW(),NOW())
ON DUPLICATE KEY UPDATE allowed=1,updated_at=NOW();
