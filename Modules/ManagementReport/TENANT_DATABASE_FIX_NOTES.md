# Management Report Dynamic Tenant Database Fix

## Root cause

The earlier route provider loaded Management Report routes with `web` and `auth`, but without `tenant.context`. Therefore the request reached the controller while Laravel was still connected to the central database. Eloquent then queried `nivasa_base.mgmt_report_runs`.

## Corrections

1. Added `tenant.context` before `auth` to all private Management Report routes.
2. Added `tenant.context` to public downloadable report links.
3. Added a strict tenant connection resolver that validates the actual database against the currently initialized tenant.
4. Added a tenant base model used by all seven Management Report entities.
5. Changed direct query-builder, schema and transaction operations to the tenant connection.
6. Prevented module migrations from auto-running through the central migration path.
7. Added `management-report:install-tenants` to create tables in all dynamic tenant databases.
8. Replaced the base-database SQL with repeat-safe SQL that contains no fixed database name and must be run in the selected tenant database.
9. Added a missing-table middleware that reports the tenant database name instead of exposing a raw SQL exception.

## IS2314 addendum — central-business databases (23 Sep 2026)

The tenant-only rule above was too strict for installations where one or more businesses intentionally operate inside the configured central-domain database. IS2314 adds a third supported mode: configured central-domain business database. The module now keeps that database active, continues strict `business_id` scoping, and creates only missing module-owned `mgmt_*` tables there when required. Dynamic tenant and copied standalone tenant behavior remains unchanged.
