# RestaurantNew Deployment Checklist

## Before deployment
- [ ] Backup code.
- [ ] Backup master database.
- [ ] Backup tenant databases.
- [ ] Confirm correct tenant/business/location IDs.
- [ ] Confirm module folder path is `Modules/RestaurantNew`.

## SQL
- [ ] Run CREATE scripts.
- [ ] Run ALTER scripts.
- [ ] Run INDEX scripts.
- [ ] Run INSERT scripts.
- [ ] Run PERMISSION scripts.
- [ ] Run SEEDER scripts where required.

## Application
- [ ] Clear cache/config/routes/views.
- [ ] Verify module is enabled.
- [ ] Verify sidebar link appears.
- [ ] Verify permissions are visible.
- [ ] Verify direct URLs are protected.

## Functional smoke test
- [ ] Create dining area/table.
- [ ] Create menu item.
- [ ] Create waiter/cashier order.
- [ ] Confirm kitchen receives order.
- [ ] Print/reprint KOT.
- [ ] Update kitchen status.
- [ ] Finalize bill/payment.
- [ ] Verify sales report.
- [ ] Verify inventory deduction if recipe is configured.
