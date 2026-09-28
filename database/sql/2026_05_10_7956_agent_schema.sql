-- 7956 Agent schema update script
-- Safe/idempotent SQL for MySQL 8+ style deployments.
-- Mirrors: database/migrations/2026_05_10_090000_enhance_agents_table_for_7956_agent.php

START TRANSACTION;

-- Add missing columns
ALTER TABLE `agents`
    ADD COLUMN IF NOT EXISTS `agent_code` VARCHAR(255) NULL AFTER `date`,
    ADD COLUMN IF NOT EXISTS `country_id` INT NULL AFTER `address`,
    ADD COLUMN IF NOT EXISTS `district_id` INT UNSIGNED NULL AFTER `country_id`,
    ADD COLUMN IF NOT EXISTS `city` VARCHAR(255) NULL AFTER `district_id`,
    ADD COLUMN IF NOT EXISTS `mobile_no_2` VARCHAR(255) NULL AFTER `mobile_number`,
    ADD COLUMN IF NOT EXISTS `mobile_no_3` VARCHAR(255) NULL AFTER `mobile_no_2`,
    ADD COLUMN IF NOT EXISTS `added_by` INT UNSIGNED NULL DEFAULT 0 AFTER `branch`;

-- Add indexes used by 7956 Agent
CREATE INDEX IF NOT EXISTS `agents_country_id_idx` ON `agents` (`country_id`);
CREATE INDEX IF NOT EXISTS `agents_district_id_idx` ON `agents` (`district_id`);
CREATE INDEX IF NOT EXISTS `agents_added_by_idx` ON `agents` (`added_by`);
CREATE INDEX IF NOT EXISTS `agents_city_idx` ON `agents` (`city`);
CREATE INDEX IF NOT EXISTS `agents_referral_code_idx` ON `agents` (`referral_code`);

-- Add unique key for generated agent code
CREATE UNIQUE INDEX IF NOT EXISTS `agents_agent_code_unique` ON `agents` (`agent_code`);

COMMIT;
