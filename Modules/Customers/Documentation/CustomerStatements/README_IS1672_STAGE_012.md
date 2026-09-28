# IS1672 Customer Statements — Stage 012 Large Parcel

This parcel introduces a Customers-owned Customer Statements workspace at:

`/customers/reports/customer-statement`

## Included
- Six colour-coded tabs specified in IS1672.
- Customers-module controller and service namespace.
- Multi-business/location/customer filter loading.
- Standard date-range picker and search/toolbars.
- Statement save wired to the existing proven Customers statement backend.
- Existing list/payment/logo/settings endpoints retained for compatibility.
- Customer-specific next allowed statement date endpoint.
- Duplicate/overlapping date protection remains in the existing save backend.
- New isolated CSS and JavaScript assets.

## Deployment
1. Copy `Modules/Customers` into the application, preserving paths.
2. Publish/copy assets:
   - `Modules/Customers/Resources/assets/css/customer-statements-v2.css` -> `public/modules/customers/css/customer-statements-v2.css`
   - `Modules/Customers/Resources/assets/js/customer-statements-v2.js` -> `public/modules/customers/js/customer-statements-v2.js`
3. Run:
   - `php artisan optimize:clear`
   - `php artisan route:clear`
   - `php artisan view:clear`
4. Open `/customers/reports/customer-statement`.

## SQL
No SQL is required for Stage 012. Existing customer statement tables are reused.

## Scope note
Stage 012 is the standalone foundation and primary statement creation workspace. The following large parcels will harden list actions, VAT conversion, payment workflow, logo CRUD, numbering persistence, email/PDF, and font designer persistence.
