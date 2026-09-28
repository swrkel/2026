# CUS_025 Distribution Dealer Analytics Dashboard

## Files included
- Customers/Http/Controllers/CustomerPortalAnalyticsController.php
- Customers/Routes/portal.php
- Customers/Resources/views/portal/analytics.blade.php
- Customers/Resources/views/portal/partials_nav.blade.php
- Customers/Resources/views/portal/dashboard.blade.php

## What this adds
- Distribution Dealer Portal → Analytics
- Current outstanding, credit limit, available credit, monthly purchases/payments, open orders
- Credit utilization indicator
- Monthly purchase/payment trends
- Top purchased products
- Order status summary
- Performance snapshot

## Safety notes
- No Contact module files changed.
- No Petro/PetroPD/PumperDashboard/Finance files changed.
- No SQL required.
- All analytics queries are table/column guarded for safer deployment.

## After upload
Run:
php artisan optimize:clear
php artisan view:clear

## Test
1. Login as Distribution Dealer.
2. Open Dashboard.
3. Click Analytics.
4. Confirm the page opens and data is limited to the logged-in dealer only.
