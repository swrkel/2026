# Enterprise Framework Complete Release

This package consolidates EFW001 through EFW005 into one standalone module package.

## Module
`Modules/EnterpriseFramework`

## Purpose
Provides reusable enterprise infrastructure for reporting modules while keeping operational modules independent.

## Included Areas
- Shared Report Engine
- Dashboard Engine
- Filter Engine
- Export and Print Engines
- Drill-down Engine
- Scheduler definitions
- Notification and alert services
- Report registry
- Global report search
- Saved filters and favourite reports
- Widget and UI component registry
- Report administration services
- Security and permission services
- Read-only reporting guard
- Audit logger
- Framework health checks
- Performance/cache/index recommendation services
- Finance Reports adapter contract/example

## Safety Rules
- No existing Finance module files are replaced.
- No operational accounting posting logic is changed.
- Framework services are infrastructure only.
- Reporting modules connect through adapters/contracts.
- Read-only reporting guard is included for protection.

## Suggested Installation
1. Backup current source code.
2. Copy `EnterpriseFramework` folder into `Modules/`.
3. Register the module if your Laravel module loader requires manual registration.
4. Clear Laravel cache/routes/views if needed.
5. Open the Enterprise Framework health/status pages after login.
6. Then connect Finance Reports through the adapter/service provider pattern.

## Notes
This is intended to support Finance Reports and future reporting modules such as PetroPD Reports, Distribution Reports, Membership Reports, Customers Reports, MyHealth Reports, Inventory Reports, and other standalone report modules.
