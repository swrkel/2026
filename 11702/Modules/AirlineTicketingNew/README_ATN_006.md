# ATN-006 — Reissue, Void, Cancellation, Refund and Credit Notes

## Included
- Reissue requests and fare-difference calculations
- Void requests and approval
- Cancellation requests
- Refund calculation and approval
- Credit-note creation
- Ticket action history
- Business/location/store-scoped data
- Separate controllers, services, requests, models, routes, views, assets, migration and SQL

## Installation
1. Merge over ATN-001 through ATN-005.
2. Add inside the authenticated AirlineTicketingNew route group:
   `require module_path('AirlineTicketingNew', 'Routes/post-ticket.php');`
3. Register or merge `Resources/lang/en/postticket.php`.
4. Publish/copy `atn-post-ticket.css` and `atn-post-ticket.js`.
5. Run migration or `Database/SQL/MASTER/MASTER_ATN_006_REISSUE_VOID_REFUND.sql`.
6. Assign ATN-006 permissions.
7. Run `php artisan optimize:clear`.
