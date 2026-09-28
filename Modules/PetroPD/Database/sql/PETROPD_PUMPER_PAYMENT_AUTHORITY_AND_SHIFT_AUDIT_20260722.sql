/*
 PETROPD PUMPER PAYMENT AUTHORITY & SHIFT INTEGRITY AUDIT
 Date: 2026-07-22

 READ-ONLY sections are safe to run at any time.
 REPAIR sections only fill missing links/Shift IDs when the relationship is
 unambiguous. They never change a non-null master Shift ID.
*/

/* 1. Authoritative credit totals: one row/amount per pump_operator_payments.id. */
SELECT
    pop.business_id,
    pop.pump_operator_id,
    pop.shift_id,
    COUNT(*) AS unique_credit_payment_count,
    SUM(COALESCE(pop.gross_amount, pop.payment_amount, 0)) AS credit_gross_total,
    SUM(COALESCE(pop.discount_amount, 0)) AS credit_discount_total,
    SUM(COALESCE(pop.net_amount,
                 COALESCE(pop.gross_amount, pop.payment_amount, 0)
                 - COALESCE(pop.discount_amount, 0))) AS credit_net_total
FROM pump_operator_payments pop
WHERE LOWER(pop.payment_type) = 'credit'
GROUP BY pop.business_id, pop.pump_operator_id, pop.shift_id
ORDER BY pop.business_id, pop.pump_operator_id, pop.shift_id;

/* 2. Collection numbers reused inside the same exact shift. */
SELECT
    business_id,
    pump_operator_id,
    shift_id,
    collection_form_no,
    COUNT(*) AS master_payment_count,
    GROUP_CONCAT(id ORDER BY id) AS pump_payment_ids
FROM pump_operator_payments
WHERE LOWER(payment_type) = 'credit'
  AND collection_form_no IS NOT NULL
  AND collection_form_no <> ''
GROUP BY business_id, pump_operator_id, shift_id, collection_form_no
HAVING COUNT(*) > 1;

/* 3. More than one credit-sale header linked to one master payment. */
SELECT
    pump_payment_id,
    COUNT(*) AS credit_header_count,
    GROUP_CONCAT(id ORDER BY id) AS credit_detail_ids
FROM settlement_credit_sale_payments
WHERE pump_payment_id IS NOT NULL
GROUP BY pump_payment_id
HAVING COUNT(*) > 1;

/* 4. Credit details with no master payment link. */
SELECT
    id AS credit_detail_id,
    business_id,
    pump_operator_id,
    shift_id,
    collection_form_no,
    amount,
    total_discount,
    sub_total
FROM settlement_credit_sale_payments
WHERE pump_payment_id IS NULL;

/* 5. Linked records whose business/operator/Shift scope does not agree. */
SELECT
    scsp.id AS credit_detail_id,
    scsp.pump_payment_id,
    scsp.business_id AS detail_business_id,
    pop.business_id AS master_business_id,
    scsp.pump_operator_id AS detail_operator_id,
    pop.pump_operator_id AS master_operator_id,
    scsp.shift_id AS detail_shift_id,
    pop.shift_id AS master_shift_id
FROM settlement_credit_sale_payments scsp
LEFT JOIN pump_operator_payments pop ON pop.id = scsp.pump_payment_id
WHERE scsp.pump_payment_id IS NOT NULL
  AND (
        pop.id IS NULL
        OR NOT (scsp.business_id <=> pop.business_id)
        OR NOT (scsp.pump_operator_id <=> pop.pump_operator_id)
        OR NOT (scsp.shift_id <=> pop.shift_id)
  );

/* 6. Master net amount versus exact supporting credit-sale header. */
SELECT
    pop.id AS pump_payment_id,
    pop.business_id,
    pop.pump_operator_id,
    pop.shift_id,
    COALESCE(pop.gross_amount, pop.payment_amount, 0) AS master_gross,
    COALESCE(pop.discount_amount, 0) AS master_discount,
    COALESCE(pop.net_amount,
             COALESCE(pop.gross_amount, pop.payment_amount, 0)
             - COALESCE(pop.discount_amount, 0)) AS master_net,
    COUNT(scsp.id) AS linked_credit_header_count,
    COALESCE(SUM(scsp.amount), 0) AS detail_gross,
    COALESCE(SUM(scsp.total_discount), 0) AS detail_discount,
    COALESCE(SUM(COALESCE(scsp.sub_total,
                          scsp.amount - COALESCE(scsp.total_discount, 0))), 0) AS detail_net
FROM pump_operator_payments pop
LEFT JOIN settlement_credit_sale_payments scsp
       ON scsp.pump_payment_id = pop.id
WHERE LOWER(pop.payment_type) = 'credit'
GROUP BY
    pop.id,
    pop.business_id,
    pop.pump_operator_id,
    pop.shift_id,
    pop.gross_amount,
    pop.payment_amount,
    pop.discount_amount,
    pop.net_amount
HAVING linked_credit_header_count <> 1
    OR ABS(master_net - detail_net) >= 0.02;

/* -------------------------------------------------------------------------
 SAFE REPAIR A: link only when one and only one master payment matches the
 same business/operator/Shift/collection number.
 ------------------------------------------------------------------------- */
UPDATE settlement_credit_sale_payments scsp
JOIN (
    SELECT
        business_id,
        pump_operator_id,
        shift_id,
        collection_form_no,
        MIN(id) AS pump_payment_id,
        COUNT(*) AS match_count
    FROM pump_operator_payments
    WHERE LOWER(payment_type) = 'credit'
      AND shift_id IS NOT NULL
      AND collection_form_no IS NOT NULL
      AND collection_form_no <> ''
    GROUP BY business_id, pump_operator_id, shift_id, collection_form_no
    HAVING COUNT(*) = 1
) exact_match
  ON exact_match.business_id = scsp.business_id
 AND exact_match.pump_operator_id = scsp.pump_operator_id
 AND exact_match.shift_id = scsp.shift_id
 AND CAST(exact_match.collection_form_no AS CHAR) = CAST(scsp.collection_form_no AS CHAR)
SET scsp.pump_payment_id = exact_match.pump_payment_id
WHERE scsp.pump_payment_id IS NULL;

/* SAFE REPAIR B: fill a missing detail Shift ID from its linked master only.
   Existing non-null Shift IDs are never changed. */
UPDATE settlement_credit_sale_payments scsp
JOIN pump_operator_payments pop ON pop.id = scsp.pump_payment_id
SET scsp.shift_id = pop.shift_id
WHERE scsp.pump_payment_id IS NOT NULL
  AND (scsp.shift_id IS NULL OR scsp.shift_id = 0);

/* SAFE REPAIR C: fill missing master financial/metadata columns from the exact
   one-to-one detail link. Existing values are preserved. */
UPDATE pump_operator_payments pop
JOIN settlement_credit_sale_payments scsp ON scsp.pump_payment_id = pop.id
LEFT JOIN (
    SELECT pump_payment_id, COUNT(*) AS detail_count
    FROM settlement_credit_sale_payments
    WHERE pump_payment_id IS NOT NULL
    GROUP BY pump_payment_id
) detail_count ON detail_count.pump_payment_id = pop.id
SET pop.gross_amount = COALESCE(pop.gross_amount, pop.payment_amount, scsp.amount),
    pop.discount_amount = COALESCE(pop.discount_amount, scsp.total_discount, 0),
    pop.net_amount = COALESCE(
        pop.net_amount,
        scsp.sub_total,
        COALESCE(pop.payment_amount, scsp.amount) - COALESCE(scsp.total_discount, 0)
    ),
    pop.source_type = COALESCE(pop.source_type, 'credit_sale'),
    pop.source_id = COALESCE(pop.source_id, scsp.id),
    pop.customer_id = COALESCE(pop.customer_id, scsp.customer_id),
    pop.transaction_date = COALESCE(pop.transaction_date, scsp.order_date),
    pop.reference_no = COALESCE(NULLIF(pop.reference_no, ''),
                                NULLIF(scsp.bill_number, ''),
                                NULLIF(scsp.order_number, ''))
WHERE LOWER(pop.payment_type) = 'credit'
  AND detail_count.detail_count = 1;

/* Final verification: this query must return zero rows before finalization. */
SELECT
    scsp.id AS credit_detail_id,
    scsp.pump_payment_id,
    scsp.shift_id AS detail_shift_id,
    pop.shift_id AS master_shift_id
FROM settlement_credit_sale_payments scsp
LEFT JOIN pump_operator_payments pop ON pop.id = scsp.pump_payment_id
WHERE scsp.pump_payment_id IS NULL
   OR pop.id IS NULL
   OR NOT (scsp.business_id <=> pop.business_id)
   OR NOT (scsp.pump_operator_id <=> pop.pump_operator_id)
   OR NOT (scsp.shift_id <=> pop.shift_id);
