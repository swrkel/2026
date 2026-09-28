-- Stock Transfer-New Master SQL Index
-- Run the tenant SQL files on every tenant database.
-- Keep the master database only for central module registration if your ERP deployment uses central registration.
SOURCE 01_MASTER_CREATE_TABLES_STOCK_TRANSFER_NEW.sql;
SOURCE 02_MASTER_ALTER_TABLES_STOCK_TRANSFER_NEW.sql;
SOURCE 03_MASTER_INSERT_PERMISSIONS_STOCK_TRANSFER_NEW.sql;
SOURCE 04_MASTER_INDEXES_STOCK_TRANSFER_NEW.sql;
