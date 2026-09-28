# Offline / Auto-Sync Feature Summary

## Overview
Enables users to work offline and automatically synchronize data at frequent intervals. Adds `offline_mode` flags to target pages, admin-configurable auto-sync intervals per business location, and auto-enables overselling during offline-mode sync.

## Updated Pages (offline_mode)
- POS: resources/views/sale_pos/create.blade.php
- Add Sales: resources/views/sell/create.blade.php
- Touch POS: resources/views/tpos_sale/create.blade.php, resources/views/tpos_sale/edit.blade.php
- Purchase: resources/views/purchase/create.blade.php, resources/views/purchase/edit.blade.php
- Ezy Invoice: Modules/EzyInvoice/Resources/views/invoices/add.blade.php

## Admin Interval Setting
- Column: `business_locations.auto_synchronization_intervals` (minutes)
- UI: Business Location → Action → Settings
- Controller: app/Http/Controllers/LocationSettingsController.php
- Client fetch + apply: public/js/offline-wrapper.js → `OfflineQueue.startAutoSync(interval)`

## Overselling Handling
- Server-side overselling permitted when `offline_mode=1` during sync.
- File: app/Utils/TransactionUtil.php (maps purchase-sell lines)

## Permissions
- Interval update checks store-level permission `UserStorePermission.offline_sync_manage`.
- Offline scripts gated by existing offline-access flags injected via AppServiceProvider.

## Key SQL
- Add column:
  ```sql
  ALTER TABLE business_locations
  ADD COLUMN auto_synchronization_intervals INT DEFAULT 0;
  ```
- Read interval for location:
  ```sql
  SELECT auto_synchronization_intervals
  FROM business_locations
  WHERE business_id = ? AND id = ?
  LIMIT 1;
  ```
- Read interval with fallback (default 5):
  ```sql
  SELECT COALESCE(NULLIF(auto_synchronization_intervals, 0), 5) AS interval
  FROM business_locations
  WHERE business_id = ? AND id = ?
  LIMIT 1;
  ```
- Update interval:
  ```sql
  UPDATE business_locations
  SET auto_synchronization_intervals = ?
  WHERE business_id = ? AND id = ?;
  ```
- List intervals for business:
  ```sql
  SELECT id, name, auto_synchronization_intervals
  FROM business_locations
  WHERE business_id = ?
  ORDER BY name;
  ```
- Check store-level permission to manage interval:
  ```sql
  SELECT EXISTS(
    SELECT 1
    FROM user_store_permissions
    WHERE business_id = ? AND user_id = ? AND offline_sync_manage = 1
  ) AS can_manage;
  ```

## Quick Validation
- Set interval at Location Settings; badge shows `Auto-sync: N min`.
- Toggle offline; submit POS/Add Sales/Ezy Invoice/Purchase; reconnect to auto-sync queued items.
- Confirm overselling does not block sync when offline-mode submissions are processed.
