/*
 PetroPD Pumper Payment Integrity Audit - 23 July 2026
 READ-ONLY: this file does not update or delete financial records.
 Run after the PetroPD payment-integrity migrations on each tenant database.
 Every exception result set should return zero rows. A09 is the control summary.
*/

/* A01 - Authoritative payments without a permanent Shift ID */
SELECT id, business_id, pump_operator_id, payment_type, payment_amount,
       collection_form_no, settlement_no, created_at
FROM pump_operator_payments
WHERE pump_operator_id IS NOT NULL
  AND pump_operator_id > 0
  AND LOWER(payment_type) IN
      ('cash','card','cards','cheque','cheques','credit','multiple_credit','other','shortage','excess')
  AND (shift_id IS NULL OR shift_id = 0)
ORDER BY business_id, pump_operator_id, id;

/* A02 - One source transaction linked to more than one master payment */
SELECT business_id, source_type, source_id,
       COUNT(*) AS master_count,
       GROUP_CONCAT(id ORDER BY id) AS pump_payment_ids
FROM pump_operator_payments
WHERE source_type IS NOT NULL AND source_type <> ''
  AND source_id IS NOT NULL AND source_id > 0
GROUP BY business_id, source_type, source_id
HAVING COUNT(*) > 1;

/* A03 - Credit master values that are incomplete */
SELECT id, business_id, pump_operator_id, shift_id, collection_form_no,
       payment_amount, gross_amount, discount_amount, net_amount
FROM pump_operator_payments
WHERE LOWER(payment_type) IN ('credit','multiple_credit')
  AND (gross_amount IS NULL OR discount_amount IS NULL OR net_amount IS NULL)
ORDER BY business_id, pump_operator_id, shift_id, id;

/* A04 - Duplicate detail links. One master payment may have one header only. */
SELECT 'settlement_cash_payments' AS detail_table, business_id, pump_payment_id,
       COUNT(*) AS detail_count, GROUP_CONCAT(id ORDER BY id) AS detail_ids
FROM settlement_cash_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'settlement_card_payments', business_id, pump_payment_id,
       COUNT(*), GROUP_CONCAT(id ORDER BY id)
FROM settlement_card_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'settlement_cheque_payments', business_id, pump_payment_id,
       COUNT(*), GROUP_CONCAT(id ORDER BY id)
FROM settlement_cheque_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'settlement_credit_sale_payments', business_id, pump_payment_id,
       COUNT(*), GROUP_CONCAT(id ORDER BY id)
FROM settlement_credit_sale_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'settlement_shortage_payments', business_id, pump_payment_id,
       COUNT(*), GROUP_CONCAT(id ORDER BY id)
FROM settlement_shortage_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'settlement_excess_payments', business_id, pump_payment_id,
       COUNT(*), GROUP_CONCAT(id ORDER BY id)
FROM settlement_excess_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'daily_collections', business_id, pump_payment_id,
       COUNT(*), GROUP_CONCAT(id ORDER BY id)
FROM daily_collections WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'daily_cards', business_id, pump_payment_id,
       COUNT(*), GROUP_CONCAT(id ORDER BY id)
FROM daily_cards WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'daily_cheque_payments', business_id, pump_payment_id,
       COUNT(*), GROUP_CONCAT(id ORDER BY id)
FROM daily_cheque_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'daily_vouchers', business_id, pump_payment_id,
       COUNT(*), GROUP_CONCAT(id ORDER BY id)
FROM daily_vouchers WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1;

/* A05 - Detail/master business or Shift mismatch */
SELECT 'settlement_cash_payments' AS detail_table, d.id AS detail_id,
       d.pump_payment_id, d.business_id AS detail_business_id,
       p.business_id AS master_business_id, d.shift_id AS detail_shift_id,
       p.shift_id AS master_shift_id
FROM settlement_cash_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.business_id<>p.business_id OR COALESCE(d.shift_id,0)<>COALESCE(p.shift_id,0)
UNION ALL
SELECT 'settlement_card_payments', d.id, d.pump_payment_id, d.business_id,
       p.business_id, d.shift_id, p.shift_id
FROM settlement_card_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.business_id<>p.business_id OR COALESCE(d.shift_id,0)<>COALESCE(p.shift_id,0)
UNION ALL
SELECT 'settlement_cheque_payments', d.id, d.pump_payment_id, d.business_id,
       p.business_id, d.shift_id, p.shift_id
FROM settlement_cheque_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.business_id<>p.business_id OR COALESCE(d.shift_id,0)<>COALESCE(p.shift_id,0)
UNION ALL
SELECT 'settlement_credit_sale_payments', d.id, d.pump_payment_id, d.business_id,
       p.business_id, d.shift_id, p.shift_id
FROM settlement_credit_sale_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.business_id<>p.business_id OR COALESCE(d.shift_id,0)<>COALESCE(p.shift_id,0)
UNION ALL
SELECT 'settlement_shortage_payments', d.id, d.pump_payment_id, d.business_id,
       p.business_id, d.shift_id, p.shift_id
FROM settlement_shortage_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.business_id<>p.business_id OR COALESCE(d.shift_id,0)<>COALESCE(p.shift_id,0)
UNION ALL
SELECT 'settlement_excess_payments', d.id, d.pump_payment_id, d.business_id,
       p.business_id, d.shift_id, p.shift_id
FROM settlement_excess_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.business_id<>p.business_id OR COALESCE(d.shift_id,0)<>COALESCE(p.shift_id,0)
UNION ALL
SELECT 'daily_collections', d.id, d.pump_payment_id, d.business_id,
       p.business_id, d.shift_id, p.shift_id
FROM daily_collections d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.business_id<>p.business_id OR COALESCE(d.shift_id,0)<>COALESCE(p.shift_id,0)
UNION ALL
SELECT 'daily_cards', d.id, d.pump_payment_id, d.business_id,
       p.business_id, d.shift_id, p.shift_id
FROM daily_cards d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.business_id<>p.business_id OR COALESCE(d.shift_id,0)<>COALESCE(p.shift_id,0)
UNION ALL
SELECT 'daily_cheque_payments', d.id, d.pump_payment_id, d.business_id,
       p.business_id, d.shift_id, p.shift_id
FROM daily_cheque_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.business_id<>p.business_id OR COALESCE(d.shift_id,0)<>COALESCE(p.shift_id,0)
UNION ALL
SELECT 'daily_vouchers', d.id, d.pump_payment_id, d.business_id,
       p.business_id, d.shift_id, p.shift_id
FROM daily_vouchers d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE d.business_id<>p.business_id OR COALESCE(d.shift_id,0)<>COALESCE(p.shift_id,0);

/* A06 - Linked detail amount differs from its authoritative master */
SELECT 'settlement_cash_payments' AS detail_table, d.id AS detail_id, p.id AS pump_payment_id,
       p.net_amount AS master_amount, d.amount AS detail_amount
FROM settlement_cash_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.amount,0)-COALESCE(p.net_amount,p.payment_amount,0)) >= 0.02
UNION ALL
SELECT 'settlement_card_payments', d.id, p.id, p.net_amount, d.amount
FROM settlement_card_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.amount,0)-COALESCE(p.net_amount,p.payment_amount,0)) >= 0.02
UNION ALL
SELECT 'settlement_cheque_payments', d.id, p.id, p.net_amount, d.amount
FROM settlement_cheque_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.amount,0)-COALESCE(p.net_amount,p.payment_amount,0)) >= 0.02
UNION ALL
SELECT 'settlement_credit_sale_payments', d.id, p.id, p.net_amount,
       COALESCE(d.sub_total,d.amount-COALESCE(d.total_discount,0))
FROM settlement_credit_sale_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.sub_total,d.amount-COALESCE(d.total_discount,0),0)
          -COALESCE(p.net_amount,p.payment_amount,0)) >= 0.02
UNION ALL
SELECT 'settlement_shortage_payments', d.id, p.id, p.net_amount, d.amount
FROM settlement_shortage_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.amount,0)-COALESCE(p.net_amount,p.payment_amount,0)) >= 0.02
UNION ALL
SELECT 'settlement_excess_payments', d.id, p.id, p.net_amount, d.amount
FROM settlement_excess_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.amount,0)-COALESCE(p.net_amount,p.payment_amount,0)) >= 0.02
UNION ALL
SELECT 'daily_collections', d.id, p.id, p.net_amount, d.current_amount
FROM daily_collections d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.current_amount,0)-COALESCE(p.net_amount,p.payment_amount,0)) >= 0.02
UNION ALL
SELECT 'daily_cards', d.id, p.id, p.net_amount, d.amount
FROM daily_cards d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.amount,0)-COALESCE(p.net_amount,p.payment_amount,0)) >= 0.02
UNION ALL
SELECT 'daily_cheque_payments', d.id, p.id, p.net_amount, d.amount
FROM daily_cheque_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.amount,0)-COALESCE(p.net_amount,p.payment_amount,0)) >= 0.02
UNION ALL
SELECT 'daily_vouchers', d.id, p.id, p.net_amount, d.total_amount
FROM daily_vouchers d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
WHERE ABS(COALESCE(d.total_amount,0)-COALESCE(p.net_amount,p.payment_amount,0)) >= 0.02;

/* A07 - Operational Pumper Dashboard rows without a master Pump Payment link */
SELECT 'daily_collections' AS operational_table, id, business_id,
       pump_operator_id AS operator_id, shift_id
FROM daily_collections
WHERE (pump_payment_id IS NULL OR pump_payment_id=0)
  AND pump_operator_id IS NOT NULL AND pump_operator_id>0
  AND shift_id IS NOT NULL AND shift_id>0
UNION ALL
SELECT 'daily_cards', id, business_id, pump_operator_id, shift_id
FROM daily_cards
WHERE (pump_payment_id IS NULL OR pump_payment_id=0)
  AND pump_operator_id IS NOT NULL AND pump_operator_id>0
  AND shift_id IS NOT NULL AND shift_id>0
UNION ALL
SELECT 'daily_cheque_payments', id, business_id, pump_operator_id, shift_id
FROM daily_cheque_payments
WHERE (pump_payment_id IS NULL OR pump_payment_id=0)
  AND pump_operator_id IS NOT NULL AND pump_operator_id>0
  AND shift_id IS NOT NULL AND shift_id>0
UNION ALL
SELECT 'daily_vouchers', id, business_id, operator_id, shift_id
FROM daily_vouchers
WHERE (pump_payment_id IS NULL OR pump_payment_id=0)
  AND operator_id IS NOT NULL AND operator_id>0
  AND shift_id IS NOT NULL AND shift_id>0;

/* A08 - One settlement linked to more than one master Shift ID */
SELECT business_id, settlement_no,
       COUNT(DISTINCT shift_id) AS shift_count,
       GROUP_CONCAT(DISTINCT shift_id ORDER BY shift_id) AS shift_ids,
       GROUP_CONCAT(id ORDER BY id) AS pump_payment_ids
FROM pump_operator_payments
WHERE settlement_no IS NOT NULL AND settlement_no<>''
  AND shift_id IS NOT NULL AND shift_id>0
GROUP BY business_id, settlement_no
HAVING COUNT(DISTINCT shift_id)>1;

/* A09 - Shift-level authoritative totals. This is the control total used by PetroPD. */
SELECT business_id, pump_operator_id, shift_id,
       COUNT(DISTINCT id) AS unique_payment_count,
       SUM(CASE WHEN LOWER(payment_type)='cash' THEN COALESCE(net_amount,payment_amount,0) ELSE 0 END) AS cash_total,
       SUM(CASE WHEN LOWER(payment_type) IN ('card','cards') THEN COALESCE(net_amount,payment_amount,0) ELSE 0 END) AS card_total,
       SUM(CASE WHEN LOWER(payment_type) IN ('cheque','cheques') THEN COALESCE(net_amount,payment_amount,0) ELSE 0 END) AS cheque_total,
       SUM(CASE WHEN LOWER(payment_type) IN ('credit','multiple_credit') THEN COALESCE(net_amount,payment_amount,0) ELSE 0 END) AS credit_total,
       SUM(CASE WHEN LOWER(payment_type)='other' THEN COALESCE(net_amount,payment_amount,0) ELSE 0 END) AS other_total,
       SUM(CASE WHEN LOWER(payment_type)='shortage' THEN COALESCE(net_amount,payment_amount,0) ELSE 0 END) AS shortage_total,
       SUM(CASE WHEN LOWER(payment_type)='excess' THEN COALESCE(net_amount,payment_amount,0) ELSE 0 END) AS excess_total,
       SUM(COALESCE(net_amount,payment_amount,0)) AS authoritative_total_paid
FROM pump_operator_payments
WHERE shift_id IS NOT NULL AND shift_id > 0
GROUP BY business_id, pump_operator_id, shift_id
ORDER BY business_id, pump_operator_id, shift_id DESC;

/* A10 - Reconciliation events blocked by the application and not yet resolved */
SELECT id, business_id, pump_operator_id, shift_ids, settlement_id, settlement_no,
       pump_payment_id, issue_type, severity, message, created_at, updated_at
FROM petro_pd_payment_reconciliation_events
WHERE resolved_at IS NULL
ORDER BY id DESC;
