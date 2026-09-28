# Communication Hub Functional Workflow V4

This package continues the Communication Hub professional UI and operational workflow build.

## Included improvements
- Keeps the approved professional dashboard style.
- Keeps prominent cards with visible gaps between boxes.
- Retains tenant database and business scope guards.
- Retains route aliases for older dashboard links.
- Expands the day-to-day user operation guide.
- Keeps SQL deployment standard without procedures, definers, delimiters, triggers, or routines.

## Deployment
Deploy only:

`Modules/CommunicationHub/`

Run tenant SQL if not already run:

`Modules/CommunicationHub/Database/SQL/00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql`

Then run:

`php artisan optimize:clear`
