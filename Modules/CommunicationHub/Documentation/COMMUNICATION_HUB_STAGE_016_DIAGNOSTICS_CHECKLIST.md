# Communication Hub Stage 016 - Server Diagnostics Checklist

## Purpose
This stage adds a tenant-safe Diagnostics Centre to help identify upload, SQL, queue, provider, OTP and business-scope issues after the module is deployed to the server.

## New page
- URL: `/communication-hub/diagnostics`
- Route: `communicationhub.diagnostics.index`
- Permission: `communicationhub.diagnostics.view`

## What to check after upload
1. Run `17_SERVER_DIAGNOSTICS_AND_ROLLOUT_SUPPORT.sql` in each tenant database where Communication Hub is enabled.
2. Clear Laravel route/config/view cache.
3. Open `/communication-hub/readiness-check` first and confirm core tables and routes are ready.
4. Open `/communication-hub/diagnostics` and check:
   - tenant database name
   - current business ID
   - required table existence
   - message status counts
   - OTP status counts
   - provider status counts
   - failed and pending messages
5. If pending/failed counts are high, verify provider credentials, queue processor and business/location scope.

## Multi-tenant note
The SQL does not include a database name. Run it after selecting each tenant database.
