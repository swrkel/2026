/*
Communication Hub - OTP Business Platform Upgrade
Run this in EACH TENANT database. No database name is hardcoded.
Safe for multi-tenant single-code / multiple-database deployments.
*/

SET @db := DATABASE();

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='business_id') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN business_id BIGINT UNSIGNED NULL AFTER id',
'SELECT "business_id already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='business_location_id') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN business_location_id BIGINT UNSIGNED NULL AFTER business_id',
'SELECT "business_location_id already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='created_by') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN created_by BIGINT UNSIGNED NULL AFTER business_location_id',
'SELECT "created_by already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='identifier') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN identifier VARCHAR(191) NULL AFTER recipient',
'SELECT "identifier already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='module') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN module VARCHAR(100) NULL AFTER channel',
'SELECT "module already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='max_attempts') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN max_attempts INT UNSIGNED NOT NULL DEFAULT 3 AFTER attempts',
'SELECT "max_attempts already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='message_id') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN message_id BIGINT UNSIGNED NULL AFTER otp_hash',
'SELECT "message_id already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='sent_at') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN sent_at TIMESTAMP NULL AFTER verified_at',
'SELECT "sent_at already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='plain_otp_preview') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN plain_otp_preview VARCHAR(20) NULL AFTER otp_hash',
'SELECT "plain_otp_preview already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND INDEX_NAME='ch_otps_business_status_idx') = 0,
'ALTER TABLE communication_hub_otps ADD INDEX ch_otps_business_status_idx (business_id, business_location_id, status)',
'SELECT "ch_otps_business_status_idx already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND INDEX_NAME='ch_otps_identifier_idx') = 0,
'ALTER TABLE communication_hub_otps ADD INDEX ch_otps_identifier_idx (business_id, identifier, purpose, status)',
'SELECT "ch_otps_identifier_idx already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE communication_hub_otps SET identifier = recipient WHERE identifier IS NULL AND recipient IS NOT NULL;
UPDATE communication_hub_otps SET max_attempts = 3 WHERE max_attempts IS NULL OR max_attempts = 0;
