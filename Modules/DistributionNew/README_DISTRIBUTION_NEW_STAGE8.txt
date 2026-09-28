Distribution New Stage 8
Apply Database/SQL/DISNEW_008_stage8.sql to each tenant database that will use this module.
For new tenants, use Database/SQL/DISNEW_MASTER.sql.
Include Routes/stage8.php from the module route provider or merge its route registration into the Distribution New module provider.
This stage remains standalone under Modules/DistributionNew and uses disnew_ tables only.
Existing Customer and SMS modules are accessed only through clean service/bridge layers; no duplicate SMS module is created.
