# SW-SEP-016 - Final Dependency Audit Cleanup

## Purpose
This stage continues Settlement SW standalone separation without changing working business logic.

## Changes
- Added more table ownership keys to `Config/settlementsw.php`.
- Extended `SettlementSwTables` to resolve shared/legacy table names centrally.
- Made module-local wrapper entities table-config aware.
- Replaced direct contact/contact-group join table literals in active SettlementSW controllers with the SettlementSW table resolver.
- Added `SettlementSwDependencyAudit` as a module-local read-only audit utility for future checks.

## Behaviour Safety
Physical table names remain unchanged by default. Existing tenant data and working Settlement SW flows are preserved. Future migration can change table names centrally in module config.

## Remaining Accepted Platform Boundaries
- Laravel framework
- Auth/session/tenant middleware
- Existing tenant database schema
- App model inheritance inside SettlementSW wrapper entities only

## Next Suggested Step
Run SW-AUDIT-001 to verify there are no active references to Petro, PetroPD, PetroDirect, Finance, Contacts, Superadmin, or VAT modules outside allowed compatibility comments/docs.
