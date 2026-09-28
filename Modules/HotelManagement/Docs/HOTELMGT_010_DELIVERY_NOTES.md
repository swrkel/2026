# HOTELMGT_010 Delivery Notes

## Completed
- Added Room Service / In-room Dining page.
- Added room service order capture with room, folio, guest, delivery time, priority and kitchen note.
- Added kitchen/delivery status workflow: Ordered, Preparing, Ready, Delivered, Cancelled.
- Added automatic folio room charge posting when payment mode is Charge to Room.
- Added tenant and business location scoping for all room service records.
- Added POS-standard layout, cards, toolbar, table and badge styling reuse.

## SQL Files
- `Docs/HOTELMGT_010_SQL.sql` contains only the SQL introduced in parcel 010.
- `Docs/HOTELMGT_MASTER_SQL.sql` is cumulative up to parcel 010.

## Replacement Notes
Upload/replace the HotelManagement module folder from this ZIP. Run `HOTELMGT_010_SQL.sql` on tenant databases that already have parcels 001-009. For a fresh tenant, use the master SQL.
