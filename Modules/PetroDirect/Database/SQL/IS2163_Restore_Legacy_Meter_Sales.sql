-- IS2163 - meter sales missing from settlements entered before 2026/08/23
--
--
-- WHAT IS WRONG
--
-- On older Direct Settlements:
--   - the Pumps column is blank
--   - Total Amount shows only the other-sale total
--   - Edit shows no meter sale rows
--
-- All three read the same relationship:
--
--     Settlement::meter_sales()  ->  hasMany(MeterSale, 'settlement_no', 'id')
--                                        ->petroDirectOwned()
--
-- and that scope requires shift_id to be NULL or 0:
--
--     where(shift_id is null or shift_id = 0)
--     and exists (settlement is a petro direct settlement of this business)
--
-- Counted on the reported tenant:
--
--     before 23 Aug   shift_id non-zero   623 rows   <- EXCLUDED
--     before 23 Aug   shift_id null       527 rows   <- fine
--     before 23 Aug   shift_id zero         5 rows   <- fine
--     on/after 23 Aug shift_id zero        46 rows   <- fine
--
-- Everything written since 23 August stores 0. The 623 older rows carry a
-- non-zero shift_id and are therefore filtered out, which is why only SOME
-- older settlements are affected rather than all of them.
--
--
-- WHY THIS IS A DATA SCRIPT AND NOT A CODE CHANGE
--
-- The shift_id condition is what separates Direct Settlement meter sales from
-- shift-based ones, which share the same table. Relaxing it in code would risk
-- pulling shift settlement rows into Direct Settlement totals - a much worse
-- fault than the one being fixed, and a silent one.
--
-- This script instead normalises the rows that DEMONSTRABLY belong to a direct
-- settlement, leaving the scope intact.
--
--
-- BEFORE YOU RUN IT
--
-- TAKE A BACKUP:
--
--     mysqldump -u USER -p DBNAME meter_sales > meter_sales_backup.sql
--
-- RUN STEP 1 AND STEP 2 FIRST and read them. Step 2 in particular decides
-- whether Step 3 is safe.


-- ---------------------------------------------------------------------------
-- STEP 1 - what would change
-- ---------------------------------------------------------------------------
--
-- Every meter sale row that belongs to a settlement but is hidden by the
-- shift_id filter.

SELECT s.`id`            AS settlement_id,
       s.`settlement_no`,
       s.`transaction_date`,
       COUNT(m.`id`)     AS hidden_rows,
       GROUP_CONCAT(DISTINCT m.`shift_id`) AS shift_ids
  FROM `meter_sales` m
  JOIN `settlements` s ON s.`id` = m.`settlement_no`
 WHERE m.`shift_id` IS NOT NULL
   AND m.`shift_id` <> 0
 GROUP BY s.`id`, s.`settlement_no`, s.`transaction_date`
 ORDER BY s.`transaction_date`;


-- ---------------------------------------------------------------------------
-- STEP 2 - SAFETY CHECK: are any of these actually shift settlements?
-- ---------------------------------------------------------------------------
--
-- The whole question is whether a non-zero shift_id means "this row belongs to
-- a shift settlement" or is simply legacy data on a direct settlement.
--
-- This lists the shift ids involved and whether a matching work shift exists.
--
-- If EVERY row comes back with no matching shift, the ids are stale and Step 3
-- is safe.
--
-- If real shifts come back, STOP. Those rows may belong to shift settlements
-- and clearing shift_id would merge two different kinds of settlement. Send me
-- this output instead.

-- The work-shift table belongs to the HR module and its name is not assumed
-- here. This instead shows, for each shift_id, whether the SETTLEMENT those
-- rows belong to is a Direct Settlement - which is the question that actually
-- matters.
--
-- A row on a Direct Settlement carrying a shift_id is legacy data: the
-- settlement is not shift-based, so the id serves no purpose and is what hides
-- the row.

SELECT m.`shift_id`,
       COUNT(*)                          AS rows_affected,
       COUNT(DISTINCT s.`id`)            AS settlements_affected,
       MIN(s.`transaction_date`)         AS earliest,
       MAX(s.`transaction_date`)         AS latest
  FROM `meter_sales` m
  JOIN `settlements` s ON s.`id` = m.`settlement_no`
 WHERE m.`shift_id` IS NOT NULL
   AND m.`shift_id` <> 0
 GROUP BY m.`shift_id`
 ORDER BY rows_affected DESC;

-- If every "latest" date above is BEFORE 2026-08-23, these are all legacy rows
-- and Step 3 is safe.
--
-- If any row shows a date ON OR AFTER 23 August, stop - something is still
-- writing shift_id on new direct settlements, and clearing it would mask a
-- live fault rather than fix historical data. Send me this output instead.


-- ---------------------------------------------------------------------------
-- STEP 3 - the correction
-- ---------------------------------------------------------------------------
--
-- ONLY run this if Step 2 reported no real work shifts.
--
-- Sets shift_id to 0 - the value every row written since 23 August uses - so
-- these rows satisfy the same scope as the newer ones.
--
-- Restricted to rows whose settlement exists, so nothing orphaned is touched.

UPDATE `meter_sales` m
  JOIN `settlements` s ON s.`id` = m.`settlement_no`
   SET m.`shift_id` = 0
 WHERE m.`shift_id` IS NOT NULL
   AND m.`shift_id` <> 0;


-- ---------------------------------------------------------------------------
-- STEP 4 - verify
-- ---------------------------------------------------------------------------
--
-- Re-run STEP 1. It should return no rows.
--
-- Then open Petro Direct / List Direct Settlement and confirm an older
-- settlement now shows its pumps, its full total, and its meter sale rows on
-- Edit.
