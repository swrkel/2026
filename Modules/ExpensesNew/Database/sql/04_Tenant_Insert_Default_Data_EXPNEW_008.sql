-- EXPNEW_008 Default Data - idempotent style
INSERT INTO `expnew_kpi_metrics` (`business_id`,`name`,`code`,`status`,`created_at`,`updated_at`)
SELECT NULL,'Expense Ratio','EXPENSE_RATIO','active',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `expnew_kpi_metrics` WHERE `code`='EXPENSE_RATIO' AND `business_id` IS NULL);

INSERT INTO `expnew_kpi_metrics` (`business_id`,`name`,`code`,`status`,`created_at`,`updated_at`)
SELECT NULL,'Cost Per Employee','COST_PER_EMPLOYEE','active',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `expnew_kpi_metrics` WHERE `code`='COST_PER_EMPLOYEE' AND `business_id` IS NULL);
