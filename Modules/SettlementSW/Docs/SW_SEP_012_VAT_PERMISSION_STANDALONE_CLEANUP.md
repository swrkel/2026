# SW_SEP_012 - VAT and Permission Standalone Cleanup

## Scope
This package continues Settlement SW standalone separation after SW_SEP_011.

## Completed
- Removed direct controller dependency on `\Modules\Vat\Http\Controllers\VatController`.
- Added `SettlementSwVatAdapter` to keep optional VAT regenerate action behind module-local code.
- Replaced `superadmin::lang.regenerate_vat` usage with `settlementsw::lang.regenerate_vat`.
- Removed direct service dependency on VAT module entity classes.
- Added SettlementSW-owned VAT payment wrapper entities:
  - `SettlementSwVatSettlementCardPayment`
  - `SettlementSwVatSettlementCashPayment`
  - `SettlementSwVatSettlementCreditSalePayment`
- Added `SettlementSwPermission` so module-specific permission keys are used first, with legacy fallback only to avoid breaking existing roles.
- Added optional VAT integration settings into `Config/settlementsw.php`.

## Compatibility Notes
- Existing VAT regenerate functionality is preserved when the host ERP still has the VAT module installed.
- If VAT is not installed, the VAT regenerate action is hidden safely instead of causing controller/action errors.
- Existing legacy permissions (`settlement.edit`, `settlement.delete`) are preserved as fallback while new permissions (`settlement_sw.update`, `settlement_sw.delete`) are being seeded/deployed.

## Lint
All PHP files in the SettlementSW module passed `php -l` syntax validation.
