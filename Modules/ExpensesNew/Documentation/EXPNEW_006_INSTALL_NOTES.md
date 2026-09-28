# EXPNEW_006 Install Notes

1. Upload this parcel over EXPNEW_001 to EXPNEW_005.
2. Include `Routes/web_expnew006.php` from the module route service provider or module loader.
3. Run `Database/sql/EXPNEW_006_analytics_intelligence_reports.sql` on each tenant database.
4. Re-run insert scripts safely; default inserts are guarded with `WHERE NOT EXISTS`.
5. Clear route/view/cache after deployment if required by your hosting panel.
