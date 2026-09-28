-- Task 8051 - optional manual SQL alternative to the Laravel migration.
-- Run against the CENTRAL database only if migrations are not being used.

ALTER TABLE `banner_idle_settings`
  ADD COLUMN `idle_seconds` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `idle_minutes`;

UPDATE `banner_idle_settings`
SET `idle_seconds` = `idle_minutes` * 60
WHERE `idle_seconds` = 0 AND `idle_minutes` > 0;
