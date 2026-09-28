Distribution New Stage 11

Purpose:
This parcel strengthens the module for testing and tenant rollout by adding role-specific dashboards, notification preferences, reusable advanced filters, seeded permissions, deployment logs and rollback SQL.

Install:
1. Replace/merge the DistributionNew folder.
2. Run Database/SQL/DISNEW_011_STAGE_SQL.sql in each tenant database.
3. Keep Database/SQL/DISNEW_MASTER_SQL.sql as the consolidated reference.
4. If required, rollback only Stage 11 using Database/SQL/DISNEW_011_ROLLBACK_SQL.sql.

No existing POS, SMS, Customers, or Distribution module files are overwritten.
