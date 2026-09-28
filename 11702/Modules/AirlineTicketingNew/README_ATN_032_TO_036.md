# ATN-032 to ATN-036 Enterprise Parcel

## ATN-032
Customer self-service portal users, dashboard metrics and service-request structure.

## ATN-033
B2B travel-agent master, credit fields and protected wallet ledger.

## ATN-034
Versioned public REST API, API-key middleware and business-scoped reservation/ticket endpoints.

## ATN-035
Mobile-ready JSON dashboard services for reservations, tickets, tasks and workflows.

## ATN-036
Daily analytics snapshots and baseline sales forecasting service.

## Installation
1. Merge over ATN-001 through ATN-031.
2. Add the five route includes from `Routes/ATN_032_TO_036_WEB_ROUTE_PATCH.php`.
3. Register the `atn_portal` auth guard/provider and `atn.api.key` middleware alias.
4. Run the migration or `Database/SQL/MASTER/MASTER_ATN_032_TO_036_ENTERPRISE_PARCEL.sql`.
5. Publish the included CSS/JS and merge the language file.
6. Assign included permissions.
7. Run `php artisan optimize:clear`.

The public API remains tenant/business scoped and never returns another business's records.
