# Petro General - Petro Dashboard (Standalone)

This page is intentionally separate from the existing Petro General `Dashboard` page.

## URL
`/petro-general/petro-dashboard`

## Route name
`petrogeneral.petro_dashboard.index`

## Standalone files
- `Http/Controllers/PetroDashboard/PetroDashboardController.php`
- `Services/PetroDashboard/PetroDashboardService.php`
- `Resources/views/petro_dashboard/index.blade.php`
- `Resources/assets/css/petro-dashboard.css`
- `Resources/assets/js/petro_dashboard/petro-dashboard.js`

The controller/service/view do not reference `Modules\\Petro`, Petro views, Petro translations, or utilities/controllers/models in any other feature module.

The tank balance calculation reproduces the legacy Petro dashboard logic using Petro General's own service and database queries:

`Purchases - Sales + Transfers In - Transfers Out + Stock Adjustments`

The page also reproduces the optional general-message setting by reading the `system` table directly rather than calling a shared transaction utility.

No SQL or migration is required.
