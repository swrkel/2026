# HOTELMGT_015 Delivery Notes

Final Hotel Management audit parcel.

## Included
- Final module menu registry configuration (`Config/menu.php`).
- Full Hotel Management permission list expanded for all completed pages.
- Permission seeder upgraded to safely insert/update Spatie-style `permissions` and the module-local `hm_module_permissions` registry when those tables exist.
- Module navigation updated to use the central menu config and avoid broken route links.
- Service provider now merges the Hotel menu config.
- Added migration and raw SQL for `hm_module_permissions` and `hm_menu_registry`.

## SQL rule
- `HOTELMGT_015_SQL.sql` contains only parcel 015 SQL.
- `HOTELMGT_MASTER_SQL.sql` is cumulative up to parcel 015.

## Replacement
Upload/replace the full `HotelManagement` module folder from this ZIP.
