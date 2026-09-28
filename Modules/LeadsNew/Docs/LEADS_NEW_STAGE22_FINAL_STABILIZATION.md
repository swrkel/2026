# Leads-New Stage 22 - Final Stabilization

This parcel adds a server-side health check and release validation support for the standalone Leads-New module.

## New verification URL

After deployment, open:

`/leads-new/health-check`

The page checks required Leads-New tenant tables and shows a practical server test checklist.

## Intended use

Use this package after installing the latest Leads-New module files. It helps confirm whether migrations, permissions, sidebar access, CRUD, reports, and multi-tenant isolation are ready for live server testing.

## Important

This parcel does not modify the existing Leads module business logic. The new code is inside `Modules/LeadsNew` only.
