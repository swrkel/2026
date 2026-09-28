# CUS_FINAL_001 - Customer Dependency Cleanup

## Purpose
This package performs a safe cleanup step after the Customers Module standalone audit.

## Changes Included

### 1. Customers permission ownership cleanup
`Modules/Customers/Services/CustomerPermissionService.php` now keeps `customers.*` permissions as the primary source.

Legacy `customer.*` and `contact.*` permission fallbacks were moved into a separate fallback map instead of being mixed into the primary Customers permission map.

### 2. Safe production fallback switch
`Modules/Customers/Config/config.php` now includes:

```php
'keep_legacy_contact_permission_fallbacks' => true,
'standalone_readiness_target' => 99,
```

Default is `true` so existing tenants continue working. After all roles are migrated to `customers.*` permissions, set this value to `false` to complete the permission separation.

## Why this is safe
- Existing users with legacy Contact permissions will continue to work.
- New Customers permissions are now the clean primary permissions.
- No database table changes.
- No ledger calculation changes.
- No Distribution Dealer Portal changes.
- No Petro/PetroPD/Finance changes.

## Files Changed
- `Modules/Customers/Services/CustomerPermissionService.php`
- `Modules/Customers/Config/config.php`

## Next Recommended Step
`CUS_OPT_001` - Customer Module performance and query optimization.
