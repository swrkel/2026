# HOTELMGT_008 Delivery Notes

## Completed area
Hotel POS completion for restaurant/minibar/service posting.

## Included changes
- Added POS categories, menu items, POS orders and POS order line tables.
- Rebuilt Hotel POS page with the same POS-style card, toolbar, table, font and spacing standard used in the module.
- Added menu item creation from the POS page.
- Added POS order posting with cash/card/charge-to-room modes.
- When payment mode is Charge to Room and a folio is selected, the POS order automatically creates a posted room charge and recalculates folio totals.
- All reads/writes use business and business-location scoped helpers for multi-tenant / multi-business safety.

## SQL files
- `Docs/HOTELMGT_008_SQL.sql` contains only SQL related to this parcel.
- `Docs/HOTELMGT_MASTER_SQL.sql` has been appended with this parcel's SQL for cumulative installation.
