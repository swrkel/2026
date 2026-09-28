-- 8051 - Idle Banner image display size.
-- Run against the CENTRAL database only if Laravel migrations are not being used.
-- Preferred deployment: php artisan migrate --force

ALTER TABLE `banner_idle_settings`
    ADD COLUMN `image_size_percent` TINYINT UNSIGNED NOT NULL DEFAULT 90 AFTER `idle_seconds`;

UPDATE `banner_idle_settings`
SET `image_size_percent` = 90
WHERE `image_size_percent` < 25 OR `image_size_percent` > 100;
