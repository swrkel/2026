# HOTELMGT_001 - Hotel Management Completion Base Parcel

## Scope completed in this parcel

- Reworked Hotel Management base pages to follow the ERP/POS clean design standard:
  - Dashboard
  - Setup
  - Rooms
  - Rate Plans
  - Reservations
  - Front Office
  - Housekeeping
  - Billing / Folios
  - POS Room Charges
  - Inventory / Stores
  - Guest CRM
  - Reports pages use the same module layout
- Added reusable module layout, navigation, toolbar, table and card styling.
- Fixed recursive module layout issue by using `hotel_content` section inside the module layout.
- Consolidated duplicate operation route definitions into `Routes/web.php` to avoid duplicate route names.
- Added basic working save/list functionality for the main operational pages.
- Added tenant/business/location scoping helper for safe multi-tenant, multiple-business filtering.
- Added tenant/business scope migration for Hotel Management tables that were missing `business_id` and `business_location_id`.
- Added raw SQL for applying the same scope columns manually to each tenant database.

## Important upload order

1. Replace the existing `Modules/HotelManagement` folder with this folder.
2. Run the migration or run the raw SQL file on each tenant database:
   - `Docs/HOTELMGT_001_SQL.sql`
3. Clear Laravel cache/routes/views if your deployment process requires it.

## Notes

- The provided POS URL redirected to the login page from this environment, so the implementation follows the POS/ERP clean standard already used in the system: compact Source Sans Pro style, white cards, rounded borders, 13px forms/tables, coloured toolbar buttons, and dashboard KPI cards.
- This is a consolidated base parcel. Advanced workflows such as room allocation calendars, OTA/channel manager, tax/service-charge posting, night audit, advanced folio splitting, and full accounting integration can continue in the next parcel.
