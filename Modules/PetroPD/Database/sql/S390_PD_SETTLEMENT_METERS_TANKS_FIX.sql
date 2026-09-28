/*
S390 - PD Settlement Meters and Tanks - 6 Jul 2026
Safe data clean-up SQL. Run inside each tenant database that needs historical PD rows refreshed.
No database name is hard-coded.
*/

/* 1) Ensure transaction dates used by Petro tank/meter reports match the PD settlement date. */
UPDATE transactions t
INNER JOIN settlements s ON s.id = t.petro_settlement_id
SET t.transaction_date = COALESCE(s.transaction_date, t.transaction_date),
    t.invoice_no = COALESCE(NULLIF(t.invoice_no, ''), s.settlement_no),
    t.updated_at = NOW()
WHERE t.petro_settlement_id IS NOT NULL
  AND s.settlement_no LIKE 'PDST%';

/* 2) Backfill Meter Sales location/date from the related transaction where columns exist in your DB.
   If your meter_sales table does not have location_id or transaction_date, skip these two statements. */
UPDATE meter_sales ms
INNER JOIN settlements s ON s.id = ms.settlement_no
SET ms.transaction_date = COALESCE(ms.transaction_date, s.transaction_date),
    ms.updated_at = NOW()
WHERE s.settlement_no LIKE 'PDST%';

UPDATE meter_sales ms
INNER JOIN settlements s ON s.id = ms.settlement_no
SET ms.location_id = COALESCE(ms.location_id, s.location_id),
    ms.updated_at = NOW()
WHERE s.settlement_no LIKE 'PDST%'
  AND s.location_id IS NOT NULL;

/* 3) Backfill tank transaction detail date/reference where the table and columns exist.
   If your tanks_transaction_details table does not exist or has different columns, skip this section.
*/
UPDATE tanks_transaction_details ttd
INNER JOIN transactions t ON t.id = ttd.transaction_id
INNER JOIN settlements s ON s.id = t.petro_settlement_id
SET ttd.transaction_date = COALESCE(ttd.transaction_date, s.transaction_date),
    ttd.date_and_time = COALESCE(ttd.date_and_time, s.transaction_date),
    ttd.settlement_no = COALESCE(NULLIF(ttd.settlement_no, ''), s.settlement_no),
    ttd.updated_at = NOW()
WHERE s.settlement_no LIKE 'PDST%';
