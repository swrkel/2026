Distribution New - Stage 1 Foundation

Created as a standalone Laravel module:
- Module name: DistributionNew
- URL prefix: /distribution-new
- Table prefix: disnew_
- Uses POS module UI style through module-local Blade partial.
- Own controllers, services, models, routes, views, JS, CSS, language, permissions, utilities, reports and SQL.

Included Stage 1 scope:
1. Dashboard route and POS-style dashboard layout.
2. Sales Order foundation: create, edit, list, show.
3. Sales Invoice from Sales Order foundation.
4. SMS queue log for customers and designated business officers.
5. Loading foundation and vehicle stock movement service.
6. Unloading / vehicle stock / store + vehicle stock route foundations.
7. Raw SQL and master SQL.

Important:
- This package intentionally avoids altering existing modules.
- Existing Customer module is used through a clean lookup service only.
- Existing SMS gateway sending can be attached by consuming disnew_sms_logs where status = queued.
