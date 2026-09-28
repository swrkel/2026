# SW_AUDIT_004 - Naming, Route and Runtime Cleanup

## Completed

### 1. SettlementSw naming standard cleanup
Renamed the remaining non-standard base controller classes so future code follows the requested `SettlementSw` convention:

- `SettlementSWController` -> `SettlementSwBaseController`
- `SWAddPaymentController` -> `SettlementSwAddPaymentBaseController`

All separated child controllers now extend the new `SettlementSw...` base classes.

### 2. Entity naming cleanup
Renamed the remaining copied entity classes that still used Petro-style class names while keeping their original database table bindings intact:

- `PetroDailyShift` -> `SettlementSwDailyShift`
- `PetroShift` -> `SettlementSwShift`
- `PetroNotificationTemplate` -> `SettlementSwNotificationTemplate`
- `PetroWhatsAppTemplate` -> `SettlementSwWhatsAppTemplate`

This is a code naming cleanup only. No database table rename is required.

### 3. Route/action runtime cleanup
Replaced remaining controller-string `action()` references with module-local named routes where possible:

- `settlement-sw.create`
- `settlement_sw.edit`
- `settlement_sw.show`
- `settlement-sw.store`
- `settlement-sw.destroy`

Added a module-local `destroy` route and controller method so the action dropdown no longer points to a missing controller action.

### 4. Runtime checks
- PHP syntax audit passed for all module PHP files.
- No active `Modules\\Petro` namespace references remain in module runtime files.
- No active `petro::lang` translation dependencies remain in module runtime files.

## Notes
Some ERP core models such as accounts, contacts, products, transactions, business locations and utilities are still referenced because they are shared ERP master/accounting infrastructure. These are not Petro-module dependencies.
