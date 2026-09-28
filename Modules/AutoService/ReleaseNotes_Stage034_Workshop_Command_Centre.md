# AutoService Stage 034 - Workshop Command Centre 2.0

## Included
- Real-time Workshop Command Centre dashboard.
- Live flow counts: waiting, inspection, approval, parts, repair, QC, ready and delivered today.
- Delayed job indicators using promised date / expected completion / last update threshold.
- Technician workload summary.
- Bay occupancy summary.
- Today attention counters.
- Auto-refresh JSON endpoint for live tiles.
- Business/location-safe data scoping.
- New permissions for command centre view/live endpoint.
- Raw SQL: `35_AUTOSERVICE_STAGE034_WORKSHOP_COMMAND_CENTRE.sql`.

## Replacement Files
- `AutoService/Http/Controllers/CommandCentreController.php`
- `AutoService/Services/AutoServiceCommandCentreService.php`
- `AutoService/Resources/views/command_centre/index.blade.php`
- `AutoService/Resources/assets/css/autoservice.css`
- `AutoService/Routes/web.php`
- `AutoService/Permissions/autoservice_permissions.php`
- `AutoService/version.json`
