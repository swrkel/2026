# SW_AUDIT_006 - Settlement SW Asset & Route Standalone Cleanup

Package: `SW_AUDIT_006_SettlementSW_Asset_Route_Standalone_Cleanup.zip`

## Fixes included

1. Removed the create page dependency on global public JavaScript assets:
   - Changed `public/js/app.js` to `public/modules/settlementsw/js/app.js`.
   - Changed `public/js/payment.js` to `public/modules/settlementsw/js/payment.js`.
   - Added safe `file_exists()` based asset versioning to avoid runtime crashes before assets are published.

2. Added module-local `Resources/assets/js/payment.js` wrapper so the module has its own payment entry point after publishing assets.

3. Removed the remaining create-page dependency on old Petro endpoint:
   - Replaced `petro/settlement/check_prev_settlement` with route `settlement-sw.check-prev-settlement`.
   - Added `/settlement-sw/check-prev-settlement` route.
   - Added `SettlementSwBaseController::checkPrevSettlement()`.

4. Added local language key:
   - `previous_unfinished_settlement_exists`.

## Verification

- PHP syntax audit passed for all PHP/Blade files.
- Confirmed no remaining create-page references to `public/js/app.js`, `public/js/payment.js`, or `petro/settlement/check_prev_settlement`.

## Notes

Some database table names still include historic `petro_*` names because the existing ERP database schema appears to store Settlement SW source shift data in those tables. This package focuses on code/runtime independence without forcing a database migration that could break existing data.
