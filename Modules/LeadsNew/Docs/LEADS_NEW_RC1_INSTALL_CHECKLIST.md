# Leads-New RC1 Install Checklist

1. Replace/merge the `Modules/LeadsNew` folder from this package.
2. Register the module provider only if your ERP does not auto-discover modules.
3. Run tenant migrations for `leads_new_*` tables.
4. Seed Leads-New permissions.
5. Enable Leads-New for the business from Super Admin module settings.
6. Give role permissions to selected users.
7. Clear application, route and view cache.
8. Open Leads-New Dashboard and verify sidebar visibility.
9. Test Add/Edit/View/Delete Lead.
10. Test Follow-ups, Notes, Activities, Reports and Settings.

No existing Leads module files should be replaced by this package.
