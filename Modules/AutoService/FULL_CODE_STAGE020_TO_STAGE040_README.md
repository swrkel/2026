# AutoService Full Code Package - Stage 020 to Stage 040

This package contains the consolidated AutoService module code up to Stage 040.

## Included
- Full AutoService module replacement folder
- Incremental SQL files from Stage 020 through Stage 040
- Consolidated master SQL:
  - `AutoService/SQL/00_MASTER_AUTOSERVICE_ALL_SQL_STAGE020_TO_STAGE040.sql`
- Final enterprise audit and validation SQL:
  - `AutoService/SQL/41_AUTOSERVICE_STAGE040_FINAL_ENTERPRISE_AUDIT.sql`

## Suggested deployment order
1. Backup current code and tenant databases.
2. Replace the current `Modules/AutoService` folder with this `AutoService` module folder, matching your ERP module path convention.
3. Execute `AutoService/SQL/00_MASTER_AUTOSERVICE_ALL_SQL_STAGE020_TO_STAGE040.sql` on each required tenant database.
4. Clear Laravel cache/routes/views/config as per your normal deployment process.
5. Enable/verify permissions and menu entries.
6. Test main workflows:
   - Reception / estimate / job card
   - Parts and labour
   - Service flow / QC / delivery
   - Billing/payment
   - Customer portal/status/bill/history
   - Vehicle history
   - Workshop planning/KPI/BI
   - Dealer/fleet/AMC
   - Final audit page

## Notes
- SQL is designed to be tenant-database executable without hardcoded database names.
- Keep this package as the clean replacement baseline up to Stage 040.
