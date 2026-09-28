# Communication Hub Commercial UI + Sidebar-Safe Package

## Deployment
Replace only:

`Modules/CommunicationHub/`

This package does not replace the main `resources/views/.../sidebar` file.

## Sidebar approach
The Communication Hub sidebar is module-owned:

- `Modules/CommunicationHub/Config/menu.php`
- `Modules/CommunicationHub/Resources/views/partials/sidebar.blade.php`

To show the module in the ERP sidebar, use only this safe include line in the main sidebar if it is not already present:

```php
@includeIf('communicationhub::partials.sidebar')
```

Do not hardcode Communication Hub submenu entries in the main sidebar.

## Added tester-visible commercial SMS pages
- SMS Dashboard
- Send SMS
- Bulk SMS
- Scheduled SMS
- SMS Packages
- Business Wallets
- Credit Refills
- Credit Transactions
- SMS Clients
- Reseller Dashboard
- API Tokens
- API Logs
- API Documentation
- Delivery Reports
- SMS Profit Reports

These are UI-first pages so testers can review the commercial SMS-selling workflow before deeper automation is connected.
