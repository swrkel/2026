Distribution New Stage 5 (DISNEW_005)

This package extends the standalone Distribution New module with route/territory planning, sales rep assignment, customer self-order access and delivery proof.

Install:
1. Replace/add the DistributionNew module files from this package.
2. Run Database/SQL/DISNEW_005_ROUTES_SALESREP_CUSTOMER_DELIVERY.sql in every tenant database that uses this module.
3. For new tenants, DISNEW_MASTER.sql contains all SQL from DISNEW_001 to DISNEW_005.
4. Clear Laravel cache/routes/views as normally done in your deployment process.
