AutoService Full Code Package up to Stage 035

Contents:
- Full AutoService module code as of Stage 035 Advanced Vehicle History.
- All SQL scripts from Stage 020 through Stage 035.
- Consolidated master SQL: AutoService/SQL/00_MASTER_AUTOSERVICE_ALL_SQL_STAGE020_TO_STAGE035.sql
- Incremental SQL copies: AutoService/SQL/STAGE020_TO_STAGE035_INCREMENTAL_SQL/

Deployment summary:
1. Backup code and tenant databases.
2. Replace the AutoService module folder with this AutoService folder.
3. Run the consolidated master SQL on each tenant database, or run incremental scripts in numeric order.
4. Clear Laravel cache/config/routes/views as per your normal server process.
5. Test module menu, permissions, business/location filters, job workflow, customer portal, billing, reports, and advanced vehicle history.

Important:
- This package is intended for Laravel multi-tenant single-code / multiple-database deployment.
- SQL is tenant-database SQL and must be applied to each tenant database that uses Auto Service.
