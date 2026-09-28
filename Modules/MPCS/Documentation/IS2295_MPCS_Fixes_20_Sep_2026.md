# IS2295 – MPCS – 20 Sep 2026

This parcel applies only to the MPCS module. No core/application files and no database schema are changed.

## Fixed items

1. **F 9C Credit form / F 9C Credit details**
   - Released the page/theme scroll locks on the F9C Credit page.
   - Keeps vertical page scrolling available after tab changes and DataTable redraws.
   - Keeps the report table horizontally scrollable when required without trapping vertical scrolling.

2. **F20 Form CDS / Print / Print preview**
   - Replaced the fragile inherited application print preview with an isolated print document.
   - Removed sidebar/header/tab/theme leakage from the print preview.
   - Removed the old 185% width + 0.54 zoom print workaround.
   - Print layout now follows the requested `Daily Report` layout: meter reading section, daily sales status and balance stock.
   - The saved F20 CDS print view is also standalone so application chrome cannot appear in preview.

3. **F20 Form / F20 Form settings**
   - Renamed headings to `Starting No (Total Sale)`, `Starting No (Cash Sale)` and `Starting No (Credit Sale)`.
   - Each heading is displayed in two rows (`Starting No` / `(Sale Type)`).
   - Reduced the three starting-number columns and fixed table percentages so the full settings table stays within the available screen width.

4. **F20 Form / Print / Preview**
   - Print now opens a clean report-only preview rather than printing page tabs and filter controls.
   - All configured product columns are forced visible in print preview, including products hidden on-screen because they had no sales for the selected filter.
   - Added a native Ctrl+P/popup-blocked fallback with the same report-only/all-products behavior.

## Database

No SQL or migration is required for IS2295.

## Deployment

Replace the MPCS module with this parcel and run:

```bash
php artisan optimize:clear
php artisan view:clear
```

Then hard-refresh the browser (Ctrl+F5) before testing the four IS2295 items.
