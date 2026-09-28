# IS1672 Customer Statements — Stage 014 Large Parcel

This parcel consolidates Stage 012, hotfix 012A and direct date-range fix 013, then adds functional server-side wiring for:

- List Customer Statements filters and DataTable loading.
- Statement-date and printed-date filtering.
- Business location, customer, customer type and universal search filters.
- List Statement Payments filters and DataTable loading.
- Existing Customers-owned statement actions returned by the standalone controller: View, Pay Total Statement, Print, Excel, PDF, Email, VAT conversion and permission-controlled Delete.
- Typed Custom Date Range modal and full standard preset list.

No schema changes are introduced. Existing customer statement tables and existing standalone controller operations are reused.

Deployment:
1. Copy Modules and public folders preserving paths.
2. php artisan optimize:clear
3. php artisan route:clear
4. php artisan view:clear
5. Browser hard refresh (Ctrl+F5).
