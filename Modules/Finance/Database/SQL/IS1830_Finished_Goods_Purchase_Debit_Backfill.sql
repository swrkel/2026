-- IS1830 – Finance module – Finished Goods purchase debit backfill
-- Safe to run repeatedly in each tenant database.
-- It corrects only existing active purchase postings already linked to the
-- Finished Goods account; it does not create, delete, or duplicate entries.

UPDATE account_transactions AS atx
INNER JOIN transactions AS txn
    ON txn.id = atx.transaction_id
INNER JOIN accounts AS acc
    ON acc.id = atx.account_id
SET
    atx.type = 'debit',
    atx.updated_at = CURRENT_TIMESTAMP
WHERE txn.type = 'purchase'
  AND txn.business_id = atx.business_id
  AND acc.business_id = atx.business_id
  AND LOWER(TRIM(acc.name)) IN ('finished goods account', 'finished goods accounting')
  AND atx.type <> 'debit'
  AND atx.deleted_at IS NULL
  AND txn.deleted_at IS NULL
  AND acc.deleted_at IS NULL;
