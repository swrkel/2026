# Leads-New Architecture Alignment V12

## Runtime changes
- Removed generic `module.json` from the replacement package because this ERP uses `modules_statuses.json` and existing standalone modules do not depend on module.json.
- Added inline Communication Hub style guard so the redesigned UI appears even when module assets are not published under public.
- Added reusable flash/error/setup partials.
- Cleaned Leads register record count display.
- Cleaned Add Lead setup warning so tenant table names are not exposed to end users.
- Added missing language keys for form labels and validation display.

## Deployment
- Replace only `Modules/LeadsNew`.
- Run the V12 raw SQL package on the active tenant database only.
- Open `/clear`.
- Test `/leads-new`, `/leads-new/leads`, `/leads-new/leads/create`.
