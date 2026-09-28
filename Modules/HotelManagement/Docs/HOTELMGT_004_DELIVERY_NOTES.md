# HOTELMGT_004 Delivery Notes

## Completed in this parcel
- Added standalone Hotel Maintenance page.
- Added work order create/list/delete flow with tenant and business-location scoping.
- Added automatic work order number generation using `MWO-000001` format.
- Added room status protection: open/in-progress/on-hold maintenance marks the room as `out_of_service` and housekeeping as `maintenance`.
- Completing/cancelling/deleting the final open maintenance order returns the room to available/dirty for housekeeping follow-up.
- Applied the same POS-style Hotel UI: cards, toolbar, table, badges, spacing, fonts and sizes.

## SQL policy
- This ZIP contains only the new specific SQL for parcel 004: `Docs/HOTELMGT_004_SQL.sql`.
- The cumulative installer remains in `Docs/HOTELMGT_MASTER_SQL.sql`.
