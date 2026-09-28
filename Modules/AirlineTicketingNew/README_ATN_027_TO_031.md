# ATN-027 to ATN-031 Enterprise Parcel

## ATN-027
Provider-independent GDS adapter contracts, DTOs, provider manager, provider settings and request logs.

## ATN-028
Flight schedules, aircraft references, disruption tracking and urgent operational task creation.

## ATN-029
Document storage, ownership, expiry, confidentiality and version history.

## ATN-030
Workflow definitions, instances, approval processing and event-based workflow start.

## ATN-031
Feature switches, encrypted API credentials and enterprise administration routes.

## Installation
1. Merge over ATN-001 through ATN-026.
2. Add the five route includes from `Routes/ATN_027_TO_031_WEB_ROUTE_PATCH.php`.
3. Run the migration or `Database/SQL/MASTER/MASTER_ATN_027_TO_031_ENTERPRISE_PARCEL.sql`.
4. Register the GDS provider manager and concrete provider adapters in the module service provider.
5. Publish the included CSS/JS and merge the language file.
6. Assign the included permissions.
7. Run `php artisan optimize:clear`.

No specific GDS vendor is hard-coded. Provider credentials are encrypted at rest through Laravel model casts.
