# Customers RC13 Runtime Audit

Generated: 2026-07-01T12:00:06

Scope: `Modules/Customers` runtime files only. Documentation and README files are excluded from the score.

Legacy Contacts runtime dependency matches: 7

Notes:
- `contact_id` database columns are intentionally allowed because existing ERP transaction/payment/ledger tables still use this column name.
- Compatibility routes/controllers are retained to redirect old customer URLs into the standalone Customers module.

```
Modules/Customers/Http/Controllers/CustomerBalanceController.php:26:     * ContactController, ContactUtil, or contact module view files.
Modules/Customers/Http/Controllers/CustomerStatementController.php:13:     * does not route through ContactController or contact statement views.
Modules/Customers/Services/CustomerStandaloneAuditService.php:28:        'ContactsController',
Modules/Customers/Services/CustomerStandaloneAuditService.php:33:        "route('contacts",
Modules/Customers/Services/CustomerStandaloneAuditService.php:34:        'route("contacts',
Modules/Customers/Services/CustomerStandaloneAuditService.php:35:        "view('contact",
Modules/Customers/Services/CustomerStandaloneAuditService.php:36:        'view("contact',
```
