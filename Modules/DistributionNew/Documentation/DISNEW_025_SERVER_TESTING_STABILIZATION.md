# Distribution New DISNEW_025 - Server Testing Stabilization

This parcel adds a real UI-visible Server Testing Centre for Distribution New.

## URL
`/distribution-new/server-testing`

## Purpose
- Check required `disnew_` tables.
- Check important UI route names.
- Check business isolation issues where `business_id` is missing.
- Check negative warehouse/vehicle stock rows.
- Check SMS bridge table availability without duplicating the existing SMS module.
- Save testing run logs in `disnew_server_testing_runs`.

## Install
1. Upload the files.
2. Run the stage SQL on each tenant DB or run the Laravel migration.
3. Clear Laravel config/route/view cache if required.
4. Enable the new permissions for testing users.
5. Open `/distribution-new/server-testing` and press **Run Full Check**.
