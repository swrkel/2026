# Leads-New Architecture Alignment V9

## Purpose
This parcel continues the Customers-style architecture alignment and functional hardening after the module started loading successfully on the server.

## Main fixes
- Added missing operational tenant table migration for `leads_new_activities` and related master tables.
- Added raw SQL scripts for tenant database execution.
- Changed database setup warning so end users no longer see internal table names.
- Improved dashboard/list visual style to better match the Communication Hub design standard.
- Kept route links URL-based in module views to avoid route-cache/name mismatch errors while route registration stabilizes.

## Files changed
- `Modules/LeadsNew/Services/LeadsNewTableGuard.php`
- `Modules/LeadsNew/Resources/views/dashboard/index.blade.php`
- `Modules/LeadsNew/Resources/views/leads/index.blade.php`
- `Modules/LeadsNew/Resources/views/leads/create.blade.php`
- `Modules/LeadsNew/Resources/lang/en/messages.php`
- `Modules/LeadsNew/Resources/assets/css/leads_new.css`
- `Modules/LeadsNew/Database/Migrations/2026_07_04_000001_create_leads_new_operational_core_tables.php`
- `Modules/LeadsNew/SQL/V9/001_create_missing_operational_core_tables.sql`
- `Modules/LeadsNew/SQL/V9/002_default_master_data.sql`
- `Modules/LeadsNew/SQL/V9/README.txt`

## Deployment
1. Replace only `Modules/LeadsNew`.
2. Run the SQL scripts in `SQL/V9` on the active tenant database.
3. Open `/clear`.
4. Test `/leads-new`, `/leads-new/leads`, and `/leads-new/leads/create`.
