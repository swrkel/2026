# Membership New — POS UI / Report Tools Update — 23 Sep 2026

## UI standard
- Membership New remains standalone; no POS files are imported or referenced.
- Module layout now follows the POS/Communication Hub visual standard with hero header, navigation, cards, panels, forms, tables, buttons, spacing and responsive behavior.
- The design is embedded from Membership New's own Blade partials so public asset publishing/version drift cannot leave the module on the old design.

## Report tools
Reusable report toolbar added with:
- Global Search
- Transactions per page: 10 / 25 / 50 / 100 / 200
- Export CSV
- Export Excel
- Column visibility
- Print
- PDF / browser Save as PDF
- Email share
- WhatsApp share

## Scope
The toolbar is applied to the dedicated Membership New reports and report/ledger/audit pages including member balances, point ledger, share register, dividend register, business balances, business statement, business history, point transactions/member ledger, dividend payouts, audit logs, error logs and final standalone audit.

## Architecture / safety
- Existing multi-tenant database selection is untouched.
- Existing business_id scoping is retained and server-side report searches remain business-scoped.
- No database schema changes or migrations are required for this UI/report-tool update.
- Previous 404 route repair remains included.
