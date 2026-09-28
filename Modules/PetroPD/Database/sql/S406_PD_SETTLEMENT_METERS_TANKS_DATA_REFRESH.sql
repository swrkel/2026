/*
S406 - PD Settlement Meters and Tanks data refresh (tenant database only)
Purpose: refresh historical PD settlement references/date/location for Tank and Meter reports.
Run only inside tenant databases where old PD settlement rows already exist and need backfill.
No CREATE/ALTER statements are included.
*/

UPDATE transactions t
INNER JOIN settlements s ON s.id = t.petro_settlement_id
SET t.transaction_date = COALESCE(s.transaction_date, t.transaction_date),
    t.invoice_no = COALESCE(NULLIF(t.invoice_no, ''), s.settlement_no),
    t.ref_no = COALESCE(NULLIF(t.ref_no, ''), s.settlement_no),
    t.updated_at = NOW()
WHERE t.petro_settlement_id IS NOT NULL
  AND s.settlement_no LIKE 'PDST%';

UPDATE meter_sales ms
INNER JOIN settlements s ON s.id = ms.settlement_no
SET ms.transaction_date = COALESCE(ms.transaction_date, s.transaction_date),
    ms.location_id = COALESCE(ms.location_id, s.location_id),
    ms.updated_at = NOW()
WHERE s.settlement_no LIKE 'PDST%';
