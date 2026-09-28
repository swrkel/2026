-- Communication Hub Stage 007: Live Chat and Internal Messaging Platform
-- Tenant DB SQL only. Run on each tenant database. No database name is specified.

CREATE TABLE IF NOT EXISTS `communication_hub_chat_conversations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `contact_type` VARCHAR(50) NULL,
  `contact_id` BIGINT UNSIGNED NULL,
  `contact_name` VARCHAR(191) NOT NULL,
  `contact_mobile` VARCHAR(50) NULL,
  `contact_email` VARCHAR(191) NULL,
  `subject` VARCHAR(191) NOT NULL,
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `source_channel` VARCHAR(50) NOT NULL DEFAULT 'manual',
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `assigned_to` BIGINT UNSIGNED NULL,
  `last_message_at` TIMESTAMP NULL,
  `closed_at` TIMESTAMP NULL,
  `closed_by` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `ch_chat_conv_business_idx` (`business_id`, `business_location_id`),
  KEY `ch_chat_conv_status_idx` (`status`, `priority`),
  KEY `ch_chat_conv_contact_idx` (`contact_type`, `contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `communication_hub_chat_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `conversation_id` BIGINT UNSIGNED NOT NULL,
  `sender_type` VARCHAR(30) NOT NULL DEFAULT 'agent',
  `sender_user_id` BIGINT UNSIGNED NULL,
  `message_type` VARCHAR(30) NOT NULL DEFAULT 'text',
  `message_body` TEXT NOT NULL,
  `attachment_url` VARCHAR(1000) NULL,
  `direction` VARCHAR(30) NOT NULL DEFAULT 'outbound',
  `status` VARCHAR(30) NOT NULL DEFAULT 'sent',
  `read_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `ch_chat_msg_business_idx` (`business_id`, `business_location_id`),
  KEY `ch_chat_msg_conversation_idx` (`conversation_id`),
  KEY `ch_chat_msg_status_idx` (`status`, `direction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `communication_hub_internal_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `sender_user_id` BIGINT UNSIGNED NULL,
  `recipient_user_id` BIGINT UNSIGNED NULL,
  `recipient_role` VARCHAR(100) NULL,
  `recipient_group` VARCHAR(100) NULL,
  `subject` VARCHAR(191) NOT NULL,
  `message_body` TEXT NOT NULL,
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `action_url` VARCHAR(1000) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'unread',
  `read_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `ch_internal_business_idx` (`business_id`, `business_location_id`),
  KEY `ch_internal_recipient_idx` (`recipient_user_id`, `recipient_role`, `recipient_group`),
  KEY `ch_internal_status_idx` (`status`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `communication_hub_chat_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `template_name` VARCHAR(191) NOT NULL,
  `template_type` VARCHAR(50) NOT NULL DEFAULT 'chat',
  `category` VARCHAR(50) NULL,
  `subject` VARCHAR(191) NULL,
  `body` TEXT NOT NULL,
  `variables` VARCHAR(1000) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `ch_chat_tpl_business_idx` (`business_id`, `business_location_id`),
  KEY `ch_chat_tpl_type_idx` (`template_type`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
