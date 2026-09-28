# Leasing Module

Standalone leasing module for the Banking suite.

## Main areas
- Collateral types
- Leasing products
- LeaseAsset registration
- LeaseContract lifecycle
- Application calculator
- Payment
- Restructure
- Insurance due list
- Asset and storage locations
- Leasing reports

## Integration rules
- Uses existing `business_locations` as Location.
- Does not depend on Deposits, Loan, Leasing, Savings, or CurrentAccounts internals.
- Banking Customer / CIF integration is through `banking_customer_id` only.
- Business logic must remain inside `Modules/Leasing` services.

## Route note
`Routes/web.php` does not declare a namespace because `Providers/RouteServiceProvider.php` already applies the module namespace.
