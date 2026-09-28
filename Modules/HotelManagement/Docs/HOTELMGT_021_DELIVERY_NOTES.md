# HOTELMGT_021 Delivery Notes

## Scope
Guest Communication bridge for Hotel Management.

## Added
- Guest Communication page with POS-style layout.
- Hotel-specific SMS/email/WhatsApp template storage.
- Hotel message queue/log table for existing ERP Communication/SMS integration.
- Manual message queue form.
- KPI cards for pending, sent and failed messages.
- Navigation link safety using route availability checks.

## SQL
- `Docs/HOTELMGT_021_SQL.sql` contains only SQL introduced in parcel 021.
- `Docs/HOTELMGT_MASTER_SQL.sql` is the cumulative master SQL up to parcel 021.

## Important
This parcel does not duplicate the ERP SMS module. It creates a clean Hotel Management bridge/log layer so the existing Communication/SMS service can pick up and send hotel messages.
