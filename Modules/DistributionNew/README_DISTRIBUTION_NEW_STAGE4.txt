Distribution New - Stage 4 (DISNEW_004)

Scope:
1. Vehicles page for each business.
2. Add/Edit Vehicle with driver/helper/capacity/status.
3. Super Admin vehicle limit setting per business.
4. Create vehicle blocked when allowed count is reached.
5. Vehicle IDs prepared for loading, loading plans, and vehicle stock ledgers.

SQL:
- Database/SQL/DISNEW_004_VEHICLES_AND_SUPERADMIN_LIMIT.sql
- Database/SQL/DISNEW_MASTER.sql updated with Stage 1-4 SQL.

Install:
- Replace/add the DistributionNew files.
- Execute only DISNEW_004 SQL for tenants already updated to Stage 3.
- For a fresh tenant, execute DISNEW_MASTER.sql.
