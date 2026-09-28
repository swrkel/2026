-- S365 - POS Customers Module Integration
-- No compulsory schema changes.
-- POS uses existing Customers module customer master/ledger tables.

-- Optional indexes for faster POS customer search when not already present.
ALTER TABLE `contacts` ADD INDEX `idx_pos_customer_search_business_type_active` (`business_id`, `type`, `active`);
ALTER TABLE `contact_ledgers` ADD INDEX `idx_pos_customer_ledger_business_contact` (`business_id`, `contact_id`);
