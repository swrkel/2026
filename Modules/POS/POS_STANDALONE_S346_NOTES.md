# POS Standalone Module – S346

## Scope completed
- Converted POS layout to standalone HTML shell; it no longer extends `layouts.app` from the main system.
- Added module-owned asset delivery route/controller for POS CSS/JS from `Modules/POS/Resources`.
- Updated POS service provider to load all POS route files instead of only `web.php`.
- Removed duplicate shifts route from `pos006_010.php` to avoid route name collision.
- Fixed SettingsController view namespace typo from `settingss.*` to `settings.*`.
- Added standalone POS master tables in the core migration: `pos_categories`, `pos_brands`, `pos_products`, `pos_customers`.
- Updated POS Sales Workspace product/customer search to use POS-owned tables only (`pos_products`, `pos_customers`, `pos_categories`, `pos_brands`).
- Updated POS Cart price lookup to use `pos_products.sell_price` instead of main-system `variations`.

## Important replacement instruction
Replace the full `Modules/POS` folder with this folder.

## SQL note
A raw SQL file is included at `Modules/POS/Database/SQL/pos_standalone_s346.sql` for global execution in each tenant database.
