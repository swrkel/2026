LEADS-NEW V9 SQL PACKAGE

Run these scripts only on the active TENANT database, not on the central database.

Recommended order:
1. 001_create_missing_operational_core_tables.sql
2. 002_default_master_data.sql

These scripts do not contain USE database_name statements, so they use whichever tenant database is selected in phpMyAdmin/Adminer.
