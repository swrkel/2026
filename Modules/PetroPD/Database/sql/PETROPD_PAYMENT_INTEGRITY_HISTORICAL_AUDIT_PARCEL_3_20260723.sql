-- PETROPD PAYMENT INTEGRITY - PARCEL 3 READ-ONLY HISTORICAL AUDIT
-- This file changes NO data.

-- 1. Authoritative payments without Shift ID.
SELECT id, business_id, pump_operator_id, payment_type, payment_amount,
       collection_form_no, settlement_no, shift_id, created_at
FROM pump_operator_payments
WHERE LOWER(payment_type) IN ('cash','card','cards','cheque','cheques','credit','multiple_credit','other','shortage','excess')
  AND (shift_id IS NULL OR shift_id = 0)
ORDER BY business_id, pump_operator_id, id;

-- 2. Duplicate authoritative source identities.
SELECT business_id, source_type, source_id, COUNT(*) AS payment_count,
       GROUP_CONCAT(id ORDER BY id) AS payment_ids
FROM pump_operator_payments
WHERE source_type IS NOT NULL AND source_type <> ''
  AND source_id IS NOT NULL AND source_id > 0
GROUP BY business_id, source_type, source_id
HAVING COUNT(*) > 1;

-- 3. Supporting rows linked to a missing master.
SELECT 'settlement_cash_payments' AS table_name, d.id, d.business_id, d.pump_payment_id, d.shift_id
FROM settlement_cash_payments d LEFT JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0 AND p.id IS NULL
UNION ALL
SELECT 'settlement_card_payments', d.id, d.business_id, d.pump_payment_id, d.shift_id
FROM settlement_card_payments d LEFT JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0 AND p.id IS NULL
UNION ALL
SELECT 'settlement_cheque_payments', d.id, d.business_id, d.pump_payment_id, d.shift_id
FROM settlement_cheque_payments d LEFT JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0 AND p.id IS NULL
UNION ALL
SELECT 'settlement_credit_sale_payments', d.id, d.business_id, d.pump_payment_id, d.shift_id
FROM settlement_credit_sale_payments d LEFT JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0 AND p.id IS NULL
UNION ALL
SELECT 'settlement_shortage_payments', d.id, d.business_id, d.pump_payment_id, d.shift_id
FROM settlement_shortage_payments d LEFT JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0 AND p.id IS NULL
UNION ALL
SELECT 'settlement_excess_payments', d.id, d.business_id, d.pump_payment_id, d.shift_id
FROM settlement_excess_payments d LEFT JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0 AND p.id IS NULL;

-- 4. Linked rows whose business/operator/Shift differs from the master.
SELECT 'settlement_cash_payments' AS table_name, d.id, d.pump_payment_id,
       d.business_id AS detail_business, p.business_id AS master_business,
       d.pump_operator_id AS detail_operator, p.pump_operator_id AS master_operator,
       d.shift_id AS detail_shift, p.shift_id AS master_shift
FROM settlement_cash_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE NOT (d.business_id <=> p.business_id)
   OR NOT (d.pump_operator_id <=> p.pump_operator_id)
   OR NOT (d.shift_id <=> p.shift_id)
UNION ALL
SELECT 'settlement_card_payments', d.id, d.pump_payment_id,
       d.business_id, p.business_id, d.pump_operator_id, p.pump_operator_id, d.shift_id, p.shift_id
FROM settlement_card_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE NOT (d.business_id <=> p.business_id)
   OR NOT (d.pump_operator_id <=> p.pump_operator_id)
   OR NOT (d.shift_id <=> p.shift_id)
UNION ALL
SELECT 'settlement_cheque_payments', d.id, d.pump_payment_id,
       d.business_id, p.business_id, d.pump_operator_id, p.pump_operator_id, d.shift_id, p.shift_id
FROM settlement_cheque_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE NOT (d.business_id <=> p.business_id)
   OR NOT (d.pump_operator_id <=> p.pump_operator_id)
   OR NOT (d.shift_id <=> p.shift_id)
UNION ALL
SELECT 'settlement_credit_sale_payments', d.id, d.pump_payment_id,
       d.business_id, p.business_id, d.pump_operator_id, p.pump_operator_id, d.shift_id, p.shift_id
FROM settlement_credit_sale_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE NOT (d.business_id <=> p.business_id)
   OR NOT (d.pump_operator_id <=> p.pump_operator_id)
   OR NOT (d.shift_id <=> p.shift_id)
UNION ALL
SELECT 'settlement_shortage_payments', d.id, d.pump_payment_id,
       d.business_id, p.business_id, d.pump_operator_id, p.pump_operator_id, d.shift_id, p.shift_id
FROM settlement_shortage_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE NOT (d.business_id <=> p.business_id)
   OR NOT (d.pump_operator_id <=> p.pump_operator_id)
   OR NOT (d.shift_id <=> p.shift_id)
UNION ALL
SELECT 'settlement_excess_payments', d.id, d.pump_payment_id,
       d.business_id, p.business_id, d.pump_operator_id, p.pump_operator_id, d.shift_id, p.shift_id
FROM settlement_excess_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE NOT (d.business_id <=> p.business_id)
   OR NOT (d.pump_operator_id <=> p.pump_operator_id)
   OR NOT (d.shift_id <=> p.shift_id);

-- 5. More than one supporting header linked to the same master payment.
SELECT 'settlement_cash_payments' AS table_name, business_id, pump_payment_id,
       COUNT(*) AS row_count, GROUP_CONCAT(id ORDER BY id) AS row_ids
FROM settlement_cash_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id>0
GROUP BY business_id,pump_payment_id HAVING COUNT(*)>1
UNION ALL
SELECT 'settlement_card_payments', business_id, pump_payment_id, COUNT(*), GROUP_CONCAT(id ORDER BY id)
FROM settlement_card_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id>0
GROUP BY business_id,pump_payment_id HAVING COUNT(*)>1
UNION ALL
SELECT 'settlement_cheque_payments', business_id, pump_payment_id, COUNT(*), GROUP_CONCAT(id ORDER BY id)
FROM settlement_cheque_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id>0
GROUP BY business_id,pump_payment_id HAVING COUNT(*)>1
UNION ALL
SELECT 'settlement_credit_sale_payments', business_id, pump_payment_id, COUNT(*), GROUP_CONCAT(id ORDER BY id)
FROM settlement_credit_sale_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id>0
GROUP BY business_id,pump_payment_id HAVING COUNT(*)>1
UNION ALL
SELECT 'settlement_shortage_payments', business_id, pump_payment_id, COUNT(*), GROUP_CONCAT(id ORDER BY id)
FROM settlement_shortage_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id>0
GROUP BY business_id,pump_payment_id HAVING COUNT(*)>1
UNION ALL
SELECT 'settlement_excess_payments', business_id, pump_payment_id, COUNT(*), GROUP_CONCAT(id ORDER BY id)
FROM settlement_excess_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id>0
GROUP BY business_id,pump_payment_id HAVING COUNT(*)>1;

-- 6. Unlinked operational rows. These are not automatically guessed by this audit.
SELECT 'daily_collections' AS table_name, id, business_id, pump_operator_id, shift_id, collection_form_no
FROM daily_collections WHERE pump_payment_id IS NULL OR pump_payment_id=0
UNION ALL
SELECT 'daily_cards', id, business_id, pump_operator_id, shift_id, collection_no
FROM daily_cards WHERE pump_payment_id IS NULL OR pump_payment_id=0
UNION ALL
SELECT 'daily_cheque_payments', id, business_id, pump_operator_id, shift_id, collection_form_no
FROM daily_cheque_payments WHERE pump_payment_id IS NULL OR pump_payment_id=0
UNION ALL
SELECT 'daily_vouchers', id, business_id, operator_id, shift_id, daily_vouchers_no
FROM daily_vouchers WHERE pump_payment_id IS NULL OR pump_payment_id=0;

-- 7. One-to-one settlement detail amounts that disagree with the authoritative master.
-- Amounts are never auto-repaired by Parcel 3.
SELECT 'settlement_cash_payments' AS table_name, d.id, d.business_id, d.pump_payment_id,
       d.amount AS detail_net,
       COALESCE(p.net_amount,p.payment_amount) AS master_net,
       ROUND(d.amount-COALESCE(p.net_amount,p.payment_amount),4) AS difference
FROM settlement_cash_payments d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.amount,0)-COALESCE(p.net_amount,p.payment_amount,0))>0.01
UNION ALL
SELECT 'settlement_card_payments', d.id, d.business_id, d.pump_payment_id,
       d.amount, COALESCE(p.net_amount,p.payment_amount),
       ROUND(d.amount-COALESCE(p.net_amount,p.payment_amount),4)
FROM settlement_card_payments d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.amount,0)-COALESCE(p.net_amount,p.payment_amount,0))>0.01
UNION ALL
SELECT 'settlement_cheque_payments', d.id, d.business_id, d.pump_payment_id,
       d.amount, COALESCE(p.net_amount,p.payment_amount),
       ROUND(d.amount-COALESCE(p.net_amount,p.payment_amount),4)
FROM settlement_cheque_payments d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.amount,0)-COALESCE(p.net_amount,p.payment_amount,0))>0.01
UNION ALL
SELECT 'settlement_shortage_payments', d.id, d.business_id, d.pump_payment_id,
       d.amount, COALESCE(p.net_amount,p.payment_amount),
       ROUND(d.amount-COALESCE(p.net_amount,p.payment_amount),4)
FROM settlement_shortage_payments d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.amount,0)-COALESCE(p.net_amount,p.payment_amount,0))>0.01
UNION ALL
SELECT 'settlement_excess_payments', d.id, d.business_id, d.pump_payment_id,
       d.amount, COALESCE(p.net_amount,p.payment_amount),
       ROUND(d.amount-COALESCE(p.net_amount,p.payment_amount),4)
FROM settlement_excess_payments d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.amount,0)-COALESCE(p.net_amount,p.payment_amount,0))>0.01;

-- 8. Credit-sale gross, discount, or net amounts that disagree with master.
SELECT d.id, d.business_id, d.pump_operator_id, d.shift_id, d.pump_payment_id,
       d.amount AS detail_gross,
       d.total_discount AS detail_discount,
       d.sub_total AS detail_net,
       COALESCE(p.gross_amount,p.payment_amount) AS master_gross,
       COALESCE(p.discount_amount,0) AS master_discount,
       COALESCE(p.net_amount,COALESCE(p.gross_amount,p.payment_amount)-COALESCE(p.discount_amount,0)) AS master_net
FROM settlement_credit_sale_payments d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.amount,0)-COALESCE(p.gross_amount,p.payment_amount,0))>0.01
   OR ABS(COALESCE(d.total_discount,0)-COALESCE(p.discount_amount,0))>0.01
   OR ABS(COALESCE(d.sub_total,0)-COALESCE(p.net_amount,COALESCE(p.gross_amount,p.payment_amount)-COALESCE(p.discount_amount,0),0))>0.01
ORDER BY d.business_id,d.shift_id,d.pump_payment_id,d.id;
