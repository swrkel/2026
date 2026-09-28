# PriceChangeNew Sequence 01 — baseline 9773

This is a fresh module. No controller, service, Blade layout, route, asset, migration or business logic from the removed PriceChangeNew module was reused.

## Deliberately preserved

`Modules/PriceChanges` is not removed. In baseline 9773 it owns MPCS/F17/F22 functions and is directly imported by Accounts, Deposits, Bakery and Finance. Removing it would damage working areas.

## Sequence 01 scope

- Tenant-aware route and logged-in business boundary
- Permitted business-location boundary
- Dashboard
- Draft add/list/view/edit/delete
- Product and variation lookup
- Current purchase/selling/tax/stock snapshots
- Eight-decimal calculation and storage
- Audit trail
- No live price update

## Future sequences

Submission/approval, scheduled application, old-stock/new-stock pricing layers, reports and settings are intentionally not active yet.

## SQL safety

The master SQL is rerun-safe. It removes the incompatible legacy `PriceChangeNew` tables only when the fresh Sequence 01 schema has not yet been installed. After installation, the cleanup is skipped to preserve drafts and permission assignments.
