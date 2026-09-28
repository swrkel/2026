# HOTELMGT_020 Delivery Notes

## Scope
- Added Data Integrity page for pre-testing and post-upload verification.
- Added tenant/business/location consistency checks for rooms, reservations, folios, charges, payments, POS, room service, housekeeping and maintenance.
- Added snapshot saving so the business can keep an audit trail before client testing.
- Corrected Hotel module nav wrapper so control links remain inside the POS-style navigation bar.

## SQL
- Use `Docs/HOTELMGT_020_SQL.sql` only when tenant databases already have parcels 001 to 019.
- Use `Docs/HOTELMGT_MASTER_SQL.sql` only for fresh tenants or tenants that missed earlier parcels.
