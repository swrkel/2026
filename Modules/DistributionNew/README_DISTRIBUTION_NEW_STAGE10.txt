Distribution New - Stage 10 Installation / Verification Guide

1. Upload/replace the DistributionNew module folder.
2. Run SQL in Database/SQL/DISNEW_010_STAGE10_STANDALONE_AUDIT.sql for each tenant database.
3. If applying from the beginning, use Database/SQL/DISNEW_MASTER.sql.
4. Clear Laravel cache after upload:
   php artisan optimize:clear
5. Confirm the module is visible in sidebar based on permissions.
6. Open Distribution New > Audit & Health Check.
7. Run checks for:
   - Required disnew_ tables
   - Standalone file locations
   - Permissions
   - Route names
   - POS-style CSS/JS assets
   - Customer bridge availability
   - SMS bridge availability
8. Fix any missing registration warnings shown in the audit page.

Stage 10 does not duplicate the existing SMS module or Customer module. It only uses bridge/service contracts so Distribution New can remain standalone while still integrating cleanly.
