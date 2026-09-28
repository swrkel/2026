-- OPTIONAL compatibility update.
-- The new Bank Reconciliation works from its own tables even without this column,
-- but this column keeps the existing Finance Account Book Reconcile/Un-Reconcile
-- indicator synchronized with finalized Bank Reconciliations.
--
-- First run:
--   SHOW COLUMNS FROM `account_transactions` LIKE 'reconcile_status';
-- If that returns NO rows, then run the ALTER below. If it returns a row, do NOT run it.

ALTER TABLE `account_transactions`
  ADD COLUMN `reconcile_status` TINYINT(1) NOT NULL DEFAULT 0,
  ADD INDEX `account_transactions_reconcile_status_idx` (`reconcile_status`);
