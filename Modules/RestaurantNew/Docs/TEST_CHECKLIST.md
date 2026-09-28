# Restaurant-New 2.0 Test Checklist

## Installation

- [ ] `RestaurantNew` is `true` in `modules_statuses.json`.
- [ ] Route list shows 79 `restaurant-new.*` routes.
- [ ] Tenant database contains exactly 45 `restnew_` tables.
- [ ] Tenant database contains 55 `restaurant_new.*` permissions.
- [ ] Restaurant-New appears in Manage and the sidebar without editing shared feature code.
- [ ] Running fresh-install or upgrade SQL twice creates no duplicate table, index or permission errors.
- [ ] No operational `restnew_` tables are created on the recognised central host.

## Isolation and access

- [ ] Business A cannot read or modify Business B Restaurant-New data.
- [ ] Restricted users see only permitted locations.
- [ ] Manager queues and reports respect location access when “All permitted locations” is selected.
- [ ] Transfer dispatch requires source access; receiving requires destination access.
- [ ] Location-specific suppliers/zones/tables/stations cannot be used by another location.
- [ ] Report users can open only their individually assigned report tabs.

## Orders, kitchen and settlement

- [ ] Open shift requires a permitted location and prevents a second open shift for the same user/location.
- [ ] Dine-in order locks its selected table.
- [ ] Concurrent orders cannot claim the same table.
- [ ] Takeaway order receives a collection token; delivery order does not.
- [ ] Delivery requires an address and a valid location delivery zone.
- [ ] Required modifier rules block incomplete submissions server-side.
- [ ] Invalid modifier/item and location/item combinations are rejected.
- [ ] KOTs split by kitchen station.
- [ ] Kitchen follows New → Accepted/Preparing → Ready.
- [ ] Recipe stock is consumed once only.
- [ ] Voiding a prepared unpaid item reverses stock once only.
- [ ] Split payments must exactly settle the balance; extra cash uses tendered amount/change.
- [ ] Paid order cannot receive another settlement.
- [ ] Table is released only after completion/cancellation as applicable.
- [ ] Ready, paid takeaway token can be called and collected.

## Stage 2 operations

- [ ] Reservation conflict check prevents overlapping use of the same table.
- [ ] Driver belongs to the active business.
- [ ] Delivery follows Waiting → Assigned → Dispatched → Delivered/Failed.
- [ ] Goods receipt remains draft until posted.
- [ ] Posting a receipt updates stock and weighted-average cost once.
- [ ] Stock transfer deducts source at dispatch and adds destination at receipt once.
- [ ] Stocktake variance updates balance and creates variance movement.
- [ ] Wastage cannot exceed available stock and creates value history.
- [ ] Controlled discount follows active dates/times/order type/minimum/maximum rules.
- [ ] A second controlled discount is rejected, including concurrent submission.

## Printing and reports

- [ ] Kitchen KOT print works.
- [ ] 58 mm, 80 mm and A4 bill layouts fit.
- [ ] Takeaway and collection-token prints work.
- [ ] Shift-close print reconciles expected and actual cash.
- [ ] All 13 report tabs filter by date and permitted location.
- [ ] CSV export headers and values are valid.
