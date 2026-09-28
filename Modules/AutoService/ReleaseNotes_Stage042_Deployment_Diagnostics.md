# Auto Service Stage 042 – Deployment Diagnostics & Server Rollout Hardening

This package adds a read-heavy Deployment Diagnostics Centre to support server testing and tenant rollout.

## Included
- Deployment Diagnostics page
- Route/menu validation
- Tenant table/scope validation
- Business/location scope checks
- Open issue, pending job, completed job, and unpaid invoice counters
- Optional diagnostic run history table
- New permission seed: `autoservice.deployment_diagnostics.view`
- New raw SQL: `43_AUTOSERVICE_STAGE042_DEPLOYMENT_DIAGNOSTICS.sql`
- Master SQL updated up to Stage 042

## Notes
Run the SQL against each tenant database. The diagnostics page still displays live checks even if the optional diagnostic history table is not yet created.
