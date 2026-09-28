-- Management Report Module - TENANT DATABASE DEFAULT DATA
-- Safe to run repeatedly. INSERT IGNORE uses the business/name unique key.
-- Inserts a standard template for every business in the currently selected tenant DB.

INSERT IGNORE INTO `mgmt_report_templates`
(`business_id`,`location_id`,`store_id`,`name`,`report_type`,`section_keys`,`filter_defaults`,`is_default`,`is_active`,`created_at`,`updated_at`)
SELECT `id`, NULL, NULL, 'Standard Daily Management Report', 'daily_management',
       JSON_ARRAY('sales','operator_sales','received_in','out','total_add','returns','financial_status','financial_status_two','financial_breakup','outstanding','stock_value','pump_variance','dip_details','final_review'),
       JSON_OBJECT('date_range','today'), 1, 1, NOW(), NOW()
FROM `business`;

SELECT DATABASE() AS `tenant_database`,
       COUNT(*) AS `management_report_templates`
FROM `mgmt_report_templates`;
