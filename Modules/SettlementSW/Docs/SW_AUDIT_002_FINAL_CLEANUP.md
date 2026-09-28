# SW_AUDIT_002 - Final Standalone Cleanup

This continuation pass was done after `SW_AUDIT_001`.

## Completed

- Replaced remaining `petro::lang.*` translation calls with module-local `settlementsw::lang.*`.
- Added missing local language keys into `Resources/lang/en/lang.php` so Settlement SW does not require Petro language files for labels/messages.
- Replaced remaining Petro view namespace references in controllers with Settlement SW module views.
- Added `SettlementSwTempController` and `settlement-sw.temp.save` route to remove the create-page auto-save dependency on the main `TempController`.
- Kept Settlement SW routes split by feature area: dashboard, payments, reports.

## Notes

Some ERP-core models/utilities are still referenced where they are shared business master data or accounting infrastructure, such as contacts, accounts, products, business locations, and transaction utilities. These are framework-level ERP dependencies, not Petro-module dependencies.

## Audit result

No active Petro module URL, Petro controller namespace, Petro entity namespace, or Petro language dependency remains in runtime files after this pass.
