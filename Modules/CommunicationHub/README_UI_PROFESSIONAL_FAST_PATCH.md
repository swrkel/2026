# Communication Hub Professional UI Fast Patch

Deploy only:
- Modules/CommunicationHub/

Purpose:
- Makes Communication Hub pages cleaner, less packed, and closer to the professional Accounting module layout.
- Adds a unified enterprise header, KPI card style, clean data tables, consistent spacing, rounded panels, toolbar styling, and empty states.
- Does not change business logic or database logic.

After upload:
php artisan optimize:clear

Notes:
- SQL is unchanged in this UI patch.
- Existing tenant database/business scope fixes remain included from the previous baseline.
