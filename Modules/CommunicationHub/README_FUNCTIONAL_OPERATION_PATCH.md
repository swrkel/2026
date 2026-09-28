# Communication Hub Functional Operation Patch

This package improves day-to-day functionality while keeping the database and business architecture intact.

## Added / hardened
- SMS package toggle/delete actions.
- Client active/inactive toggle and delete action.
- API token active/inactive toggle and delete action.
- Manual wallet credit adjustments.
- Professional forms and action tables for key commercial pages.
- Day-to-day user operation guide.

## Deploy
Replace only `Modules/CommunicationHub/`.

Then run `php artisan optimize:clear`.

## SQL
No new schema is required in this patch. Existing tenant SQL remains under `Database/SQL/`.
