-- PETROPD PAYMENT INTEGRITY - PARCEL 3 CONSERVATIVE REPAIR
-- BACK UP THE TENANT DATABASE FIRST.
-- This script NEVER deletes rows and NEVER overwrites a conflicting non-zero Shift ID.
-- It only fills NULL/zero fields from an exact direct relationship or a unique match.

START TRANSACTION;

-- A. Strong direct links: parent_id -> settlement detail id.
UPDATE settlement_cash_payments d JOIN pump_operator_payments p
  ON p.business_id=d.business_id AND p.parent_id=d.id AND LOWER(p.payment_type)='cash'
SET d.pump_payment_id=p.id,
    d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE (d.pump_payment_id IS NULL OR d.pump_payment_id=0);

UPDATE settlement_card_payments d JOIN pump_operator_payments p
  ON p.business_id=d.business_id AND p.parent_id=d.id AND LOWER(p.payment_type) IN ('card','cards')
SET d.pump_payment_id=p.id,
    d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE (d.pump_payment_id IS NULL OR d.pump_payment_id=0);

UPDATE settlement_cheque_payments d JOIN pump_operator_payments p
  ON p.business_id=d.business_id AND p.parent_id=d.id AND LOWER(p.payment_type) IN ('cheque','cheques')
SET d.pump_payment_id=p.id,
    d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE (d.pump_payment_id IS NULL OR d.pump_payment_id=0);

UPDATE settlement_credit_sale_payments d JOIN pump_operator_payments p
  ON p.business_id=d.business_id AND p.parent_id=d.id AND LOWER(p.payment_type) IN ('credit','multiple_credit')
SET d.pump_payment_id=p.id,
    d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE (d.pump_payment_id IS NULL OR d.pump_payment_id=0);

UPDATE settlement_shortage_payments d JOIN pump_operator_payments p
  ON p.business_id=d.business_id AND p.parent_id=d.id AND LOWER(p.payment_type)='shortage'
SET d.pump_payment_id=p.id,
    d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE (d.pump_payment_id IS NULL OR d.pump_payment_id=0);

UPDATE settlement_excess_payments d JOIN pump_operator_payments p
  ON p.business_id=d.business_id AND p.parent_id=d.id AND LOWER(p.payment_type)='excess'
SET d.pump_payment_id=p.id,
    d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE (d.pump_payment_id IS NULL OR d.pump_payment_id=0);

-- B. Existing linked rows: fill only missing scope fields from master.
UPDATE settlement_cash_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0;
UPDATE settlement_card_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0;
UPDATE settlement_cheque_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0;
UPDATE settlement_credit_sale_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0;
UPDATE settlement_shortage_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0;
UPDATE settlement_excess_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0;

-- C. Cheque operational rows have a strong historical linked_payment_id.
UPDATE daily_cheque_payments d JOIN pump_operator_payments p
  ON p.id=d.linked_payment_id AND p.business_id=d.business_id
 AND LOWER(p.payment_type) IN ('cheque','cheques')
SET d.pump_payment_id=p.id,
    d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE d.pump_payment_id IS NULL OR d.pump_payment_id=0;

-- D. Unique legacy operational matches. HAVING COUNT(*)=1 prevents guessing.
UPDATE daily_cards d
JOIN (
  SELECT business_id,pump_operator_id,shift_id,collection_form_no,
         MIN(id) AS pump_payment_id,COUNT(*) AS payment_count
  FROM pump_operator_payments
  WHERE LOWER(payment_type) IN ('card','cards')
    AND shift_id IS NOT NULL AND shift_id>0
    AND collection_form_no IS NOT NULL AND collection_form_no<>''
  GROUP BY business_id,pump_operator_id,shift_id,collection_form_no
  HAVING COUNT(*)=1
) p ON p.business_id=d.business_id
   AND p.pump_operator_id=d.pump_operator_id
   AND p.shift_id=d.shift_id
   AND CAST(p.collection_form_no AS CHAR)=CAST(d.collection_no AS CHAR)
SET d.pump_payment_id=p.pump_payment_id
WHERE d.pump_payment_id IS NULL OR d.pump_payment_id=0;

UPDATE daily_vouchers d
JOIN (
  SELECT business_id,pump_operator_id,shift_id,collection_form_no,
         MIN(id) AS pump_payment_id,COUNT(*) AS payment_count
  FROM pump_operator_payments
  WHERE LOWER(payment_type) IN ('credit','multiple_credit')
    AND shift_id IS NOT NULL AND shift_id>0
    AND collection_form_no IS NOT NULL AND collection_form_no<>''
  GROUP BY business_id,pump_operator_id,shift_id,collection_form_no
  HAVING COUNT(*)=1
) p ON p.business_id=d.business_id
   AND p.pump_operator_id=d.operator_id
   AND p.shift_id=d.shift_id
   AND CAST(p.collection_form_no AS CHAR)=CAST(d.daily_vouchers_no AS CHAR)
SET d.pump_payment_id=p.pump_payment_id
WHERE d.pump_payment_id IS NULL OR d.pump_payment_id=0;

-- E. Fill missing operational scope from an existing link only.
UPDATE daily_collections d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0;
UPDATE daily_cards d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0;
UPDATE daily_cheque_payments d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.pump_operator_id=IF(d.pump_operator_id IS NULL OR d.pump_operator_id=0,p.pump_operator_id,d.pump_operator_id)
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0;
UPDATE daily_vouchers d JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=IF(d.shift_id IS NULL OR d.shift_id=0,p.shift_id,d.shift_id),
    d.operator_id=IF(d.operator_id IS NULL OR d.operator_id=0,p.pump_operator_id,d.operator_id)
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0;

COMMIT;

-- Run PETROPD_PAYMENT_INTEGRITY_HISTORICAL_AUDIT_PARCEL_3_20260723.sql again.
-- Any remaining rows are ambiguous or conflicting and must be reviewed; do not guess.
