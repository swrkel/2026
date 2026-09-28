# DISNEW 016 Production Audit Checklist

1. Confirm Distribution New sidebar is visible.
2. Confirm dashboard opens without 404.
3. Confirm Sales Orders, Invoices, Loading, Unloading, Vehicles, Reports pages open.
4. Confirm each tenant database has all `disnew_` tables.
5. Confirm each business respects Super Admin limits.
6. Confirm SMS events use the bridge to existing SMS module.
7. Confirm customer lookups use the existing Customer module service/lookup layer.
8. Confirm no old Distribution module classes are required by this module.
9. Confirm POS-style UI buttons, cards, tables and filters are applied.
10. Confirm rollback SQL is stored safely but not run unless required.
