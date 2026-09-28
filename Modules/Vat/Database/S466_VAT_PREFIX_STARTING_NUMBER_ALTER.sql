-- S466 - VAT Module Prefixes
-- Run this in each tenant database if migrations are not being used.
-- VARCHAR is required to retain configured leading zeros such as 00025 and 0001.

ALTER TABLE `vat_prefixes`
    MODIFY `starting_no` VARCHAR(50) NOT NULL;

ALTER TABLE `vat_invoice2_prefixes`
    MODIFY `starting_no` VARCHAR(50) NOT NULL;

ALTER TABLE `vat_statement_prefixes`
    MODIFY `starting_no` VARCHAR(50) NOT NULL;
