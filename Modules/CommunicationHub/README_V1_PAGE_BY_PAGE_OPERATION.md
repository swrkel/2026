# Communication Hub v1.0 - Page by Page Operation Package

## Deploy
Replace only:

`Modules/CommunicationHub/`

Make sure your root `modules_statuses.json` contains:

```json
"CommunicationHub": true
```

Then run:

```bash
php artisan optimize:clear
```

## Tenant SQL
Run this in each tenant DB:

`Modules/CommunicationHub/Database/SQL/00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql`

If the previous CommunicationHub SQL was already installed, you can run only:

`Modules/CommunicationHub/Database/SQL/02_COMMERCIAL_SMS_PLATFORM_TABLES.sql`

Optional permissions:

`Modules/CommunicationHub/Database/SQL/01_COMMUNICATION_HUB_PERMISSIONS.sql`

## UI testing order
1. Communication Hub > Dashboard
2. SMS Packages
3. SMS Clients
4. Business Wallets
5. Credit Refills
6. Credit Transactions
7. Send SMS
8. Bulk SMS
9. API Tokens
10. API Logs
11. Delivery Reports
12. Profit Reports

## Module registration standard
This package adds:

`Modules/CommunicationHub/module_registration.php`

This is the first module-level registration standard file. Later we can apply the same pattern to Customers, Banking, PetroPD and other standalone modules.

## Notes
- Menu remains inside `Modules/CommunicationHub`.
- Main sidebar should only include the module sidebar partial.
- Versioned API routes remain available under `/api/v1/communication-hub`.
