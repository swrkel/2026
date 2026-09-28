# Stage 2 Change Log

Stage 2 is cumulative and includes the complete Stage 1 operational core.

## Added

- Manager control centre with late KOT, reservations, deliveries, low stock and approval history
- Reservations and table conflict validation
- Delivery zones, fees, dispatches, drivers and delivery status workflow
- Controlled discounts, usage history, order adjustments and manager approvals
- Item voiding with prepared-stock reversal
- Table transfer history
- Restaurant-owned suppliers
- Goods receipts with batch/expiry details and weighted-average costing
- Inter-location ingredient transfers
- Ingredient stocktakes and variance posting
- Wastage posting and valuation
- Ingredient barcode, preferred supplier, purchase-unit conversion and expiry tracking
- Hourly, waiter, discount, void, stock usage, purchase, wastage and profitability reports
- Separate permissions for all advanced report tabs and operations
- Stage 2-only idempotent upgrade SQL

## Hardened

- Manager/report/transfer location scoping
- Delivery driver/business validation and transition locking
- Controlled-discount duplicate prevention after row locking
- Item-void duplicate prevention after row locking
- Location-specific shift opening and reconciliation locking
- Takeaway-only collection tokens
- Delivery availability separate from takeaway availability
