# ATN-007 to ATN-011 — Large Consolidated Parcel

This parcel combines five sections:

## ATN-007 — Supplier Finance
- Supplier settlements
- Settlement lines
- Airline/supplier payable tracking
- Agent commissions
- Ticket profitability

## ATN-008 — Operations and Notifications
- Ticketing deadline queue
- Operational task engine
- Notification rules
- Notification queue/log bridge

## ATN-009 — Reports and Exports
- Ticket Sales report
- Ticket Profitability report
- CSV export service
- Location/store/date filters

## ATN-010 — Audit, Security and Health
- Module audit service
- Module health check
- Location access middleware
- Required-table validation

## ATN-011 — UI and Dashboard Integration
- Full module navigation
- POS-style consolidated CSS/JS
- Executive dashboard service
- Shared report and operations layout

## Installation
1. Merge over ATN-001 through ATN-006.
2. Add the four route includes from `Routes/ATN_007_TO_011_WEB_ROUTE_PATCH.php`.
3. Register/merge `Resources/lang/en/largeparcel.php`.
4. Publish `atn-large-parcel.css` and `atn-large-parcel.js`.
5. Run both migrations or execute `Database/SQL/MASTER/MASTER_ATN_007_TO_011_LARGE_PARCEL.sql`.
6. Insert/assign the included permissions.
7. Run `php artisan optimize:clear`.

The package keeps all code under `Modules/AirlineTicketingNew`, uses `atn_` tables,
and preserves business/location/store scoping.
