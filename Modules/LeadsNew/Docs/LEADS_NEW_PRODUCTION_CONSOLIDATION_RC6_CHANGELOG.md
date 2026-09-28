# Leads-New Production Consolidation RC6

## Scope
RC6 continues the production-completion cycle for the standalone Leads-New module.

## Main focus
- Keep the module isolated under `Modules/LeadsNew`.
- Keep route loading module-owned; no main route replacement is required.
- Continue Communication Hub design alignment.
- Add release validation support table for server testing and audit notes.
- Add deployment manifest and quality checklist.
- Maintain RC-specific SQL plus cumulative MASTER SQL.

## Deployment
Replace only `Modules/LeadsNew`, run the RC6 SQL on the active tenant database, then clear cache.

## SQL packaging
- `RC6/` contains only RC6 SQL.
- `MASTER/` contains cumulative SQL through RC6.
