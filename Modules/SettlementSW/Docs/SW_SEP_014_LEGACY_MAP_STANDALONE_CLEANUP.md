# SW-SEP-014 — Settlement SW Legacy Map Standalone Cleanup

## Purpose
Continue Settlement SW standalone separation without changing correctly working behaviour.

## Completed
- Added `Services/SettlementSwLegacyMap.php`.
- Removed hard-coded legacy product module key usage from active controllers.
- Removed hard-coded `petro_settlement_id` column usage from active controllers.
- Added module-local config section for legacy-compatible column names.
- Removed direct VAT controller action reference from SettlementSW config.
- Kept legacy DB compatibility centralized in `Config/settlementsw.php`.

## Files changed
- `Config/settlementsw.php`
- `Services/SettlementSwLegacyMap.php`
- `Http/Controllers/SettlementSwAddPaymentBaseController.php`
- `Http/Controllers/SWAddPaymentController.php`
- `Http/Controllers/SettlementSwBaseController.php`
- `Http/Controllers/SettlementSWController.php`

## Audit
- No active direct `Modules\\Vat`, `Modules\\Petro`, `Modules\\HR`, or `Modules\\Superadmin` runtime dependency references found outside docs.
- PHP lint passed for module PHP files.
