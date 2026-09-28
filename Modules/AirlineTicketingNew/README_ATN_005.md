# ATN-005 — Tickets, Invoices, Receipts and Payments

## Included
- Reservation-to-ticket issuance
- Ticket segment/coupon creation
- Ticket numbering
- Ticket invoice creation
- Invoice numbering
- Customer payment receipt
- Partial and full payment allocation
- Automatic invoice paid/due updates
- Payment numbering
- Receipt numbering
- Receipt view and printable receipt
- Dedicated models, services, requests, controllers, routes, views, assets, migration and SQL

## Installation
1. Merge over ATN-001 through ATN-004.
2. Add inside the authenticated AirlineTicketingNew route group:
   `require module_path('AirlineTicketingNew', 'Routes/ticketing.php');`
3. Register or merge `Resources/lang/en/ticketing.php`.
4. Publish/copy `atn-ticketing.css` and `atn-ticketing.js`.
5. Run the migration or `Database/SQL/MASTER/MASTER_ATN_005_TICKETS_INVOICES_PAYMENTS.sql`.
6. Assign ATN-005 permissions.
7. Run `php artisan optimize:clear`.

All records remain scoped by business, location and store.
