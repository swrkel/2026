# S350 Customers Module Testing 3 - Standalone Contact-Parity Pages

This package moves the requested customer pages into the standalone Customers module route/view/controller layer while keeping existing working logic and database tables intact.

## Covered pages
- Customer Statement
- Customer Payment
- Outstanding Received
- Edit Received Outstanding
- Customer Payment Bulk
- List Customer Payments
- Customer Interest
- Interest Settings
- Ledger Discount

## Important
- Routes are inside `Modules/Customers/Routes/web.php`.
- Controllers are inside `Modules/Customers/Http/Controllers`.
- Views are inside `Modules/Customers/Resources/views` using the `customers::` namespace.
- The package does not change main Contacts files.
- The page routes are registered before the `/customers/{id}` catch-all to prevent 404 or customer-id routing conflicts.

## SQL
No SQL required for this package.
