# RestaurantNew Final Testing Checklist

## Core flow
- Waiter creates dine-in order.
- Cashier creates takeaway sale.
- Kitchen screen shows all received orders.
- Kitchen can print KOT.
- Kitchen can print bill where enabled.
- Kitchen status changes from received to preparing, ready and served.
- Cashier finalizes bill and records payment.

## Multi-tenant and multi-business
- Tenant A cannot see Tenant B data.
- Business A cannot see Business B data unless allowed by system access rules.
- Location filter applies correctly to POS, kitchen, reports and dashboards.

## Reports
- Sales report totals match bills.
- Payment report totals match received payments.
- Kitchen reports match KOT statuses.
- Inventory consumption matches finalized orders.

## Security
- Direct URL access blocked without permissions.
- Super Admin feature controls enable/disable RestaurantNew pages.
- Audit log records write actions.
