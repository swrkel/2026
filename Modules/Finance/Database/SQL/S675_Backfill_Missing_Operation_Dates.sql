-- S675 - Finance - backfill missing Date & Time on deposits and transfers
--
-- Symptom: on List Deposits & Transfers, the Date & Time column is blank for
-- deposits made from the List Account page.
--
--
-- WHY A SCRIPT IS NEEDED AS WELL AS THE CODE FIX
--
-- account_transactions.operation_date was saved NULL because
-- commonUtil->uf_date() returns null - it does not throw - when the submitted
-- date does not match the business date format. The null went into the insert
-- and nothing downstream noticed.
--
-- The accompanying module update stops that happening again: all three deposit
-- paths now use resolveOperationDate(), which tries the business format first,
-- then common formats, then falls back to now().
--
-- But that only affects rows saved FROM NOW ON. The two deposits in the report
-- were saved before the fix, so they still hold NULL and their Date & Time
-- column stays blank however many times the page is reloaded. This script
-- repairs those existing rows.
--
--
-- WHAT IT DOES
--
-- Sets operation_date from created_at for rows that have no operation_date.
-- created_at is when the transaction was recorded, which is the closest true
-- value available - the original entered date was never stored, so it cannot be
-- recovered.
--
-- Only rows where operation_date IS NULL or zero are touched. A row with a real
-- date is never modified.
--
-- Safe to run repeatedly: after the first run there is nothing left to match.
--
-- RUN ONCE PER TENANT DATABASE.
--
--
-- HOW TO RUN
--
-- Step 1 - see how many rows are affected, before changing anything:
--
--     SELECT COUNT(*) AS blank_dates
--     FROM `account_transactions`
--     WHERE (`operation_date` IS NULL OR `operation_date` = '0000-00-00 00:00:00');
--
-- Step 2 - if that count looks right, run the UPDATE below.
--
-- Step 3 - confirm it is now zero by re-running the SELECT from step 1.


UPDATE `account_transactions`
SET `operation_date` = `created_at`
WHERE (`operation_date` IS NULL OR `operation_date` = '0000-00-00 00:00:00')
  AND `created_at` IS NOT NULL
  AND `created_at` <> '0000-00-00 00:00:00';


-- The contact ledger carries the same column and can be affected the same way.
-- Run this only if List Deposits & Transfers still shows blanks after the above.
--
-- UPDATE `contact_ledgers`
-- SET `operation_date` = `created_at`
-- WHERE (`operation_date` IS NULL OR `operation_date` = '0000-00-00 00:00:00')
--   AND `created_at` IS NOT NULL
--   AND `created_at` <> '0000-00-00 00:00:00';
