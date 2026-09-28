# Products New - Imported Products Missing From List Products Fix (07 Sep 2026)

## Issue
Products imported through `products-new/import-export` could be marked as imported while no usable product row was visible in the active business List Products page. Older import sessions could also contain rows marked `imported` without a corresponding product master row.

## Fix
- Import review/commit no longer depends on implicit `{session}` route-model binding; the import session is resolved after Products New tenant/business context is initialized.
- Every committed product is written through `ProductWriteService` and verified in the active business `products` master before success is returned.
- Imported active/inactive state is normalized across `not_for_selling`, `is_inactive`, and `products_new_status` when those columns exist.
- Older phantom import lines are safely reconciled from their saved CSV payload when the Import / Export Centre is opened.
- Repair is business/user scoped and trace-based so already repaired/current imports are skipped on later visits.
- No new database table or migration is required.

## Changed files
- `Http/Controllers/ImportExportController.php`
- `Services/ImportExport/ProductImportService.php`
- `Services/ProductWriteService.php`
- `Resources/views/import_export/index.blade.php`
