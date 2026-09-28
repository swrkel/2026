# ATN-022 to ATN-026 Enterprise Parcel

Sections:
1. Accounting mapping and balanced journal posting
2. Multi-currency rates, conversion and revaluation
3. BSP periods, ADM/ACM adjustment structure and reconciliation
4. CRM interactions and loyalty points ledger
5. Management dashboard KPIs

Installation:
- Merge over ATN-001 through ATN-021.
- Include Routes/enterprise.php inside the authenticated module route group.
- Run the migration or MASTER_ATN_022_TO_026_ENTERPRISE_PARCEL.sql.
- Assign included permissions.
- Publish enterprise CSS/JS.
- Run php artisan optimize:clear.
