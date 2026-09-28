/*
 PETROPD PAYMENT INTEGRITY - FINAL READ-ONLY VERIFICATION
 Date: 23 July 2026

 Run after the Parcels 1-4 master SQL on EACH tenant database.
 This script performs SELECT statements only. It never inserts, updates or deletes.
 Any non-zero result in a section marked BLOCKING must be investigated before
 finalizing the affected settlement.
*/

SET @db_name := DATABASE();

SELECT 'DATABASE' AS section, @db_name AS value;

/* 1. Required authoritative master columns. BLOCKING when rows are returned. */
SELECT 'MISSING_MASTER_COLUMN' AS issue, required.column_name
FROM (
    SELECT 'gross_amount' column_name UNION ALL
    SELECT 'discount_amount' UNION ALL
    SELECT 'net_amount' UNION ALL
    SELECT 'source_type' UNION ALL
    SELECT 'source_id' UNION ALL
    SELECT 'customer_id' UNION ALL
    SELECT 'transaction_date' UNION ALL
    SELECT 'reference_no' UNION ALL
    SELECT 'shift_id'
) required
LEFT JOIN information_schema.columns c
  ON c.table_schema = @db_name
 AND c.table_name = 'pump_operator_payments'
 AND c.column_name = required.column_name
WHERE c.column_name IS NULL;

/* 2. Required audit tables. BLOCKING when rows are returned. */
SELECT 'MISSING_AUDIT_TABLE' AS issue, required.table_name
FROM (
    SELECT 'petro_pd_payment_reconciliation_events' table_name UNION ALL
    SELECT 'petro_pd_payment_integrity_repair_runs' UNION ALL
    SELECT 'petro_pd_payment_integrity_repair_actions'
) required
LEFT JOIN information_schema.tables t
  ON t.table_schema = @db_name
 AND t.table_name = required.table_name
WHERE t.table_name IS NULL;

/* 3. Database guards. BLOCKING when rows are returned. */
SELECT 'MISSING_TRIGGER' AS issue, required.trigger_name
FROM (
    SELECT 'trg_pop_authority_bi_20260723' trigger_name UNION ALL
    SELECT 'trg_pop_authority_bu_20260723' UNION ALL
    SELECT 'trg_scp_authority_bi_20260723' UNION ALL
    SELECT 'trg_scp_authority_bu_20260723' UNION ALL
    SELECT 'trg_scardp_authority_bi_20260723' UNION ALL
    SELECT 'trg_scardp_authority_bu_20260723' UNION ALL
    SELECT 'trg_schqp_authority_bi_20260723' UNION ALL
    SELECT 'trg_schqp_authority_bu_20260723' UNION ALL
    SELECT 'trg_scrp_authority_bi_20260723' UNION ALL
    SELECT 'trg_scrp_authority_bu_20260723' UNION ALL
    SELECT 'trg_sshp_authority_bi_20260723' UNION ALL
    SELECT 'trg_sshp_authority_bu_20260723' UNION ALL
    SELECT 'trg_sexp_authority_bi_20260723' UNION ALL
    SELECT 'trg_sexp_authority_bu_20260723' UNION ALL
    SELECT 'trg_dcol_authority_bi_20260723' UNION ALL
    SELECT 'trg_dcol_authority_bu_20260723' UNION ALL
    SELECT 'trg_dcard_authority_bi_20260723' UNION ALL
    SELECT 'trg_dcard_authority_bu_20260723' UNION ALL
    SELECT 'trg_dchq_authority_bi_20260723' UNION ALL
    SELECT 'trg_dchq_authority_bu_20260723' UNION ALL
    SELECT 'trg_dvch_authority_bi_20260723' UNION ALL
    SELECT 'trg_dvch_authority_bu_20260723'
) required
LEFT JOIN information_schema.triggers tr
  ON tr.trigger_schema = @db_name
 AND tr.trigger_name = required.trigger_name
WHERE tr.trigger_name IS NULL;

/* 4. Master payments missing immutable Shift ID. BLOCKING when count > 0. */
SELECT COUNT(*) AS master_payments_missing_shift_id
FROM pump_operator_payments
WHERE pump_operator_id IS NOT NULL
  AND pump_operator_id > 0
  AND LOWER(payment_type) IN (
      'cash','card','cards','cheque','cheques','credit','multiple_credit',
      'other','shortage','excess'
  )
  AND (shift_id IS NULL OR shift_id = 0);

/* 5. Duplicate authoritative source identities. BLOCKING when rows are returned. */
SELECT business_id, source_type, source_id, COUNT(*) AS payment_count,
       GROUP_CONCAT(id ORDER BY id) AS pump_payment_ids
FROM pump_operator_payments
WHERE source_type IS NOT NULL
  AND source_type <> ''
  AND source_id IS NOT NULL
  AND source_id > 0
GROUP BY business_id, source_type, source_id
HAVING COUNT(*) > 1;

/* 6. Credit master gross/discount/net consistency. BLOCKING when rows are returned. */
SELECT id AS pump_payment_id, business_id, pump_operator_id, shift_id,
       gross_amount, discount_amount, net_amount, payment_amount
FROM pump_operator_payments
WHERE LOWER(payment_type) IN ('credit','multiple_credit')
  AND ABS(
      COALESCE(net_amount, payment_amount, 0)
      - (COALESCE(gross_amount, payment_amount, 0) - COALESCE(discount_amount, 0))
  ) > 0.02;

/* 7. Supporting rows linked more than once to one master. BLOCKING when rows are returned. */
SELECT 'settlement_cash_payments' table_name, business_id, pump_payment_id, COUNT(*) row_count
FROM settlement_cash_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'settlement_card_payments', business_id, pump_payment_id, COUNT(*)
FROM settlement_card_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'settlement_cheque_payments', business_id, pump_payment_id, COUNT(*)
FROM settlement_cheque_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'settlement_credit_sale_payments', business_id, pump_payment_id, COUNT(*)
FROM settlement_credit_sale_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'settlement_shortage_payments', business_id, pump_payment_id, COUNT(*)
FROM settlement_shortage_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'settlement_excess_payments', business_id, pump_payment_id, COUNT(*)
FROM settlement_excess_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'daily_collections', business_id, pump_payment_id, COUNT(*)
FROM daily_collections WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'daily_cards', business_id, pump_payment_id, COUNT(*)
FROM daily_cards WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'daily_cheque_payments', business_id, pump_payment_id, COUNT(*)
FROM daily_cheque_payments WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1
UNION ALL
SELECT 'daily_vouchers', business_id, pump_payment_id, COUNT(*)
FROM daily_vouchers WHERE pump_payment_id IS NOT NULL AND pump_payment_id > 0
GROUP BY business_id, pump_payment_id HAVING COUNT(*) > 1;

/* 8. Detail/master business/operator/Shift mismatches. BLOCKING when rows are returned. */
SELECT 'settlement_credit_sale_payments' table_name, d.id detail_id, d.pump_payment_id,
       d.business_id detail_business, p.business_id master_business,
       d.pump_operator_id detail_operator, p.pump_operator_id master_operator,
       d.shift_id detail_shift, p.shift_id master_shift
FROM settlement_credit_sale_payments d
JOIN pump_operator_payments p ON p.id = d.pump_payment_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id > 0
  AND (
      COALESCE(d.business_id,0) <> COALESCE(p.business_id,0)
      OR COALESCE(d.pump_operator_id,0) <> COALESCE(p.pump_operator_id,0)
      OR COALESCE(d.shift_id,0) <> COALESCE(p.shift_id,0)
  )
UNION ALL
SELECT 'settlement_card_payments', d.id, d.pump_payment_id,
       d.business_id, p.business_id, d.pump_operator_id, p.pump_operator_id,
       d.shift_id, p.shift_id
FROM settlement_card_payments d
JOIN pump_operator_payments p ON p.id = d.pump_payment_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id > 0
  AND (
      COALESCE(d.business_id,0) <> COALESCE(p.business_id,0)
      OR COALESCE(d.pump_operator_id,0) <> COALESCE(p.pump_operator_id,0)
      OR COALESCE(d.shift_id,0) <> COALESCE(p.shift_id,0)
  )
UNION ALL
SELECT 'settlement_cheque_payments', d.id, d.pump_payment_id,
       d.business_id, p.business_id, d.pump_operator_id, p.pump_operator_id,
       d.shift_id, p.shift_id
FROM settlement_cheque_payments d
JOIN pump_operator_payments p ON p.id = d.pump_payment_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id > 0
  AND (
      COALESCE(d.business_id,0) <> COALESCE(p.business_id,0)
      OR COALESCE(d.pump_operator_id,0) <> COALESCE(p.pump_operator_id,0)
      OR COALESCE(d.shift_id,0) <> COALESCE(p.shift_id,0)
  );

/* 9. Unresolved critical reconciliation events. BLOCKING when count > 0. */
SELECT COUNT(*) AS unresolved_critical_reconciliation_events
FROM petro_pd_payment_reconciliation_events
WHERE severity = 'critical' AND resolved_at IS NULL;

/* 10. Latest repair/audit runs for deployment evidence. */
SELECT id, tenant_reference, business_id, apply_changes, status,
       scanned_rows, issues_found, safe_repairs_found, repairs_applied,
       started_at, finished_at
FROM petro_pd_payment_integrity_repair_runs
ORDER BY id DESC
LIMIT 20;
