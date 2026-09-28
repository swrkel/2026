# DISNEW_015 Testing Checklist

1. Run SQL/migration in tenant DB.
2. Confirm Advanced Analytics menu appears for users with permission.
3. Open Executive Dashboard.
4. Open KPI Dashboard.
5. Open Scheduled Reports.
6. Create a scheduled report and queue delivery.
7. Confirm disnew_report_delivery_logs gets queued rows.
8. Confirm SMS rows are only bridge rows and existing SMS module remains unchanged.
9. Confirm DataTables toolbar buttons are visible.
10. Confirm no non-disnew table was created by this parcel.
