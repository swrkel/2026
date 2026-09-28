# Leads-New Production Consolidation RC3

## Focus
- Tenant-safe query hardening for dashboard and lead register.
- Missing table completion for documents and settings.
- Follow-up table business/location column alignment.
- Communication-Hub style UI hardening continues inside the module assets.

## Module changes
- `Services/LeadsNewTableGuard.php`: added safe column guard for existing tenant databases.
- `Services/LeadsNewDashboardService.php`: avoids querying missing columns and keeps dashboard operational during staged SQL rollout.
- `Http/Controllers/LeadsNewLeadController.php`: adds tenant-aware lead listing without breaking old tenant databases.

## Database changes
Use the matching RC3 SQL package.
- RC3 folder: only changes related to this RC3 module ZIP.
- MASTER folder: cumulative SQL up to RC3 for fresh tenant databases.

## Deployment
1. Replace only `Modules/LeadsNew`.
2. Run RC3 SQL on the active tenant database.
3. Open `/clear`.
4. Test `/leads-new`, `/leads-new/leads`, `/leads-new/leads/create`.
