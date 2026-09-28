# CUS_032 – Distribution Dealer GPS Delivery Tracking + Live Status

## Purpose
Adds a read-only Distribution Dealer live tracking page and API endpoints inside `Modules/Customers`.

## Included
- Dealer portal menu item: Live Tracking
- `/distribution-dealer/live-tracking`
- `/distribution-dealer/deliveries/{id}/track`
- API endpoints:
  - `/api/dealer/tracking`
  - `/api/dealer/delivery/{id}/tracking`
  - `/api/dealer/vehicle-location`
- Optional SQL tables for delivery timeline and vehicle GPS location.

## Safety
This package does not modify Contact, Petro, PetroPD, Finance, or Distribution module files.
It reads available delivery data and falls back safely when GPS tables do not yet have records.

## Upload
Upload the included files preserving folder paths.

## SQL
Run:

```sql
Customers/Database/sql/CUS_032_delivery_tracking_tables.sql
```

## Clear Cache

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

## Test
1. Login as Distribution Dealer.
2. Open Live Tracking.
3. Confirm deliveries are visible.
4. Click Track Details.
5. Confirm no ERP sidebar appears and no other customer data is shown.
