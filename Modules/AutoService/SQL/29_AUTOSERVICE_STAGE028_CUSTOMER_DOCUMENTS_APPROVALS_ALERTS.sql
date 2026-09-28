/*
 AutoService Stage 028 - Customer Documents, Approval Evidence and Portal Alerts
 Run this on each tenant database. No database name is hard-coded.
*/

SET @database_name = DATABASE();

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_documents' AND COLUMN_NAME='uploaded_by_customer') = 0,
'ALTER TABLE auto_service_documents ADD COLUMN uploaded_by_customer TINYINT(1) NOT NULL DEFAULT 0 AFTER customer_download_allowed',
'SELECT "auto_service_documents.uploaded_by_customer already exists" AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_documents' AND COLUMN_NAME='customer_name') = 0,
'ALTER TABLE auto_service_documents ADD COLUMN customer_name VARCHAR(191) NULL AFTER uploaded_by_customer',
'SELECT "auto_service_documents.customer_name already exists" AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_documents' AND COLUMN_NAME='customer_mobile') = 0,
'ALTER TABLE auto_service_documents ADD COLUMN customer_mobile VARCHAR(50) NULL AFTER customer_name',
'SELECT "auto_service_documents.customer_mobile already exists" AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_documents' AND COLUMN_NAME='customer_upload_ip') = 0,
'ALTER TABLE auto_service_documents ADD COLUMN customer_upload_ip VARCHAR(64) NULL AFTER customer_mobile',
'SELECT "auto_service_documents.customer_upload_ip already exists" AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_documents' AND COLUMN_NAME='document_status') = 0,
'ALTER TABLE auto_service_documents ADD COLUMN document_status VARCHAR(50) NOT NULL DEFAULT "active" AFTER customer_upload_ip, ADD INDEX idx_as_doc_status (document_status)',
'SELECT "auto_service_documents.document_status already exists" AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO auto_service_settings (business_id, `key`, `value`, created_at, updated_at)
SELECT NULL, 'allow_customer_document_upload', '1', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_settings')
AND NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key`='allow_customer_document_upload');

INSERT INTO auto_service_settings (business_id, `key`, `value`, created_at, updated_at)
SELECT NULL, 'notify_service_advisor_on_customer_upload', '1', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_settings')
AND NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key`='notify_service_advisor_on_customer_upload');

INSERT INTO auto_service_settings (business_id, `key`, `value`, created_at, updated_at)
SELECT NULL, 'allow_customer_photo_upload', '1', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_settings')
AND NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key`='allow_customer_photo_upload');

/* Verification */
SELECT 'Stage 028 customer portal documents upgrade completed' AS status;
