-- POS Standalone S367 - Register, Shift and Cash Operations
-- Run after selecting the tenant database. No database name is hardcoded.

ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `approval_status` VARCHAR(40) NOT NULL DEFAULT 'approved' AFTER `variance_amount`;
ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `approved_by` BIGINT UNSIGNED NULL AFTER `closed_by`;
ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL AFTER `approved_by`;
ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `closing_note` TEXT NULL;
ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `business_location_id` BIGINT UNSIGNED NULL;
ALTER TABLE `pos_register_sessions` ADD INDEX IF NOT EXISTS `pos_register_sessions_status_idx` (`status`);
ALTER TABLE `pos_register_sessions` ADD INDEX IF NOT EXISTS `pos_register_sessions_register_status_idx` (`register_id`,`status`);

ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `approval_status` VARCHAR(40) NOT NULL DEFAULT 'approved' AFTER `amount`;
ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `approved_by` BIGINT UNSIGNED NULL AFTER `created_by`;
ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL AFTER `approved_by`;
ALTER TABLE `pos_cash_movements` ADD INDEX IF NOT EXISTS `pos_cash_movements_approval_idx` (`approval_status`);
ALTER TABLE `pos_cash_movements` ADD INDEX IF NOT EXISTS `pos_cash_movements_session_type_idx` (`register_session_id`,`movement_type`);

CREATE TABLE IF NOT EXISTS `pos_shift_handover_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `register_session_id` BIGINT UNSIGNED NOT NULL,
  `from_user_id` BIGINT UNSIGNED NULL,
  `to_user_id` BIGINT UNSIGNED NULL,
  `handover_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_shift_handover_session_idx` (`register_session_id`),
  KEY `pos_shift_handover_business_idx` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
