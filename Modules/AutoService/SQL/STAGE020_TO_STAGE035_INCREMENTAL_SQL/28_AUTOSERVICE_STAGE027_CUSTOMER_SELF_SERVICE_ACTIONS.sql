-- AutoService Stage 027 - Customer Self Service Actions
-- Apply this SQL to every tenant database that uses the Auto Service module.
-- The script is safe for multi-tenant single-code / multiple-database deployments.

SET @database_name = DATABASE();

-- Appointment portal request support
SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_appointments' AND COLUMN_NAME='request_source') = 0,
    'ALTER TABLE auto_service_appointments ADD COLUMN request_source VARCHAR(50) NULL AFTER status',
    'SELECT "auto_service_appointments.request_source already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_appointments' AND COLUMN_NAME='customer_name') = 0,
    'ALTER TABLE auto_service_appointments ADD COLUMN customer_name VARCHAR(191) NULL AFTER request_source',
    'SELECT "auto_service_appointments.customer_name already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_appointments' AND COLUMN_NAME='customer_mobile') = 0,
    'ALTER TABLE auto_service_appointments ADD COLUMN customer_mobile VARCHAR(50) NULL AFTER customer_name',
    'SELECT "auto_service_appointments.customer_mobile already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_appointments' AND COLUMN_NAME='customer_email') = 0,
    'ALTER TABLE auto_service_appointments ADD COLUMN customer_email VARCHAR(191) NULL AFTER customer_mobile',
    'SELECT "auto_service_appointments.customer_email already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Approval response traceability
SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_approval_requests' AND COLUMN_NAME='customer_response_ip') = 0,
    'ALTER TABLE auto_service_approval_requests ADD COLUMN customer_response_ip VARCHAR(64) NULL AFTER customer_note',
    'SELECT "auto_service_approval_requests.customer_response_ip already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Portal feature flags
SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_settings') > 0,
    'INSERT INTO auto_service_settings (business_id, `key`, `value`, created_at, updated_at) SELECT NULL, ''allow_customer_appointment_request'', ''1'', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key`=''allow_customer_appointment_request'' AND business_id IS NULL)',
    'SELECT "auto_service_settings table missing - skipped allow_customer_appointment_request" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_settings') > 0,
    'INSERT INTO auto_service_settings (business_id, `key`, `value`, created_at, updated_at) SELECT NULL, ''allow_customer_portal_approval_response'', ''1'', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key`=''allow_customer_portal_approval_response'' AND business_id IS NULL)',
    'SELECT "auto_service_settings table missing - skipped allow_customer_portal_approval_response" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Optional permission keys for admin visibility/configuration.
SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='permissions') > 0,
    'INSERT INTO permissions (`name`, guard_name, created_at, updated_at) SELECT ''autoservice.customer_portal.self_service'', ''web'', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE `name`=''autoservice.customer_portal.self_service'')',
    'SELECT "permissions table missing - skipped autoservice.customer_portal.self_service" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
