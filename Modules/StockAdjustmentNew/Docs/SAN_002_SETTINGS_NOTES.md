# S 577 - Stock Adjustment New Settings

## New page

`/stock-adjustment-new/settings`

The page contains:

1. Business-specific numbering, decimals and list defaults.
2. Workflow settings for reason/location/store/approval/auto-submit/auto-post.
3. Inventory controls for batch selection, zero-stock products, negative quantities, zero cost and date restrictions.
4. Accounting mappings by effective date, adjustment type, category and sub-category.

## Database

New standalone tables use the required `san_` prefix:

- `san_stock_adjustment_settings`
- `san_stock_adjustment_account_mappings`

Run either the migration or `SQL/11_Settings_Upgrade.sql` on every tenant database. `SQL/10_MASTER_INSTALL.sql` contains the full idempotent installation and upgrade set.

## Shared dropdowns

The page reads Categories and Finance accounts through `SettingsReferenceService`. It does not duplicate those common master tables and safely shows empty dropdowns if a shared module is not installed in a tenant.
