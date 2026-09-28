# Communication Hub 404 Fix V2

## Root cause corrected

The previous package added missing Email routes, but the full Communication Hub route group was still forced through Stancl domain-only tenant middleware:

- `InitializeTenancyByDomain`
- `PreventAccessFromCentralDomains`
- `ScopeSessions`

The host ERP can select the tenant database through the logged-in business/session and `tenant_db()`. On that architecture, `PreventAccessFromCentralDomains` can abort with HTTP 404 before the Communication Hub controller is reached.

## Fixes in V2

- Replaced the domain-only route restriction with module middleware that calls the host ERP `tenant_db()` helper when available.
- Keeps Communication Hub reachable on both the ERP central host and tenant hosts after login.
- Main service provider now registers the route provider itself, protecting against module loaders that only bootstrap the first provider.
- Added compatibility redirects for old dashboard URL variants.
- Retained V1 Email routes and menu corrections.

## Deployment

Copy the included `CommunicationHub` folder over `Modules/CommunicationHub`.

Confirm the root `modules_statuses.json` contains:

```json
"CommunicationHub": true
```

Then run:

```bash
php artisan optimize:clear
php artisan module:enable CommunicationHub
php artisan route:list --name=communicationhub
```

The route list must contain `communicationhub.dashboard` with URI `communication-hub`.

No migration or SQL is required for this routing correction.
