-- IS1992 - Finance module - restore the journal ledger-link columns
--
-- Symptom: List Journals showed
--     DataTables warning: table id=journal_table - Exception Message:
--     SQLSTATE[42S22]: Column not found: 1054 Unknown column
--     'journals.show_in_ledger'
--
-- `journals` is a CORE table and the Finance module ships no migration for it
-- (Database/Migrations is empty and the service provider does not call
-- loadMigrationsFrom). The ledger-link columns were therefore never created on
-- some tenants, while the module's code assumes them.
--
-- JournalController now guards every read and write with
-- Schema::getColumnListing('journals'), so the module WORKS without this
-- script - the list loads and journals save, just with no ledger link. Run this
-- to restore the feature itself: linking a journal entry to a customer,
-- supplier or pump operator ledger.
--
-- RUN THIS ONCE PER TENANT DATABASE (nivasa_lashini, nivasa_ishadi, ...).
-- Confirmed missing on at least lashini and ishadi.
--
--
-- HOW TO RUN
--
-- Step 1 - see which columns this database already has:
--
--     SHOW COLUMNS FROM `journals`;
--
-- Step 2 - run ONLY the ALTER statements below for columns that did not
-- appear. Running one that already exists is harmless: MySQL replies
-- "#1060 - Duplicate column name" and changes nothing. Nothing is dropped or
-- overwritten either way.
--
-- No stored procedure, no information_schema, no CREATE ROUTINE privilege -
-- the earlier version of this script used all three and failed on cPanel
-- hosting with "#1044 Access denied ... to database 'information_schema'".


-- Which ledger the entry appears in. 'no' matches the controller's own default
-- and its validation rule (in:no,customer,supplier,pump_operator), so existing
-- rows keep the meaning they have today: not linked to any ledger.
ALTER TABLE `journals` ADD COLUMN `show_in_ledger` VARCHAR(20) NULL DEFAULT 'no';

-- Free-text label shown beside the ledger entry. Null whenever show_in_ledger
-- is 'no'.
ALTER TABLE `journals` ADD COLUMN `show_in` VARCHAR(255) NULL DEFAULT NULL;

-- The ledger holder. Exactly one of these is set, chosen by show_in_ledger.
-- Deliberately NOT foreign keys: contacts and pump operators belong to other
-- modules, and a constraint here would fail on a tenant without them.
ALTER TABLE `journals` ADD COLUMN `customer_id` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `journals` ADD COLUMN `supplier_id` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `journals` ADD COLUMN `pump_operator` INT UNSIGNED NULL DEFAULT NULL;


-- Step 3 - verify. All five columns should be listed.
--
--     SHOW COLUMNS FROM `journals`;
