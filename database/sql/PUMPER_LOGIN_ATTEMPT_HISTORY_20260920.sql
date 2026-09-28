-- Pumper Dashboard login block/unblock history.
-- Run this in each tenant database if migrations are not used.

CREATE TABLE IF NOT EXISTS `pumper_login_attempt_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pumper_login_attempt_id` INT UNSIGNED NULL,
  `business_id` INT UNSIGNED NULL,
  `pump_operator_id` INT UNSIGNED NULL,
  `operator_user_id` INT UNSIGNED NULL,
  `operator_name` VARCHAR(255) NULL,
  `company_number` VARCHAR(255) NULL,
  `ip_address` VARCHAR(255) NULL,
  `passcode_mask` VARCHAR(32) NULL,
  `attempt_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `blocked_at` DATETIME NULL,
  `unblocked_at` DATETIME NULL,
  `unblocked_by_user_id` INT UNSIGNED NULL,
  `unblocked_by_name` VARCHAR(255) NULL,
  `source_module` VARCHAR(64) NULL,
  `notes` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plah_attempt_idx` (`pumper_login_attempt_id`),
  KEY `plah_business_idx` (`business_id`),
  KEY `plah_operator_idx` (`pump_operator_id`),
  KEY `plah_blocked_at_idx` (`blocked_at`),
  KEY `plah_unblocked_at_idx` (`unblocked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pumper_login_attempt_histories`
  (`pumper_login_attempt_id`, `business_id`, `company_number`, `ip_address`,
   `passcode_mask`, `attempt_count`, `blocked_at`, `source_module`, `notes`,
   `created_at`, `updated_at`)
SELECT
  pla.`id`, pla.`business_id`, pla.`company_number`, pla.`ip_address`,
  CASE
    WHEN pla.`last_entered_passcode` IS NULL OR pla.`last_entered_passcode` = '' THEN NULL
    ELSE CONCAT(
      REPEAT('*', GREATEST(2, CHAR_LENGTH(pla.`last_entered_passcode`) - 2)),
      RIGHT(pla.`last_entered_passcode`, 2)
    )
  END,
  COALESCE(pla.`attempt_count`, 0),
  COALESCE(pla.`updated_at`, pla.`created_at`),
  'Legacy Import',
  'Existing blocked record imported when login history was enabled.',
  NOW(), NOW()
FROM `pumper_login_attempts` pla
WHERE pla.`status` = 'Blocked'
  AND NOT EXISTS (
    SELECT 1
    FROM `pumper_login_attempt_histories` h
    WHERE h.`pumper_login_attempt_id` = pla.`id`
      AND h.`unblocked_at` IS NULL
  );
