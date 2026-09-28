-- S518 VAT Invoice2 decimal settings
-- Run in each tenant database only when Laravel migrations are not being used.

ALTER TABLE `vat_invoice2_prefixes`
    ADD COLUMN IF NOT EXISTS `sub_total_no_of_decimals` INT UNSIGNED NULL AFTER `unit_vat_rounding_off_required`,
    ADD COLUMN IF NOT EXISTS `sub_total_rounding_off_required` TINYINT(1) NOT NULL DEFAULT 0 AFTER `sub_total_no_of_decimals`;
