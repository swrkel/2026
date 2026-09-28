# StockTransfer-New STN_023 - Maintenance Control Guide

This parcel adds a small post-live control area for administrators and testers.

## Purpose
- Track open post-live tasks.
- Record follow-up and status changes.
- Plan maintenance work by due date.
- Keep support work separated from the core transfer workflow.

## Install
1. Copy changed files.
2. Run `23_TENANT_MAINTENANCE_CONTROL_STOCK_TRANSFER_NEW.sql` in each tenant database that uses StockTransfer-New.
3. Include `Routes/admin_maintenance.php` from the module route loader if your module loader does not auto-load route fragments.
4. Assign the new permissions to the relevant roles.

## Notes
This feature is standalone inside `StockTransferNew` and does not create product, POS, inventory, or SMS duplicate tables.
