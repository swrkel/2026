# Communication Hub Professional UI v2 + Route Fix

Deploy only `Modules/CommunicationHub/`.

Fixes:
- Redesigns `/communication-hub` dashboard with a cleaner enterprise SaaS layout.
- Makes KPI cards more prominent and better spaced.
- Adds structured overview, provider status, recent refills and daily summary panels.
- Fixes missing route `communicationhub.commercial.refills` by using existing route `communicationhub.commercial.credit_refills`.
- Does not change database schema or business logic.

After upload run:

```bash
php artisan optimize:clear
```
