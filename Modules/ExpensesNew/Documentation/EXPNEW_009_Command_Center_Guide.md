# EXPNEW_009 Command Center Guide

This parcel adds the standalone Enterprise Expense Command Center, Approval Workbench and Live Operations Board.

Deployment:
1. Copy files into the ExpensesNew module.
2. Run `01_Tenant_Create_Tables_EXPNEW_009.sql` on each tenant database.
3. Run `04_Tenant_Insert_Default_Data_EXPNEW_009.sql` on each tenant database. Inserts are written to avoid duplicates.
4. Include the CSS/JS assets in the module layout or existing module asset compiler.

No legacy Expenses module files are changed.
