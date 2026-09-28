-- Communication Hub Stage 005: Push Notifications Platform
-- Run this inside EACH tenant database. No database name is specified because this ERP uses multiple tenant databases.

CREATE TABLE IF NOT EXISTS communication_hub_push_devices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NULL,
    device_name VARCHAR(191) NULL,
    platform VARCHAR(50) NOT NULL DEFAULT 'web',
    browser VARCHAR(100) NULL,
    device_group VARCHAR(100) NULL,
    device_token TEXT NOT NULL,
    endpoint TEXT NULL,
    public_key TEXT NULL,
    auth_token TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    last_seen_at TIMESTAMP NULL DEFAULT NULL,
    disabled_at TIMESTAMP NULL DEFAULT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX ch_push_devices_business_idx (business_id, business_location_id),
    INDEX ch_push_devices_user_idx (user_id),
    INDEX ch_push_devices_status_idx (status),
    INDEX ch_push_devices_group_idx (device_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS communication_hub_push_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    template_name VARCHAR(191) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'general',
    title VARCHAR(191) NOT NULL,
    body TEXT NOT NULL,
    icon_url VARCHAR(1000) NULL,
    image_url VARCHAR(1000) NULL,
    action_url VARCHAR(1000) NULL,
    variables TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX ch_push_templates_business_idx (business_id, business_location_id),
    INDEX ch_push_templates_status_idx (status),
    INDEX ch_push_templates_category_idx (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS communication_hub_push_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    device_id BIGINT UNSIGNED NULL,
    subscription_type VARCHAR(100) NOT NULL DEFAULT 'general',
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX ch_push_subscriptions_business_idx (business_id, business_location_id),
    INDEX ch_push_subscriptions_device_idx (device_id),
    INDEX ch_push_subscriptions_type_idx (subscription_type, is_enabled)
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
CALL ch_add_column_if_missing('communication_hub_messages', 'message_type', 'VARCHAR(30) NULL AFTER `channel`');
CALL ch_add_column_if_missing('communication_hub_messages', 'subject', 'VARCHAR(191) NULL AFTER `recipient`');
CALL ch_add_column_if_missing('communication_hub_messages', 'title', 'VARCHAR(191) NULL AFTER `subject`');
CALL ch_add_column_if_missing('communication_hub_messages', 'action_url', 'VARCHAR(1000) NULL AFTER `caption`');
CALL ch_add_column_if_missing('communication_hub_messages', 'icon_url', 'VARCHAR(1000) NULL AFTER `action_url`');
CALL ch_add_column_if_missing('communication_hub_messages', 'image_url', 'VARCHAR(1000) NULL AFTER `icon_url`');
CALL ch_add_column_if_missing('communication_hub_messages', 'opened_at', 'TIMESTAMP NULL DEFAULT NULL AFTER `read_at`');

CALL ch_add_column_if_missing('communication_hub_delivery_events', 'event_type', 'VARCHAR(50) NULL AFTER `event`');
CALL ch_add_column_if_missing('communication_hub_delivery_events', 'business_id', 'BIGINT UNSIGNED NULL AFTER `id`');
CALL ch_add_column_if_missing('communication_hub_delivery_events', 'business_location_id', 'BIGINT UNSIGNED NULL AFTER `business_id`');

CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_push_business_idx', '(`business_id`, `business_location_id`, `channel`, `status`)');
CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_push_schedule_idx', '(`channel`, `status`, `scheduled_at`)');
CALL ch_add_index_if_missing('communication_hub_delivery_events', 'ch_delivery_events_push_idx', '(`business_id`, `event_type`)');

DROP PROCEDURE IF EXISTS ch_add_column_if_missing;
DROP PROCEDURE IF EXISTS ch_add_index_if_missing;

-- Optional default templates for each tenant. Business-specific templates can be added from the UI.
INSERT INTO communication_hub_push_templates (business_id, business_location_id, template_name, category, title, body, action_url, variables, status, created_at, updated_at)
SELECT NULL, NULL, 'Invoice Posted Alert', 'finance', 'Invoice Posted', 'Invoice {invoice_no} has been posted for {customer_name}.', NULL, '{invoice_no},{customer_name}', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM communication_hub_push_templates WHERE template_name = 'Invoice Posted Alert');

INSERT INTO communication_hub_push_templates (business_id, business_location_id, template_name, category, title, body, action_url, variables, status, created_at, updated_at)
SELECT NULL, NULL, 'Approval Required Alert', 'approval', 'Approval Required', '{module_name} requires your approval.', NULL, '{module_name}', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM communication_hub_push_templates WHERE template_name = 'Approval Required Alert');
