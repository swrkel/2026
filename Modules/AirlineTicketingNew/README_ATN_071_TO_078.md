# ATN-071 to ATN-078 — 8 Section Parcel

## ATN-071 Executive Dashboard
KPI cards for sales, collections, refunds, profit, tickets and open tasks.

## ATN-072 Report Centre
Central report registry and saved-report support.

## ATN-073 Business Intelligence
Monthly trends and year-over-year comparison.

## ATN-074 Forecasting
Configurable forecast models and moving-average forecasting.

## ATN-075 Scheduled Reports
Report schedules, recipients, formats and next-run tracking.

## ATN-076 Report Builder
Safe allow-listed data sources and customizable report templates.

## ATN-077 KPI & Alert Centre
Threshold rules that create operational exceptions.

## ATN-078 Export Centre
CSV and JSON streaming exports with reusable column definitions.

## Installation
1. Merge over ATN-001 through ATN-070.
2. Include `Routes/reporting-enterprise.php` inside the authenticated module route group.
3. Run the migration or `MASTER_ATN_071_TO_078_8_SECTION_PARCEL.sql`.
4. Assign included permissions.
5. Publish the CSS/JS and merge the language file.
6. Run `php artisan optimize:clear`.
