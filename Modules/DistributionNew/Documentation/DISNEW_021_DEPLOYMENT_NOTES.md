# DISNEW_021 Deployment Notes

This package is a stage parcel only. Upload/merge into the existing `Modules/DistributionNew` folder after the full replacement package and stages 13-20.

Use `SQL/DISNEW_021_production_completion.sql` for tenant databases where you apply raw SQL manually. Use the migration file only if your deployment process runs Laravel migrations.

No database name is hardcoded, so the SQL can be run against each tenant database selected in your SQL client.
