# ATN-012 to ATN-016 — Large Consolidated Parcel

## ATN-012
- Supplier payments
- Settlement payment allocation
- BSP remittance structure

## ATN-013
- Staff incentive calculation
- Percentage and fixed incentive support
- Staff incentive register

## ATN-014
- Airline-wise sales report
- Outstanding invoice report
- Date-filtered report queries

## ATN-015
- Notification queue administration
- Safe bridge preparation for the existing standalone SMS/email modules
- No duplicate messaging engine

## ATN-016
- Route isolation test
- Business-scope test
- Health CLI command
- Scoped query guard
- Production hardening helpers

## Installation
1. Merge over ATN-001 through ATN-011.
2. Add the route includes in `Routes/ATN_012_TO_016_WEB_ROUTE_PATCH.php`.
3. Register/merge `Resources/lang/en/largeparcel2.php`.
4. Publish `atn-large-parcel-2.css` and `atn-large-parcel-2.js`.
5. Run the migration or `Database/SQL/MASTER/MASTER_ATN_012_TO_016_LARGE_PARCEL.sql`.
6. Assign included permissions.
7. Register `AirlineTicketingHealthCommand` in the module service provider.
8. Run `php artisan optimize:clear`.
