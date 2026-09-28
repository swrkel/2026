# SW_AUDIT_005 Runtime Reference Cleanup

## Completed in this package

### 1. Removed legacy Petro JavaScript global names
The Add Payment auto-balance logic still used copied global names such as:
- `window.__petro_auto_balance_pending`
- `window.__petro_auto_balance_state`

These were renamed to module-local Settlement SW names:
- `window.__settlementsw_auto_balance_pending`
- `window.__settlementsw_auto_balance_state`

This avoids future confusion and removes another copied Petro runtime reference from active JS/view code.

### 2. Fixed missing Settlement SW asset reference
`Resources/views/swsettlement/edit.blade.php` was still loading:
- `modules/settlementsw/js/payment.js`

That file is not present in the module package. The view now loads the separated module JS file:
- `modules/settlementsw/js/swsettlement/add-payment.js`

### 3. Added hyphenated route-name aliases
Some separated views/controllers use the newer `settlement-sw.*` route naming style, while old copied controller sections still use `settlement_sw.*`.

To avoid route-not-found runtime issues during transition, aliases were added for:
- `settlement-sw.edit`
- `settlement-sw.show`
- `settlement-sw.print`

The older route names remain available for backward compatibility.

## Audit status
- PHP syntax audit passed for module controllers, services, entities, providers, config, and routes.
- ZIP integrity passed.

## Remaining allowed ERP-core dependencies
The module still calls shared ERP core areas such as contacts, accounts, products, business locations, tax/VAT, expenses, and HR work-shift lookup where the original ERP business process depends on shared master/accounting data. These are not Petro-module dependencies.
