# SW-SEP-013 Settlement SW View/Table Config Cleanup

## Scope
Continued standalone cleanup inside `Modules/SettlementSW` only. No correctly working outside-module files were changed.

## Changes

### 1. Removed model/database lookup from print/show Blade views
The settlement print/show views no longer query `SettlementSwWorkShift` directly inside Blade.

Added:
- `Services/SettlementSwWorkShiftFormatter.php`

Updated:
- `Resources/views/swsettlement/print.blade.php`
- `Resources/views/swsettlement/show.blade.php`

This keeps view files smaller and easier to maintain, and moves lookup/formatting logic into a module-local service.

### 2. Work shift table now controlled by SettlementSW config
Updated:
- `Config/settlementsw.php`
- `Entities/SettlementSwWorkShift.php`

The physical work-shift table remains compatible with the existing ERP table, but the table name is now controlled by:

```php
config('settlementsw.tables.work_shifts')
```

This keeps future Settlement SW migration/separation controlled inside the module only.

## Validation
- PHP syntax lint passed for modified PHP files.
- No outside modules changed.
