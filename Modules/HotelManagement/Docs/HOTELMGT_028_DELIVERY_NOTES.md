# HOTELMGT_028 Delivery Notes

## Added
- Valet Parking page in POS-style Hotel Management UI.
- Parking zone setup with capacity and active/inactive control.
- Valet ticket register with guest, room, vehicle, key tag, slot, timing and status flow.
- Status workflow: parked, requested, retrieving, released, paid, cancelled.
- Valet payment capture with cash/card/room-charge labels.
- Tenant, business and business-location scoped tables.

## SQL
- `Docs/HOTELMGT_028_SQL.sql` contains only Parcel 028 SQL.
- `Docs/HOTELMGT_MASTER_SQL.sql` is updated cumulatively.

## Main files
- `Http/Controllers/ValetParkingController.php`
- `Services/ValetParkingService.php`
- `Resources/views/valet/index.blade.php`
- `Database/Migrations/2026_07_05_000028_create_hm_valet_parking_tables.php`
- `Routes/web.php`
- `Config/menu.php`
