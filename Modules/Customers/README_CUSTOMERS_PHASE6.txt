Customers Standalone Module - Phase 6

Completed safely without removing or changing Contacts module files.

Phase 6 additions:
- Keeps all customer records centralized under the main/head office business_id.
- Adds branch/location assignment support when the existing contacts table has business_location_id or location_id.
- Adds branch/location filter to the standalone Customers register.
- Adds branch/location to CSV export.
- Adds branch/location display in customer profile page.
- Keeps compatibility with existing contacts table and old Contacts module.
- If the contacts table has no supported branch/location column, the module continues working and shows a safe note.

No database migration is included in this phase because no existing Contacts data should be changed without approval.
