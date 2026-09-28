# Leads-New Production Consolidation RC5

## Scope

RC5 continues the production consolidation cycle for the Leads-New standalone module.

## Runtime changes

- Added tenant-safe model classes for saved filters, UI preferences, bulk action logs, and module system checks.
- Kept all Leads-New routing inside the module; no main route changes are required.
- Continued Customers-style standalone bootstrapping and module-only replacement strategy.
- Preserved backward-compatible route/view/lang aliases to avoid breaking cached sidebar links during deployment.

## Database changes

The matching SQL package contains:

- `RC5/` folder with SQL related only to this RC5 package.
- `MASTER/` folder with cumulative SQL up to RC5.

Run SQL on the active tenant database only.
Do not run on the central database.
No database name is hardcoded in the SQL files.

## Deployment

1. Replace only `Modules/LeadsNew`.
2. Run the SQL from `RC5/` for existing tenants that already reached RC4.
3. For a fresh tenant, run the SQL from `MASTER/`.
4. Open `/clear`.
5. Test `/leads-new`, `/leads-new/leads`, `/leads-new/leads/create`, `/leads-new/reports`, and `/leads-new/settings`.
