-- Communication Hub Stage 004: WhatsApp Business Platform
-- Run this inside EACH tenant database. No database name is specified because this ERP uses multiple tenant databases.

CREATE TABLE IF NOT EXISTS communication_hub_whatsapp_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    profile_name VARCHAR(191) NOT NULL,
    provider_name VARCHAR(100) NOT NULL DEFAULT 'meta_cloud_api',
    phone_number VARCHAR(30) NOT NULL,
    business_account_id VARCHAR(191) NULL,
    phone_number_id VARCHAR(191) NULL,
    api_base_url VARCHAR(500) NULL,
    access_token TEXT NULL,
    webhook_verify_token VARCHAR(191) NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX ch_wa_profiles_business_idx (business_id, business_location_id),
    INDEX ch_wa_profiles_status_idx (status),
    INDEX ch_wa_profiles_default_idx (business_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS communication_hub_whatsapp_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    provider_template_id VARCHAR(191) NULL,
    template_name VARCHAR(191) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'utility',
    language_code VARCHAR(20) NOT NULL DEFAULT 'en',
    header_type VARCHAR(30) NULL,
    header_text VARCHAR(500) NULL,
    body TEXT NOT NULL,
    footer_text VARCHAR(500) NULL,
    variables TEXT NULL,
    buttons_json LONGTEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    approved_at TIMESTAMP NULL DEFAULT NULL,
    rejected_reason TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX ch_wa_templates_business_idx (business_id, business_location_id),
    INDEX ch_wa_templates_status_idx (status),
    INDEX ch_wa_templates_name_idx (template_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS communication_hub_whatsapp_campaigns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    profile_id BIGINT UNSIGNED NULL,
    template_id BIGINT UNSIGNED NULL,
    campaign_name VARCHAR(191) NOT NULL,
    audience_source VARCHAR(100) NULL,
    recipient_count INT NOT NULL DEFAULT 0,
    queued_count INT NOT NULL DEFAULT 0,
    sent_count INT NOT NULL DEFAULT 0,
    failed_count INT NOT NULL DEFAULT 0,
    read_count INT NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL DEFAULT NULL,
    started_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX ch_wa_campaigns_business_idx (business_id, business_location_id),
    INDEX ch_wa_campaigns_status_idx (status),
    INDEX ch_wa_campaigns_schedule_idx (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



DELIMITER $$
DROP PROCEDURE IF EXISTS ch_add_column_if_missing $$
CREATE PROCEDURE ch_add_column_if_missing(IN p_table VARCHAR(128), IN p_column VARCHAR(128), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = p_table AND column_name = p_column) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS ch_add_index_if_missing $$
CREATE PROCEDURE ch_add_index_if_missing(IN p_table VARCHAR(128), IN p_index VARCHAR(128), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;

CALL ch_add_column_if_missing('communication_hub_messages', 'business_location_id', 'BIGINT UNSIGNED NULL AFTER `business_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'profile_id', 'BIGINT UNSIGNED NULL AFTER `client_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'campaign_id', 'BIGINT UNSIGNED NULL AFTER `profile_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'message_type', 'VARCHAR(30) NULL AFTER `channel`');
CALL ch_add_column_if_missing('communication_hub_messages', 'media_url', 'VARCHAR(1000) NULL AFTER `message`');
CALL ch_add_column_if_missing('communication_hub_messages', 'caption', 'VARCHAR(1000) NULL AFTER `media_url`');
CALL ch_add_column_if_missing('communication_hub_messages', 'provider_message_id', 'VARCHAR(191) NULL AFTER `response_message`');
CALL ch_add_column_if_missing('communication_hub_messages', 'delivered_at', 'TIMESTAMP NULL DEFAULT NULL AFTER `sent_at`');
CALL ch_add_column_if_missing('communication_hub_messages', 'read_at', 'TIMESTAMP NULL DEFAULT NULL AFTER `delivered_at`');

CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_wa_business_idx', '(`business_id`, `business_location_id`, `channel`, `status`)');
CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_wa_profile_idx', '(`profile_id`, `channel`, `status`)');
CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_wa_campaign_idx', '(`campaign_id`, `channel`, `status`)');

DROP PROCEDURE IF EXISTS ch_add_column_if_missing;
DROP PROCEDURE IF EXISTS ch_add_index_if_missing;

-- Optional draft default profile creation when a tenant has a businesses table.
DELIMITER $$
DROP PROCEDURE IF EXISTS ch_seed_default_whatsapp_profiles $$
CREATE PROCEDURE ch_seed_default_whatsapp_profiles()
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'businesses') THEN
        INSERT INTO communication_hub_whatsapp_profiles
            (business_id, business_location_id, profile_name, provider_name, phone_number, is_default, status, created_at, updated_at)
        SELECT b.id, NULL, CONCAT('Default WhatsApp - ', COALESCE(b.name, b.id)), 'meta_cloud_api', '', 1, 'draft', NOW(), NOW()
        FROM businesses b
        WHERE NOT EXISTS (
            SELECT 1 FROM communication_hub_whatsapp_profiles p WHERE p.business_id = b.id
        )
        LIMIT 500;
    END IF;
END $$
DELIMITER ;
CALL ch_seed_default_whatsapp_profiles();
DROP PROCEDURE IF EXISTS ch_seed_default_whatsapp_profiles;
