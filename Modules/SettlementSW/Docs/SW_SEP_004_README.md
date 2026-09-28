# SW_SEP_004 View Separation

This package separates Settlement SW Blade views into smaller function-based folders using the requested `SettlementSw` naming direction.

## Changed main views
- `Resources/views/swsettlement/create.blade.php`
- `Resources/views/swsettlement/edit.blade.php`
- `Resources/views/swsettlement/add_payment_page.blade.php`

## New view folders
- `meter_sales/`
- `other_sales/`
- `other_income/`
- `customer_payments/`
- `payments/`
- `payments/tabs/`
- `expenses/`
- `preview/`
- `dashboard/`

## Safety note
The old `swsettlement/partials/*` files are not removed in this package. They are kept for backward compatibility while the main create/edit/payment pages now point to the new separated view paths.
