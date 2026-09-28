CUSTOMERS S526 - RAW SQL DEPLOYMENT
Date: 24 July 2026

Issue corrected
---------------
Credit sales already stored in transactions were not shown in:
1. Customers / Customer Register / Total Due
2. Customers / Customer Register / Action / Ledger

Deployment
----------
Run this file inside EACH tenant database:

00_MASTER_CUSTOMER_CREDIT_SALE_RECONCILIATION.sql

The SQL only adds optional performance indexes. It does not insert, update or
backfill business data. Historical credit sales become visible through the code
reconciliation immediately after deployment.

Safety
------
- Table existence is checked.
- Required column existence is checked.
- Named index existence is checked.
- Equivalent ordered-column indexes under another name are checked.
- DDL errors do not stop the remaining safe index checks.
- No INSERT statements are required.
