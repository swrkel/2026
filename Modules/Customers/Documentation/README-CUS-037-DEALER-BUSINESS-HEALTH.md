# CUS-037 Distribution Dealer Business Health Score

## Included
- Business Health portal page: `/distribution-dealer/business-health`
- Overall dealer score 0-100
- Credit, payment, purchase, delivery, loyalty, and growth health scores
- Credit utilization and outstanding summary
- AI-style health insights
- 12-month health timeline

## Upload Paths
- `Modules/Customers/Http/Controllers/CustomerBusinessHealthController.php`
- `Modules/Customers/Services/CustomerHealthScoreService.php`
- `Modules/Customers/Resources/views/portal/business_health.blade.php`
- `Modules/Customers/Resources/views/portal/partials_nav.blade.php`
- `Modules/Customers/Routes/portal.php`

## After Upload
```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

## Test
1. Login as Distribution Dealer.
2. Open Business Health from the portal menu.
3. Confirm the scores, outstanding/credit summary, insights, and timeline load only for the logged-in dealer.
