# Stock Transfer-New Production Deployment Checklist

## Before upload
- Confirm the existing Products module is installed and active.
- Confirm every tenant database has locations and stores configured.
- Confirm the module is enabled in modules_statuses.json.

## SQL
Run SQL on every tenant database in the following order:
1. 01_MASTER_CREATE_TABLES_STOCK_TRANSFER_NEW.sql
2. 02_MASTER_ALTER_TABLES_STOCK_TRANSFER_NEW.sql
3. 03_MASTER_INSERT_PERMISSIONS_STOCK_TRANSFER_NEW.sql
4. 04_MASTER_INDEXES_STOCK_TRANSFER_NEW.sql

## After upload
- Clear config, route and view cache if required by the server process.
- Assign Stock Transfer-New permissions to roles.
- Open /stock-transfer-new/readiness.
- Test create -> approve -> dispatch -> receive -> report flow.
- Test product lookup from the Products module bridge.
