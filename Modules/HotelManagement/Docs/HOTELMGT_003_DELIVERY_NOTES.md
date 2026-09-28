# HOTELMGT_003 Delivery Notes

## Scope completed
- Reservation page continued in POS module design standard.
- Reservation save now supports automatic reservation numbers when blank.
- Reservation can assign a room and rate plan during save.
- Assigned room is marked reserved while keeping tenant/business/location scoping.
- Reservation list now shows guest and room number information.
- Front Office check-in now updates reservation status and creates an open guest folio automatically.
- Check-out now updates room to available/dirty, updates reservation status, and closes open folio when applicable.
- Billing/Folio page improved with guest/reservation display, automatic folio numbers, quick payment posting, charge posting, and calculated balance.
- Added tenant-safe operational indexes for faster reservation, check-in, check-out and folio lookups.

## SQL files
- `Docs/HOTELMGT_003_SQL.sql` contains only the SQL related to this ZIP.
- `Docs/HOTELMGT_MASTER_SQL.sql` continues to contain the full cumulative SQL.
