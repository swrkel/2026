Distribution New Stage 6 Installation Notes

1. Upload/replace the DistributionNew module files.
2. Run Database/SQL/DISNEW_006_COLLECTIONS_SETTLEMENTS_PORTALS_REPORTS.sql in each tenant database.
3. If applying from a fresh tenant, run Database/SQL/DISNEW_MASTER.sql instead.
4. Clear Laravel route/config/view cache if your deployment process requires it.
5. Confirm permissions in Super Admin / Manage page.

Main new URLs:
- /distribution-new/collections
- /distribution-new/settlements
- /distribution-new/customer/orders
- /distribution-new/sales-rep-portal
- /distribution-new/reports/operational/daily
- /distribution-new/reports/operational/vehicle-stock
