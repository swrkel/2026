# STN_036 Dock & Capacity Planning

Adds standalone dock scheduling and dispatch capacity planning for StockTransfer-New.

Run tenant SQL in this order:
1. STN_036_CREATE_TABLES.sql
2. STN_036_ALTER_TABLES.sql
3. STN_036_INSERT_PERMISSIONS.sql

No product master tables are created. Product details remain sourced from the existing Products module bridge.
