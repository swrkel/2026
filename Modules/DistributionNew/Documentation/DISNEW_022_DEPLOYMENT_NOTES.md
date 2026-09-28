# DISNEW 022 Deployment Notes

1. Upload/replace the included DistributionNew files.
2. Run `DistributionNew/SQL/DISNEW_022_operational_polish.sql` on every enabled tenant database.
3. If you use Laravel migrations, run the included stage 22 migration.
4. Enable the new permissions from Super Admin/Manage or through your permission seeder.
5. Test URLs:
   - `/distribution-new/operational-polish/profitability`
   - `/distribution-new/operational-polish/collection-controls`
   - `/distribution-new/operational-polish/reconciliation-exceptions`
   - `/distribution-new/operational-polish/deployment-verification`
