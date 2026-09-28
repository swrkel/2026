SIMPLE AUDIT v1.0.2 - phpMyAdmin SQL INSTALLATION
==================================================

IMPORTANT
---------
The Simple Audit tenant tables must be installed in EACH TENANT DATABASE.
Do NOT run the tenant installer while information_schema, mysql,
performance_schema, sys, or the central/master database is selected.

CORRECT ORDER IN phpMyAdmin
---------------------------
1. In the left sidebar, click the actual TENANT database name.
2. Confirm that tenant database is shown as the selected database.
3. Import: simple_audit_tenant_install.sql
4. Keep the SAME tenant database selected.
5. Import: simple_audit_verify.sql
6. At the end of verification, check verification_result.
   Expected result:
     PASS - Simple Audit database objects are installed
7. The summary must also show:
     simple_audit_tables_found   = 5
     simple_audit_tables_expected = 5
     simple_audit_triggers_found = 21
     simple_audit_triggers_expected = 21

v1.0.2 VERIFICATION FIX
-----------------------
The verification file is now fully read-only and only reads INFORMATION_SCHEMA.
It does NOT SELECT directly from sau_settings or any other sau_* table.
Therefore, if a Simple Audit table is missing or the wrong database is selected,
the file reports MISSING / WRONG DATABASE instead of failing with MySQL #1109.

IF THE VERIFIER SHOWS MISSING
-----------------------------
Do not create tables manually. Re-select the correct tenant database and import
simple_audit_tenant_install.sql first. Then run simple_audit_verify.sql again.

NEVER INSTALL SAU TABLES INTO
-----------------------------
- information_schema
- mysql
- performance_schema
- sys
- the central/master database
