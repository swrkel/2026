# DISNEW_017 Mobile / Offline Testing Checklist

1. Confirm the Distribution New sidebar shows Mobile & Offline Sync.
2. Open `/distribution-new/mobile/sync-batches`.
3. Open `/distribution-new/mobile/devices`.
4. Test mobile login endpoint with a device UUID.
5. Test sales rep order push through `/distribution-new/api/sales-rep/orders`.
6. Test customer order push through `/distribution-new/api/customer/orders`.
7. Test driver checkpoint and ePOD endpoints.
8. Test offline sync push with at least one queued order item.
9. Confirm sync batch and item rows are created in tenant database.
10. Confirm all new tables use `disnew_` prefix.
