Customers Module Phase 10 - Standalone Customer Import

What was added:
1. Customer CSV import page at /customers/import
2. Downloadable CSV template retained at /customers/import-template
3. Duplicate handling by Customer Code first, then Email
4. Options to update existing customers or skip duplicates
5. Branch/location assignment through Branch Location ID or a default selected branch
6. Activity logging for import-created and import-updated customers

Safety rules:
- No Contacts module files were removed or changed.
- Customer master data still uses contacts table with type = customer.
- Customer records remain centralized by business_id/head office.
- Branch/location is assignment/filtering only.

Testing:
1. Upload/replace only Modules/Customers.
2. Run: php artisan view:clear && php artisan cache:clear
3. Open /customers/import.
4. Download template, add 1-2 customers, upload CSV.
5. Confirm customers appear in /customers and activity history is recorded if Phase 7 tables are installed.
