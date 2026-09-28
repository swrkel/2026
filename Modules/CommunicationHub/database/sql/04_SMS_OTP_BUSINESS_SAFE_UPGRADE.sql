-- CommunicationHub(6) - 04_SMS_OTP_BUSINESS_SAFE_UPGRADE.sql
-- Purpose: SMS-first commercial workflow hardening + OTP preparation.
-- Run in EACH tenant database. Safe for existing tenants.

SET @db := DATABASE();

-- Messages: add missing operational columns only when absent.
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND COLUMN_NAME='business_location_id')=0,
'ALTER TABLE communication_hub_messages ADD COLUMN business_location_id BIGINT UNSIGNED NULL AFTER business_id', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND COLUMN_NAME='sender_id')=0,
'ALTER TABLE communication_hub_messages ADD COLUMN sender_id VARCHAR(100) NULL AFTER provider_id', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND COLUMN_NAME='gateway_cost')=0,
'ALTER TABLE communication_hub_messages ADD COLUMN gateway_cost DECIMAL(18,4) NOT NULL DEFAULT 0.0000 AFTER client_id', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND COLUMN_NAME='selling_price')=0,
'ALTER TABLE communication_hub_messages ADD COLUMN selling_price DECIMAL(18,4) NOT NULL DEFAULT 0.0000 AFTER gateway_cost', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND COLUMN_NAME='profit_amount')=0,
'ALTER TABLE communication_hub_messages ADD COLUMN profit_amount DECIMAL(18,4) NOT NULL DEFAULT 0.0000 AFTER selling_price', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND COLUMN_NAME='wallet_charge_status')=0,
'ALTER TABLE communication_hub_messages ADD COLUMN wallet_charge_status VARCHAR(30) NULL AFTER currency', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- OTP: keep all OTP records tenant/business-aware and compatible with older schemas.
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='business_id')=0,
'ALTER TABLE communication_hub_otps ADD COLUMN business_id BIGINT UNSIGNED NULL AFTER id', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='module')=0,
'ALTER TABLE communication_hub_otps ADD COLUMN module VARCHAR(100) NULL AFTER business_id', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='recipient')=0,
'ALTER TABLE communication_hub_otps ADD COLUMN recipient VARCHAR(255) NULL AFTER module', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='identifier')=0,
'ALTER TABLE communication_hub_otps ADD COLUMN identifier VARCHAR(255) NULL AFTER recipient', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='channel')=0,
'ALTER TABLE communication_hub_otps ADD COLUMN channel VARCHAR(50) NULL AFTER identifier', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='verified_at')=0,
'ALTER TABLE communication_hub_otps ADD COLUMN verified_at TIMESTAMP NULL AFTER expires_at', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Helpful indexes, added only when absent.
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND INDEX_NAME='idx_ch_msg_business_status')=0,
'ALTER TABLE communication_hub_messages ADD INDEX idx_ch_msg_business_status (business_id, status)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND INDEX_NAME='idx_ch_msg_business_channel_status')=0,
'ALTER TABLE communication_hub_messages ADD INDEX idx_ch_msg_business_channel_status (business_id, channel, status)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND INDEX_NAME='idx_ch_otp_business_status')=0,
'ALTER TABLE communication_hub_otps ADD INDEX idx_ch_otp_business_status (business_id, status)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND INDEX_NAME='idx_ch_otp_business_identifier')=0,
'ALTER TABLE communication_hub_otps ADD INDEX idx_ch_otp_business_identifier (business_id, identifier)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
