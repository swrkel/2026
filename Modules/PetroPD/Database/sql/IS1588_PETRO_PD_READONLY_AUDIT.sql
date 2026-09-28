/* IS1588 Petro PD readonly checks - safe to run, no data change */

/* 1. Check pump current meter columns available */
SELECT COLUMN_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'pumps'
  AND COLUMN_NAME IN ('current_meter','pod_last_meter','last_meter_reading','starting_meter');

/* 2. Compare pump current meter with open pump assignments */
SELECT poa.id AS assignment_id, poa.shift_id, poa.pump_operator_id, p.id AS pump_id,
       p.pump_no, p.current_meter AS pump_current_meter, poa.starting_meter AS assignment_starting_meter,
       poa.status, poa.is_confirmed
FROM pump_operator_assignments poa
JOIN pumps p ON p.id = poa.pump_id
WHERE poa.status = 'open'
ORDER BY poa.id DESC
LIMIT 50;

/* 3. Payment Summary total verification */
SELECT shift_id, payment_type, SUM(payment_amount) AS total_amount, COUNT(*) AS records
FROM pump_operator_payments
GROUP BY shift_id, payment_type
ORDER BY shift_id DESC, payment_type
LIMIT 100;

/* 4. Shortage/excess values entered from pumper dashboard */
SELECT shift_id, pump_operator_id, payment_type, SUM(payment_amount) AS amount, COUNT(*) AS records
FROM pump_operator_payments
WHERE payment_type IN ('shortage','excess')
GROUP BY shift_id, pump_operator_id, payment_type
ORDER BY shift_id DESC
LIMIT 100;

/* 5. Pumper day entries with saved settlement date */
SELECT pde.id, pde.shift_id, pde.date, pde.settlement_no, pde.settlement_datetime, pde.updated_at
FROM pumper_day_entries pde
ORDER BY pde.id DESC
LIMIT 100;
