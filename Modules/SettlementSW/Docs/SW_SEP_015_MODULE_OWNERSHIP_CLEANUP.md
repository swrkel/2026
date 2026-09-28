# SW-SEP-015 - Settlement SW Module Ownership Cleanup

## Goal
Continue Settlement SW standalone separation without changing working business logic.

## Completed in this package

1. Added more module-owned table-name resolver methods in `SettlementSwTables`.
2. Moved additional active controller table names into `Config/settlementsw.php`:
   - settlements
   - products
   - temp_data
   - daily_collections
   - daily_voucher_items
   - customer_payments
   - pump_operator_other_sales
   - pump_operator_meter_sale_details
   - meter_sales
3. Replaced remaining direct `DB::table('...')` calls in active Settlement SW controllers where safe.
4. Added Settlement SW utility services for future small-file maintenance:
   - `SettlementSwNumberFormatter`
   - `SettlementSwDateFormatter`
   - `SettlementSwReportHelper`
5. Kept legacy physical table names configurable so tenant DB compatibility is preserved.

## Safety note
This stage does not rename database tables or change existing route names. It only moves table ownership/configuration into the SettlementSW module so future schema separation can be handled centrally.

## Validation
PHP lint passed for all changed PHP runtime files.
