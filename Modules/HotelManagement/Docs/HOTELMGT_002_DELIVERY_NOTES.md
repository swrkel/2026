# HOTELMGT_002 - Hotel Management Continuation

## Completed in this parcel
- Continued POS-standard UI foundation from HOTELMGT_001.
- Added safe tenant/business/location scoping helper methods for all Hotel Management CRUD operations.
- Added update and delete route support for key Hotel Management pages.
- Added business and business-location scoping during creation for guests, reservations, rate plans, rooms, housekeeping, billing, hotel POS charges, inventory and setup.
- Improved Front Office check-in/check-out so room status updates are scoped to the current tenant/business/location.
- Added soft-delete aware delete handling for all supported tables.
- Added audit logging for create/update/delete/check-in/check-out operations where `hm_audit_logs` exists.

## Pages covered
- Dashboard
- Hotel Setup
- Rooms
- Rate Plans
- Reservations
- Front Office
- Housekeeping
- Billing / Folios
- Hotel POS Charges
- Inventory
- Guest CRM

## SQL
No new table is required beyond HOTELMGT_001. Use `HOTELMGT_002_SQL.sql` only as a verification/support SQL file. Existing tenant databases should still run the master SQL if they have not already applied the Hotel Management schema.
