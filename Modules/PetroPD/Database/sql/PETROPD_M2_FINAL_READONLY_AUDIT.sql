/* PETROPD M2 FINAL READONLY AUDIT - safe SELECT checks only */

/* Duplicate pump operator payments by business/operator/shift/type/amount/date where columns exist. */
SELECT business_id, pump_operator_id, shift_id, payment_type, amount, DATE(created_at) AS payment_date, COUNT(*) AS duplicate_count
FROM pump_operator_payments
GROUP BY business_id, pump_operator_id, shift_id, payment_type, amount, DATE(created_at)
HAVING COUNT(*) > 1
ORDER BY duplicate_count DESC, payment_date DESC;

/* Duplicate settlement numbers. */
SELECT business_id, settlement_no, COUNT(*) AS duplicate_count
FROM settlements
GROUP BY business_id, settlement_no
HAVING COUNT(*) > 1
ORDER BY duplicate_count DESC;

/* Account transactions with same ref/account/type/amount. */
SELECT business_id, ref_no, account_id, type, amount, COUNT(*) AS duplicate_count
FROM account_transactions
WHERE ref_no IS NOT NULL AND ref_no <> ''
GROUP BY business_id, ref_no, account_id, type, amount
HAVING COUNT(*) > 1
ORDER BY duplicate_count DESC;
