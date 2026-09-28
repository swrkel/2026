# HOTELMGT_042 Delivery Notes

## Focus
Assets & Equipment Management for the Hotel Management module.

## Included
- Asset/equipment register.
- Room, department, staff, guest or department assignment workflow.
- Asset inspection workflow with next inspection date and maintenance-required flag.
- Asset disposal capture.
- POS-standard card, KPI, toolbar and table UI.
- Tenant database, business and business-location scoping.

## SQL
- `Docs/HOTELMGT_042_SQL.sql` contains only SQL introduced in this parcel.
- `Docs/HOTELMGT_MASTER_SQL.sql` is updated with cumulative SQL up to this parcel.
- SQL is global and does not include hardcoded database names.

## Main Files Added/Changed
- `Http/Controllers/AssetEquipmentController.php`
- `Services/AssetEquipmentService.php`
- `Resources/views/asset_equipment/index.blade.php`
- `Routes/web.php`
- `Resources/views/partials/nav.blade.php`
- `Config/menu.php`
