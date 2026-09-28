Distribution New Stage 9
Apply Database/SQL/DISNEW_009_stage9.sql to each tenant database that will use Distribution New.
For new tenants, use Database/SQL/DISNEW_MASTER.sql.
Register Routes/stage9.php and Routes/api.php from the Distribution New module service provider.
This package keeps all new tables prefixed with disnew_ and all logic inside Modules/DistributionNew.
Existing Customers and SMS modules remain external integrations through clean bridge/service layers only.
