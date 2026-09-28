# SW_SEP_010 - Settlement SW Entity/Core Model Wrapper Cleanup

This package continues the Settlement SW standalone separation without changing working business logic.

## Completed

- Added Settlement SW module-local wrappers for shared ERP core models used by Settlement SW controllers/views.
- Replaced direct `App\Contact`, `App\Product`, `App\Business`, account, transaction and similar model imports with `Modules\SettlementSW\Entities\SettlementSw*` aliases.
- Updated the remaining active `PetroDailyShift` entity usage to `SettlementSwDailyShift`.
- Removed obsolete Petro-named entity files from the active module path.
- Replaced additional hard-coded product module keys with `config('settlementsw.product_module_key', 'petro_settlements')` to keep runtime behavior configurable.
- Added module-owned subscription guard fallback in the legacy `SWAddPaymentController` so active code does not directly check the Petro module flag.

## Notes

- Historic database table names such as `petro_daily_shifts` are preserved to avoid tenant data migration risk.
- Shared utility classes remain injected as ERP platform infrastructure because replacing them blindly would risk breaking working payment/accounting behavior.

## Validation

- PHP lint passed for `Http`, `Services`, `Entities`, `Routes`, `Providers`, and `Config`.
