# SW_AUDIT_007 - Settlement SW Config / Label Runtime Cleanup

## Purpose
This pass continues the standalone separation after `SW_AUDIT_006` by removing remaining active-code wording/config hard-coding that still made the Settlement SW module look tied to old Petro runtime flags.

## Changes included

### 1. Product module key moved to Settlement SW config
The historic product classification key is now read through:

```php
config('settlementsw.product_module_key', 'petro_settlements')
```

This keeps existing tenant/product data compatible while giving Settlement SW a module-owned config point for any future database or product-category migration.

Updated active controller usages in:
- `Http/Controllers/SettlementSwBaseController.php`
- `Http/Controllers/SettlementSwAddPaymentBaseController.php`

### 2. Subscription guard moved to module-owned helper
Added a local helper method:

```php
hasSettlementSwSubscription($business_id)
```

It checks the new Settlement SW subscription key first:

```php
enable_settlement_sw_module
```

Then falls back to the old existing key only for backward compatibility:

```php
enable_petro_module
```

This avoids hard-coding the Petro flag inside the payment controller while preserving current installations until the new Settlement SW subscription flag is seeded/enabled.

### 3. Config keys added
Updated `Config/settlementsw.php` with:
- `product_module_key`
- `subscription_permission_key`
- `legacy_subscription_permission_key`

### 4. Breadcrumb label cleanup
Replaced remaining active breadcrumb labels that displayed Petro with Settlement SW wording.

Updated:
- `Resources/views/swsettlement/index.blade.php`
- `Resources/views/swsettlement/create.blade.php`
- `Resources/lang/en/lang.php`

## Audit checks
- PHP syntax audit passed.
- No active `/petro` URL references found in runtime code.
- No active `petro::lang` translation references found in runtime code.
- No active `Modules\\Petro` namespace references found in runtime code.
- ZIP integrity passed.

## Notes
Some database table names and field names still contain historical `petro_*` wording because the existing ERP schema appears to store shared shift/settlement source data in those tables. This package does not force a database migration, to avoid breaking current tenant data.
