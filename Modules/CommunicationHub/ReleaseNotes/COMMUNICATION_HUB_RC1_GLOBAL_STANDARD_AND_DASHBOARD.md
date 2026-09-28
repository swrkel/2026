# Communication Hub RC1 - Global ERP Standard & Dashboard Refinement

## Main updates

- Dashboard refined to the official ERP Dashboard Standard.
- KPI area uses 4-column desktop layout.
- KPI cards are clickable and act as dashboard shortcuts.
- Added analytics, recent activity and provider status panels.
- Added Global ERP Development Standard document.
- Preserved tenant database and business-scope safeguards.
- No stored procedures, triggers, definers or delimiter blocks are required in SQL.

## Deployment

Deploy:

- `Modules/CommunicationHub/`
- `Modules/CoreUI/` if CoreUI is not yet deployed or not enabled.

Then run:

```bash
php artisan optimize:clear
```

Make sure `modules_statuses.json` contains:

```json
"CommunicationHub": true,
"CoreUI": true
```
