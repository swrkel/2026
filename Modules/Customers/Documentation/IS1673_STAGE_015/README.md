# IS1673 Customer Payments - Stage 015 Large Parcel

This stage establishes the standalone Customer Payments workspace inside the Customers module.

Implemented in this parcel:
- Customers sidebar entry through the Customers-owned page registry.
- Standalone `/customers/customer-payments` workspace.
- Exactly four tabs:
  - Customer Payment Bulk (purple)
  - List Customer Payments (blue)
  - Customer Interest (green)
  - Interest Settings (orange)
- Existing Customers-owned controllers, routes, views and services retained.
- Existing bulk payment, customer balance, invoice loading, payment listing, interest listing and interest settings logic retained.
- No Contacts module view/controller dependency is introduced.

Deployment:
1. Copy the `Modules/Customers` folder over the existing module.
2. Run `php artisan optimize:clear`.
3. Run `php artisan route:clear` and `php artisan view:clear`.
4. Test `/customers/customer-payments`.

This is the foundation parcel. Subsequent parcels will harden posting, edit/delete, exports, print, validation, audit and reporting workflows.
